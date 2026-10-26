<?php
include '../connection.php';

header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $classes = $cc->conn->query(
        'SELECT id, name FROM classes WHERE deleted_at IS NULL ORDER BY numeric_no, name'
    )->fetch_all(MYSQLI_ASSOC);
    $subjects = $cc->conn->query(
        'SELECT id, name, code FROM subjects WHERE deleted_at IS NULL ORDER BY name'
    )->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'status' => true,
        'data' => [
            'classes' => $classes,
            'subjects' => $subjects
        ],
        'message' => 'Class and subject options loaded.'
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log('Exam subject marks options API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Unable to load classes and subjects.'
    ]);
}
?>
