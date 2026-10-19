<?php
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Invalid student ID.'
    ]);
    exit;
}

$statement = $cc->conn->prepare(
    "SELECT s.*,
            c.name AS class_id,
            sec.name AS section,
            se.class_id AS enrollment_class_id,
            se.section_id AS enrollment_section_id,
            se.session_id AS enrollment_session_id,
            se.roll_no
     FROM students s
     LEFT JOIN student_enrollments se
       ON se.student_id = s.id
      AND se.session_id = (
          SELECT id FROM sessions
          WHERE is_current = 1 AND deleted_at IS NULL
          ORDER BY id DESC LIMIT 1
      )
      AND se.deleted_at IS NULL
     LEFT JOIN classes c ON c.id = se.class_id AND c.deleted_at IS NULL
     LEFT JOIN sections sec ON sec.id = se.section_id AND sec.deleted_at IS NULL
     WHERE s.id = ? AND s.deleted_at IS NULL
     ORDER BY se.id DESC
     LIMIT 1"
);
$statement->bind_param('i', $id);
$statement->execute();
$student = $statement->get_result()->fetch_assoc();
$statement->close();

if (!$student) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Student not found.'
    ]);
    exit;
}

echo json_encode([
    'status' => true,
    'data' => [$student],
    'message' => 'Student record found.'
]);
?>
