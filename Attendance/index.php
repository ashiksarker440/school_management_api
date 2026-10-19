<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$sql = "
    SELECT a.*,
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
    WHERE a.deleted_at IS NULL
    ORDER BY a.date DESC, a.id DESC
";

$result = $cc->conn->query($sql);
$rows = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'status' => true,
    'data' => $rows,
    'message' => count($rows) ? 'Records found' : 'No records found'
]);
?>
