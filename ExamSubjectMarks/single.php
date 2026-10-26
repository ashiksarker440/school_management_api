<?php
include '../connection.php';

header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = $_GET['id'] ?? '';
if (!ctype_digit((string) $id) || (int) $id < 1) {
    http_response_code(400);
    echo json_encode(['status' => false, 'data' => [], 'message' => 'Invalid record ID.']);
    exit;
}

try {
    $statement = $cc->conn->prepare(
        "SELECT esm.id, esm.exam_id, e.exam_name, esm.class_id, esm.subject_id,
                esm.full_marks, esm.pass_marks, esm.exam_date, esm.deleted_at
         FROM exam_subject_marks esm
         LEFT JOIN exams e ON e.id = esm.exam_id AND e.deleted_at IS NULL
         WHERE esm.id = ? AND esm.deleted_at IS NULL
         LIMIT 1"
    );
    $recordId = (int) $id;
    $statement->bind_param('i', $recordId);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['status' => false, 'data' => [], 'message' => 'Record not found.']);
        exit;
    }

    echo json_encode(['status' => true, 'data' => [$row], 'message' => 'Record found']);
} catch (mysqli_sql_exception $exception) {
    error_log('Exam subject marks detail API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['status' => false, 'data' => [], 'message' => 'Unable to load record.']);
}
?>
