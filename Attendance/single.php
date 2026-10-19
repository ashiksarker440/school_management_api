<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Invalid attendance ID.'
    ]);
    exit;
}

$statement = $cc->conn->prepare(
    "SELECT a.*,
            s.admission_number AS admission_no,
            s.name,
            COALESCE(se.roll_no, '') AS roll_no,
            CASE
                WHEN se.id IS NULL THEN ''
                ELSE CONCAT(se.class_id, ' - ', se.section_id)
            END AS class_section
     FROM attendance a
     LEFT JOIN students s ON s.id = a.student_id AND s.deleted_at IS NULL
     LEFT JOIN (
         SELECT student_id, MAX(id) AS id
         FROM student_enrollments
         WHERE deleted_at IS NULL
         GROUP BY student_id
     ) latest_enrollment ON latest_enrollment.student_id = a.student_id
     LEFT JOIN student_enrollments se ON se.id = latest_enrollment.id
     WHERE a.id = ? AND a.deleted_at IS NULL
     LIMIT 1"
);
$statement->bind_param('i', $id);
$statement->execute();
$row = $statement->get_result()->fetch_assoc();
$statement->close();

if (!$row) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Attendance not found.'
    ]);
    exit;
}

echo json_encode([
    'status' => true,
    'data' => [$row],
    'message' => 'Record found'
]);
?>
