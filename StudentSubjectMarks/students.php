<?php
include '../connection.php';
include '../auth_check.php';
require_once 'common.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$filters = student_marks_filters($_GET);

try {
    $markConfig = student_marks_validate_filters($cc->conn, $filters);
    $statement = $cc->conn->prepare(
        "SELECT s.id AS student_id, s.admission_number, s.name, se.roll_no,
                saved.id AS mark_id, saved.obtained_marks, saved.is_pass,
                saved.grade, saved.gpa_point
         FROM student_enrollments se
         INNER JOIN students s ON s.id = se.student_id AND s.deleted_at IS NULL
         LEFT JOIN student_subject_marks saved
           ON saved.student_id = s.id
          AND saved.exam_id = ?
          AND saved.subject_id = ?
         WHERE se.session_id = ? AND se.class_id = ? AND se.section_id = ?
           AND se.deleted_at IS NULL
         ORDER BY se.roll_no, s.name"
    );
    $statement->bind_param(
        'iiiii',
        $filters['exam_id'],
        $filters['subject_id'],
        $filters['session_id'],
        $filters['class_id'],
        $filters['section_id']
    );
    $statement->execute();
    $students = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    student_marks_respond(true, [
        'filters' => $filters,
        'full_marks' => $markConfig['full_marks'],
        'pass_marks' => $markConfig['pass_marks'],
        'students' => $students
    ], count($students) ? 'Enrolled students loaded.' : 'No students are enrolled for the selected filters.');
} catch (mysqli_sql_exception $exception) {
    error_log('Student subject marks roster API error: ' . $exception->getMessage());
    student_marks_respond(false, [], 'Unable to load students for these filters.', 500);
}
?>
