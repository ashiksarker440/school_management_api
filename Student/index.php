<?php
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$result = $cc->conn->query(
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
     WHERE s.deleted_at IS NULL
     ORDER BY s.id DESC"
);

echo json_encode([
    'status' => true,
    'data' => $result->fetch_all(MYSQLI_ASSOC),
    'message' => 'Student records loaded.'
]);
?>
