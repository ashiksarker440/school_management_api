<?php
include '../connection.php';
include '../auth_check.php';
require_once 'common.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $result = $cc->conn->query(
        "SELECT marks.id, marks.student_id, s.name AS student_name, s.admission_number,
                marks.exam_id, e.exam_name, marks.subject_id, sub.name AS subject_name,
                esm.class_id, c.name AS class_name, marks.obtained_marks,
                marks.is_pass, marks.grade, marks.gpa_point, marks.created_at, marks.updated_at
         FROM student_subject_marks marks
         INNER JOIN students s ON s.id = marks.student_id
         INNER JOIN exams e ON e.id = marks.exam_id
         INNER JOIN subjects sub ON sub.id = marks.subject_id
         LEFT JOIN exam_subject_marks esm
           ON esm.exam_id = marks.exam_id AND esm.subject_id = marks.subject_id
          AND esm.deleted_at IS NULL
         LEFT JOIN classes c ON c.id = esm.class_id AND c.deleted_at IS NULL
         ORDER BY marks.updated_at DESC, marks.id DESC"
    );
    student_marks_respond(true, $result->fetch_all(MYSQLI_ASSOC), 'Student subject marks loaded.');
} catch (mysqli_sql_exception $exception) {
    error_log('Student subject marks list API error: ' . $exception->getMessage());
    student_marks_respond(false, [], 'Unable to load student subject marks.', 500);
}
?>
