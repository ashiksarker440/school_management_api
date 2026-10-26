<?php
include '../connection.php';
include '../auth_check.php';
require_once 'common.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $sessions = $cc->conn->query(
        'SELECT id, name, is_current FROM sessions WHERE deleted_at IS NULL ORDER BY id DESC'
    )->fetch_all(MYSQLI_ASSOC);
    $classes = $cc->conn->query(
        'SELECT id, name FROM classes WHERE deleted_at IS NULL ORDER BY numeric_no, name'
    )->fetch_all(MYSQLI_ASSOC);
    $subjects = $cc->conn->query(
        'SELECT id, name, code FROM subjects WHERE deleted_at IS NULL ORDER BY name'
    )->fetch_all(MYSQLI_ASSOC);
    $exams = $cc->conn->query(
        'SELECT id, exam_name FROM exams WHERE deleted_at IS NULL ORDER BY id DESC'
    )->fetch_all(MYSQLI_ASSOC);

    $sections = [];
    $classId = student_marks_positive_int($_GET['class_id'] ?? null);
    if ($classId) {
        $statement = $cc->conn->prepare(
            'SELECT id, name FROM sections WHERE class_id = ? AND deleted_at IS NULL ORDER BY name'
        );
        $statement->bind_param('i', $classId);
        $statement->execute();
        $sections = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
    }

    student_marks_respond(true, [
        'sessions' => $sessions,
        'classes' => $classes,
        'sections' => $sections,
        'subjects' => $subjects,
        'exams' => $exams
    ], 'Bulk marks options loaded.');
} catch (mysqli_sql_exception $exception) {
    error_log('Student subject marks options API error: ' . $exception->getMessage());
    student_marks_respond(false, [], 'Unable to load bulk marks options.', 500);
}
?>
