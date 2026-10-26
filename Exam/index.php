<?php
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$result = $cc->conn->query(
    "SELECT id, exam_name, type, created_at, deleted_at
     FROM exams
     WHERE deleted_at IS NULL
     ORDER BY created_at DESC, id DESC"
);
$rows = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'status' => true,
    'data' => $rows,
    'message' => count($rows) ? 'Records found' : 'No records found'
]);
?>
