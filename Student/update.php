<?php
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function update_response($status, $data, $message, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$data = $_POST;
$name = trim((string) ($data['name'] ?? ''));
$admissionNumber = trim((string) ($data['admission_number'] ?? ''));
$classId = filter_var($data['class_id'] ?? null, FILTER_VALIDATE_INT);
$sectionId = filter_var($data['section_id'] ?? null, FILTER_VALIDATE_INT);
$rollNo = $data['roll_no'];

if (!$id || $id < 1 || $name === '' || $admissionNumber === '' || !$classId || !$sectionId || !$rollNo) {
    update_response(false, [], 'Student, name, admission number, class, section and roll number are required.', 400);
}

$uploadedPath = '';
$transactionStarted = false;

try {
    $sessionResult = $cc->conn->query(
        "SELECT id FROM sessions
         WHERE is_current = 1 AND deleted_at IS NULL
         ORDER BY id DESC LIMIT 1"
    );
    $session = $sessionResult->fetch_assoc();
    if (!$session) {
        update_response(false, [], 'No current academic session is configured.', 409);
    }
    $sessionId = (int) $session['id'];

    $studentStatement = $cc->conn->prepare(
        "SELECT id, user_id, photo FROM students
         WHERE id = ? AND deleted_at IS NULL
         LIMIT 1"
    );
    $studentStatement->bind_param('i', $id);
    $studentStatement->execute();
    $existingStudent = $studentStatement->get_result()->fetch_assoc();
    $studentStatement->close();
    if (!$existingStudent) {
        update_response(false, [], 'Student not found.', 404);
    }

    $sectionStatement = $cc->conn->prepare(
        "SELECT id FROM sections
         WHERE id = ? AND class_id = ? AND deleted_at IS NULL
         LIMIT 1"
    );
    $sectionStatement->bind_param('ii', $sectionId, $classId);
    $sectionStatement->execute();
    $sectionExists = $sectionStatement->get_result()->fetch_assoc();
    $sectionStatement->close();
    if (!$sectionExists) {
        update_response(false, [], 'The selected section does not belong to the selected class.', 400);
    }

    $uploadDir = '../images/student_images/';
    if (!empty($_FILES['files']['name'][0])) {
        $fileName = bin2hex(random_bytes(8)) . '_' . basename($_FILES['files']['name'][0]);
        $targetPath = $uploadDir . $fileName;
        if (
            !is_uploaded_file($_FILES['files']['tmp_name'][0]) ||
            !move_uploaded_file($_FILES['files']['tmp_name'][0], $targetPath)
        ) {
            update_response(false, [], 'Student photo could not be uploaded.', 400);
        }
        $uploadedPath = 'images/student_images/' . $fileName;
    }

    $email = trim((string) ($data['email'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $guardianName = trim((string) ($data['guardian_name'] ?? ''));
    $guardianRelation = trim((string) ($data['guardian_relation'] ?? ''));
    $guardianPhone = trim((string) ($data['guardian_phone'] ?? ''));
    $address = trim((string) ($data['address'] ?? ''));
    $username = $email !== '' ? $email : $admissionNumber;

    $cc->conn->begin_transaction();
    $transactionStarted = true;

    $studentSql = $uploadedPath !== ''
        ? "UPDATE students
           SET admission_number = ?, name = ?, email = ?, phone = ?, guardian_name = ?,
               guardian_relation = ?, guardian_phone = ?, address = ?, photo = ?
           WHERE id = ?"
        : "UPDATE students
           SET admission_number = ?, name = ?, email = ?, phone = ?, guardian_name = ?,
               guardian_relation = ?, guardian_phone = ?, address = ?
           WHERE id = ?";
    $studentUpdate = $cc->conn->prepare($studentSql);
    if ($uploadedPath !== '') {
        $studentUpdate->bind_param(
            'sssssssssi',
            $admissionNumber,
            $name,
            $email,
            $phone,
            $guardianName,
            $guardianRelation,
            $guardianPhone,
            $address,
            $uploadedPath,
            $id
        );
    } else {
        $studentUpdate->bind_param(
            'ssssssssi',
            $admissionNumber,
            $name,
            $email,
            $phone,
            $guardianName,
            $guardianRelation,
            $guardianPhone,
            $address,
            $id
        );
    }
    $studentUpdate->execute();
    $studentUpdate->close();

    if (!empty($existingStudent['user_id'])) {
        $userId = (int) $existingStudent['user_id'];
        $password = trim((string) ($data['password'] ?? ''));
        if ($password !== '') {
            $passwordHash = md5($password);
            $userUpdate = $cc->conn->prepare(
                "UPDATE users
                 SET email = ?, name = ?, username = ?, role = 'student', is_active = 1, password = ?
                 WHERE id = ?"
            );
            $userUpdate->bind_param('ssssi', $email, $name, $username, $passwordHash, $userId);
        } else {
            $userUpdate = $cc->conn->prepare(
                "UPDATE users
                 SET email = ?, name = ?, username = ?, role = 'student', is_active = 1
                 WHERE id = ?"
            );
            $userUpdate->bind_param('sssi', $email, $name, $username, $userId);
        }
        $userUpdate->execute();
        $userUpdate->close();
    }

    $enrollmentStatement = $cc->conn->prepare(
        "SELECT id FROM student_enrollments
         WHERE student_id = ? AND session_id = ? AND deleted_at IS NULL
         ORDER BY id DESC LIMIT 1"
    );
    $enrollmentStatement->bind_param('ii', $id, $sessionId);
    $enrollmentStatement->execute();
    $existingEnrollment = $enrollmentStatement->get_result()->fetch_assoc();
    $enrollmentStatement->close();

    if ($existingEnrollment) {
        $enrollmentId = (int) $existingEnrollment['id'];
        $enrollmentUpdate = $cc->conn->prepare(
            "UPDATE student_enrollments SET class_id = ?, section_id = ?, roll_no = ? WHERE id = ?"
        );
        $enrollmentUpdate->bind_param('iisi', $classId, $sectionId, $rollNo, $enrollmentId);
        $enrollmentUpdate->execute();
        $enrollmentUpdate->close();
    } else {
        $enrollmentInsert = $cc->conn->prepare(
            "INSERT INTO student_enrollments (student_id, session_id, class_id, section_id, roll_no)
             VALUES (?, ?, ?, ?, ?)"
        );
        $enrollmentInsert->bind_param('iiiis', $id, $sessionId, $classId, $sectionId, $rollNo);
        $enrollmentInsert->execute();
        $enrollmentInsert->close();
    }

    $cc->conn->commit();
    $transactionStarted = false;

    if (
        $uploadedPath !== '' && !empty($existingStudent['photo']) &&
        file_exists('../' . $existingStudent['photo'])
    ) {
        unlink('../' . $existingStudent['photo']);
    }

    update_response(true, $id, 'Student updated successfully.');
} catch (mysqli_sql_exception $exception) {
    if ($transactionStarted) {
        $cc->conn->rollback();
    }
    if ($uploadedPath !== '' && file_exists('../' . $uploadedPath)) {
        unlink('../' . $uploadedPath);
    }
    error_log('Student update API error: ' . $exception->getMessage());
    update_response(false, [], 'Student could not be updated. Please check the student details and try again.', 500);
}
?>