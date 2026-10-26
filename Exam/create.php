<?php
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$examName = trim($_POST['exam_name'] ?? '');
$type = trim($_POST['type'] ?? '');

if ($examName === '' || $type === '') {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Exam name and type are required.'
    ]);
    exit;
}

$createdAt = date('Y-m-d H:i:s');
$statement = $cc->conn->prepare(
    'INSERT INTO exams (exam_name, type, created_at) VALUES (?, ?, ?)'
);
$statement->bind_param('sss', $examName, $type, $createdAt);
$statement->execute();
$id = $cc->conn->insert_id;
$statement->close();

echo json_encode([
    'status' => true,
    'data' => $id,
    'message' => 'Exam created successfully.'
]);
?>
