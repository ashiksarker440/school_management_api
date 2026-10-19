<?php
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function create_response($status, $data, $message, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

$data = $_POST;
$name = trim((string) ($data['name'] ?? ''));
$admissionNumber = trim((string) ($data['admission_number'] ?? ''));
$password = (string) ($data['password'] ?? '');
$classId = filter_var($data['class_id'] ?? null, FILTER_VALIDATE_INT);
$sectionId = filter_var($data['section_id'] ?? null, FILTER_VALIDATE_INT);
$rollNo = filter_var($data['roll_no'] ?? null, FILTER_VALIDATE_INT);

if ($name === '' || $admissionNumber === '' || $password === '' || !$classId || !$sectionId || !$rollNo) {
    create_response(false, [], 'Name, admission number, password, class, section and roll number are required.', 400);
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
        create_response(false, [], 'No current academic session is configured.', 409);
    }
    $sessionId = (int) $session['id'];

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
        create_response(false, [], 'The selected section does not belong to the selected class.', 400);
    }

    if (!empty($_FILES['files']['name'][0])) {
        $uploadDir = '../images/student_images/';
        $fileName = bin2hex(random_bytes(8)) . '_' . basename($_FILES['files']['name'][0]);
        $targetPath = $uploadDir . $fileName;
        if (
            !is_uploaded_file($_FILES['files']['tmp_name'][0]) ||
            !move_uploaded_file($_FILES['files']['tmp_name'][0], $targetPath)
        ) {
            create_response(false, [], 'Student photo could not be uploaded.', 400);
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
    $createdAt = date('Y-m-d H:i:s');
    $passwordHash = md5($password);

    $cc->conn->begin_transaction();
    $transactionStarted = true;

    $userStatement = $cc->conn->prepare(
        "INSERT INTO users (email, name, username, role, is_active, created_at, password)
         VALUES (?, ?, ?, 'student', 1, ?, ?)"
    );
    $userStatement->bind_param('sssss', $email, $name, $username, $createdAt, $passwordHash);
    $userStatement->execute();
    $userId = $cc->conn->insert_id;
    $userStatement->close();

    $studentStatement = $cc->conn->prepare(
        "INSERT INTO students
            (user_id, admission_number, name, email, phone, guardian_name,
             guardian_relation, guardian_phone, address, photo, created_at, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
    );
    $studentStatement->bind_param(
        'issssssssss',
        $userId,
        $admissionNumber,
        $name,
        $email,
        $phone,
        $guardianName,
        $guardianRelation,
        $guardianPhone,
        $address,
        $uploadedPath,
        $createdAt
    );
    $studentStatement->execute();
    $studentId = $cc->conn->insert_id;
    $studentStatement->close();

    $enrollmentStatement = $cc->conn->prepare(
        "INSERT INTO student_enrollments (student_id, session_id, class_id, section_id, roll_no)
         VALUES (?, ?, ?, ?, ?)"
    );
    $enrollmentStatement->bind_param('iiiii', $studentId, $sessionId, $classId, $sectionId, $rollNo);
    $enrollmentStatement->execute();
    $enrollmentStatement->close();

    $cc->conn->commit();
    $transactionStarted = false;

    create_response(true, $studentId, 'Student created successfully.');
} catch (mysqli_sql_exception $exception) {
    if ($transactionStarted) {
        $cc->conn->rollback();
    }
    if ($uploadedPath !== '' && file_exists('../' . $uploadedPath)) {
        unlink('../' . $uploadedPath);
    }
    error_log('Student create API error: ' . $exception->getMessage());
    create_response(false, [], 'Student could not be created. Please check the student details and try again.', 500);
}
?>