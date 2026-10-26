<?php
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = $_GET['id'] ?? '';
if (!ctype_digit((string) $id) || (int) $id < 1) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Invalid exam ID.'
    ]);
    exit;
}

$statement = $cc->conn->prepare(
    "SELECT id, exam_name, type, created_at, deleted_at
     FROM exams
     WHERE id = ? AND deleted_at IS NULL
     LIMIT 1"
);
$examId = (int) $id;
$statement->bind_param('i', $examId);
$statement->execute();
$row = $statement->get_result()->fetch_assoc();
$statement->close();

if (!$row) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Exam not found.'
    ]);
    exit;
}

echo json_encode([
    'status' => true,
    'data' => [$row],
    'message' => 'Record found'
]);
?>
