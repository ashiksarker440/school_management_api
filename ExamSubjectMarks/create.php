<?php
include '../connection.php';

header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function valid_exam_date($value)
{
    if (!is_string($value)) {
        return false;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

$examId = filter_var($_POST['exam_id'] ?? null, FILTER_VALIDATE_INT);
$classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
$subjectId = filter_var($_POST['subject_id'] ?? null, FILTER_VALIDATE_INT);
$fullMarks = $_POST['full_marks'] ?? '';
$passMarks = $_POST['pass_marks'] ?? '';
$examDate = $_POST['exam_date'] ?? '';

if (!$examId || $examId < 1 || !$classId || $classId < 1 || !$subjectId || $subjectId < 1
    || !is_numeric($fullMarks) || !is_numeric($passMarks)
    || (float) $fullMarks <= 0 || (float) $passMarks < 0 || (float) $passMarks > (float) $fullMarks
    || !valid_exam_date($examDate)) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Provide valid exam, class, and subject IDs; valid marks; and an exam date.'
    ]);
    exit;
}

try {
    $examCheck = $cc->conn->prepare('SELECT id FROM exams WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $examCheck->bind_param('i', $examId);
    $examCheck->execute();
    $examExists = $examCheck->get_result()->num_rows > 0;
    $examCheck->close();
    if (!$examExists) {
        http_response_code(400);
        echo json_encode(['status' => false, 'data' => [], 'message' => 'Selected exam does not exist.']);
        exit;
    }

    $classCheck = $cc->conn->prepare('SELECT id FROM classes WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $classCheck->bind_param('i', $classId);
    $classCheck->execute();
    $classExists = $classCheck->get_result()->num_rows > 0;
    $classCheck->close();
    if (!$classExists) {
        http_response_code(400);
        echo json_encode(['status' => false, 'data' => [], 'message' => 'Selected class does not exist.']);
        exit;
    }

    $subjectCheck = $cc->conn->prepare('SELECT id FROM subjects WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $subjectCheck->bind_param('i', $subjectId);
    $subjectCheck->execute();
    $subjectExists = $subjectCheck->get_result()->num_rows > 0;
    $subjectCheck->close();
    if (!$subjectExists) {
        http_response_code(400);
        echo json_encode(['status' => false, 'data' => [], 'message' => 'Selected subject does not exist.']);
        exit;
    }

    $statement = $cc->conn->prepare(
        'INSERT INTO exam_subject_marks (exam_id, class_id, subject_id, full_marks, pass_marks, exam_date)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $fullMarksValue = (float) $fullMarks;
    $passMarksValue = (float) $passMarks;
    $statement->bind_param('iiidds', $examId, $classId, $subjectId, $fullMarksValue, $passMarksValue, $examDate);
    $statement->execute();
    $recordId = $cc->conn->insert_id;
    $statement->close();

    echo json_encode([
        'status' => true,
        'data' => $recordId,
        'message' => 'Exam subject marks created successfully.'
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log('Exam subject marks create API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['status' => false, 'data' => [], 'message' => 'Unable to create exam subject marks.']);
}
?>
