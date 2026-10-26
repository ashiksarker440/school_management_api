<?php
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = $_GET['id'] ?? '';
$examName = trim($_POST['exam_name'] ?? '');
$type = trim($_POST['type'] ?? '');

if (!ctype_digit((string) $id) || (int) $id < 1 || $examName === '' || $type === '') {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'A valid exam ID, exam name, and type are required.'
    ]);
    exit;
}

$examId = (int) $id;
$check = $cc->conn->prepare(
    'SELECT id FROM exams WHERE id = ? AND deleted_at IS NULL LIMIT 1'
);
$check->bind_param('i', $examId);
$check->execute();
$exists = $check->get_result()->num_rows > 0;
$check->close();

if (!$exists) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Exam not found.'
    ]);
    exit;
}

$statement = $cc->conn->prepare(
    'UPDATE exams SET exam_name = ?, type = ? WHERE id = ? AND deleted_at IS NULL'
);
$statement->bind_param('ssi', $examName, $type, $examId);
$statement->execute();
$updated = $statement->affected_rows;
$statement->close();

echo json_encode([
    'status' => true,
    'data' => $updated,
    'message' => 'Exam updated successfully.'
]);
?>
