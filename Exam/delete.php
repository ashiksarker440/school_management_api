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

$examId = (int) $id;
$statement = $cc->conn->prepare(
    'UPDATE exams SET deleted_at = ? WHERE id = ? AND deleted_at IS NULL'
);
$deletedAt = date('Y-m-d H:i:s');
$statement->bind_param('si', $deletedAt, $examId);
$statement->execute();
$deleted = $statement->affected_rows;
$statement->close();

if ($deleted === 0) {
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
    'data' => $deleted,
    'message' => 'Exam deleted successfully.'
]);
?>
