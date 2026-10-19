<?php
include '../connection.php';
include '../auth_check.php';

header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function respond($status, $data, $message, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

function valid_date($value)
{
    if (!is_string($value)) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

$transactionStarted = false;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = [
            'classes' => [],
            'sections' => [],
            'students' => []
        ];

        $classResult = $cc->conn->query(
            "SELECT DISTINCT se.class_id, c.name AS class_name
             FROM student_enrollments se
             INNER JOIN students s ON s.id = se.student_id
             join classes c on c.id = se.class_id
             WHERE se.deleted_at IS NULL AND s.deleted_at IS NULL
             ORDER BY se.class_id"
        );
        while ($row = $classResult->fetch_assoc()) {
            $data['classes'][] = [
                'class_id' => $row['class_id'],
                'name' => $row['class_name']
            ];
        }

        $classId = trim((string) ($_GET['class_id'] ?? ''));
        $sectionId = trim((string) ($_GET['section_id'] ?? ''));
        $date = $_GET['date'] ?? '';

        if ($classId !== '') {
            $sectionStatement = $cc->conn->prepare(
                "SELECT DISTINCT section_id, s2.name AS section_name
                 FROM student_enrollments se
                 INNER JOIN students s ON s.id = se.student_id
                 join sections s2 on s2.id = se.section_id
                 WHERE se.class_id = ? AND se.deleted_at IS NULL AND s.deleted_at IS NULL
                 ORDER BY section_id"
            );
            $sectionStatement->bind_param('s', $classId);
            $sectionStatement->execute();
            $sectionResult = $sectionStatement->get_result();
            while ($row = $sectionResult->fetch_assoc()) {
                $data['sections'][] = [
                    'section_id' => $row['section_id'],
                    'name' => $row['section_name']
                ];
            }
            $sectionStatement->close();
        }

        if ($sectionId !== '') {
            if ($classId === '' || !valid_date($date)) {
                respond(false, [], 'A valid date, class and section are required.', 400);
            }

            $studentStatement = $cc->conn->prepare(
                "SELECT s.id AS student_id, s.admission_number, s.name, se.roll_no,
                        a.id AS attendance_id, a.status
                 FROM students s
                 INNER JOIN (
                     SELECT student_id, MIN(roll_no) AS roll_no
                     FROM student_enrollments
                     WHERE class_id = ? AND section_id = ? AND deleted_at IS NULL
                     GROUP BY student_id
                 ) se ON se.student_id = s.id
                 LEFT JOIN (
                     SELECT student_id, MAX(id) AS id
                     FROM attendance
                     WHERE date = ? AND deleted_at IS NULL
                     GROUP BY student_id
                 ) latest ON latest.student_id = s.id
                 LEFT JOIN attendance a ON a.id = latest.id
                 WHERE s.deleted_at IS NULL
                 ORDER BY se.roll_no, s.name"
            );
            $studentStatement->bind_param('sss', $classId, $sectionId, $date);
            $studentStatement->execute();
            $studentResult = $studentStatement->get_result();
            while ($row = $studentResult->fetch_assoc()) {
                $data['students'][] = $row;
            }
            $studentStatement->close();
        }

        respond(true, $data, 'Attendance roster loaded.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, [], 'Method not allowed.', 405);
    }

    $date = $_POST['date'] ?? '';
    $classId = trim((string) ($_POST['class_id'] ?? ''));
    $sectionId = trim((string) ($_POST['section_id'] ?? ''));
    $records = json_decode($_POST['records'] ?? '', true);

    if (!valid_date($date) || $classId === '' || $sectionId === '') {
        respond(false, [], 'A valid date, class and section are required.', 400);
    }
    if (!is_array($records) || count($records) === 0) {
        respond(false, [], 'At least one student attendance record is required.', 400);
    }

    $validatedRecords = [];
    $studentIds = [];
    foreach ($records as $record) {
        if (!is_array($record)) {
            respond(false, [], 'Invalid attendance record.', 400);
        }

        $studentId = filter_var($record['student_id'] ?? null, FILTER_VALIDATE_INT);
        $status = filter_var($record['status'] ?? null, FILTER_VALIDATE_INT);
        if (!$studentId || !in_array($status, [0, 1, 2], true)) {
            respond(false, [], 'Each student must have a valid attendance status.', 400);
        }
        if (isset($studentIds[$studentId])) {
            respond(false, [], 'Duplicate student attendance records are not allowed.', 400);
        }

        $studentIds[$studentId] = true;
        $validatedRecords[] = [
            'student_id' => $studentId,
            'status' => $status
        ];
    }

    $rosterStatement = $cc->conn->prepare(
        "SELECT DISTINCT s.id
         FROM student_enrollments se
         INNER JOIN students s ON s.id = se.student_id
         WHERE se.class_id = ? AND se.section_id = ?
           AND se.deleted_at IS NULL AND s.deleted_at IS NULL"
    );
    $rosterStatement->bind_param('ss', $classId, $sectionId);
    $rosterStatement->execute();
    $rosterResult = $rosterStatement->get_result();
    $rosterIds = [];
    while ($row = $rosterResult->fetch_assoc()) {
        $rosterIds[(int) $row['id']] = true;
    }
    $rosterStatement->close();

    foreach ($validatedRecords as $record) {
        if (!isset($rosterIds[$record['student_id']])) {
            respond(false, [], 'A submitted student is not enrolled in the selected class and section.', 400);
        }
    }

    $cc->conn->begin_transaction();
    $transactionStarted = true;
    $findStatement = $cc->conn->prepare(
        "SELECT id FROM attendance
         WHERE date = ? AND student_id = ? AND deleted_at IS NULL
         ORDER BY id DESC LIMIT 1 FOR UPDATE"
    );
    $updateStatement = $cc->conn->prepare(
        "UPDATE attendance SET status = ? WHERE id = ?"
    );
    $insertStatement = $cc->conn->prepare(
        "INSERT INTO attendance (date, student_id, status, created_at)
         VALUES (?, ?, ?, NOW())"
    );

    $inserted = 0;
    $updated = 0;
    foreach ($validatedRecords as $record) {
        $studentId = $record['student_id'];
        $status = $record['status'];
        $findStatement->bind_param('si', $date, $studentId);
        $findStatement->execute();
        $existing = $findStatement->get_result()->fetch_assoc();

        if ($existing) {
            $attendanceId = (int) $existing['id'];
            $updateStatement->bind_param('ii', $status, $attendanceId);
            $updateStatement->execute();
            $updated++;
        } else {
            $insertStatement->bind_param('sii', $date, $studentId, $status);
            $insertStatement->execute();
            $inserted++;
        }
    }

    $findStatement->close();
    $updateStatement->close();
    $insertStatement->close();
    $cc->conn->commit();
    $transactionStarted = false;

    respond(true, ['inserted' => $inserted, 'updated' => $updated], 'Attendance saved successfully.');
} catch (mysqli_sql_exception $exception) {
    if ($transactionStarted) {
        $cc->conn->rollback();
    }
    error_log('Bulk attendance API error: ' . $exception->getMessage());
    respond(false, [], 'Unable to load or save attendance. Please check the database configuration.', 500);
}
?>