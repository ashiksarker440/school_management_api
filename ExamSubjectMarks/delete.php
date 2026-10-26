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
    $recordId = (int) $id;
    $deletedAt = date('Y-m-d H:i:s');
    $statement = $cc->conn->prepare(
        'UPDATE exam_subject_marks SET deleted_at = ? WHERE id = ? AND deleted_at IS NULL'
    );
    $statement->bind_param('si', $deletedAt, $recordId);
    $statement->execute();
    $deleted = $statement->affected_rows;
    $statement->close();

    if ($deleted === 0) {
        http_response_code(404);
        echo json_encode(['status' => false, 'data' => [], 'message' => 'Record not found.']);
        exit;
    }

    echo json_encode([
        'status' => true,
        'data' => $deleted,
        'message' => 'Exam subject marks deleted successfully.'
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log('Exam subject marks delete API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['status' => false, 'data' => [], 'message' => 'Unable to delete exam subject marks.']);
}
?>
