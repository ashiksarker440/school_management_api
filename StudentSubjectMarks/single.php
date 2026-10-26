<?php
include '../connection.php';
include '../auth_check.php';
require_once 'common.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$id = student_marks_positive_int($_GET['id'] ?? null);
if (!$id) {
    student_marks_respond(false, [], 'Invalid student marks record ID.', 400);
}

try {
    $statement = $cc->conn->prepare(
        "SELECT marks.id, marks.student_id, s.name AS student_name, s.admission_number,
                marks.exam_id, e.exam_name, marks.subject_id, sub.name AS subject_name,
                marks.obtained_marks, marks.is_pass, marks.grade, marks.gpa_point,
                marks.created_at, marks.updated_at
         FROM student_subject_marks marks
         INNER JOIN students s ON s.id = marks.student_id
         INNER JOIN exams e ON e.id = marks.exam_id
         INNER JOIN subjects sub ON sub.id = marks.subject_id
         WHERE marks.id = ? LIMIT 1"
    );
    $statement->bind_param('i', $id);
    $statement->execute();
    $record = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!$record) {
        student_marks_respond(false, [], 'Student marks record not found.', 404);
    }
    student_marks_respond(true, $record, 'Student marks record loaded.');
} catch (mysqli_sql_exception $exception) {
    error_log('Student subject marks detail API error: ' . $exception->getMessage());
    student_marks_respond(false, [], 'Unable to load student marks record.', 500);
}
?>
