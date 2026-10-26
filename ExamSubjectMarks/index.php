<?php
include '../connection.php';

header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $result = $cc->conn->query(
        "SELECT esm.id, esm.exam_id, e.exam_name, esm.class_id, c.name AS class_name,
                esm.subject_id, s.name AS subject_name,
                esm.full_marks, esm.pass_marks, esm.exam_date, esm.deleted_at
         FROM exam_subject_marks esm
         LEFT JOIN exams e ON e.id = esm.exam_id AND e.deleted_at IS NULL
         LEFT JOIN classes c ON c.id = esm.class_id AND c.deleted_at IS NULL
         LEFT JOIN subjects s ON s.id = esm.subject_id AND s.deleted_at IS NULL
         WHERE esm.deleted_at IS NULL
         ORDER BY esm.exam_date DESC, esm.id DESC"
    );
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'status' => true,
        'data' => $rows,
        'message' => count($rows) ? 'Records found' : 'No records found'
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log('Exam subject marks list API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Unable to load exam subject marks.'
    ]);
}
?>
