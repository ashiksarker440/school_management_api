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
    $exams = $cc->conn->query(
        'SELECT id, exam_name FROM exams WHERE deleted_at IS NULL ORDER BY id DESC'
    )->fetch_all(MYSQLI_ASSOC);

    $sections = [];
    $students = [];
    $classId = result_sheet_id($_GET['class_id'] ?? null);
    $sessionId = result_sheet_id($_GET['session_id'] ?? null);
    $sectionId = result_sheet_id($_GET['section_id'] ?? null);

    if ($classId) {
        $statement = $cc->conn->prepare(
            'SELECT id, name FROM sections WHERE class_id = ? AND deleted_at IS NULL ORDER BY name'
        );
        $statement->bind_param('i', $classId);
        $statement->execute();
        $sections = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
    }

    if ($classId && $sessionId && $sectionId) {
        $statement = $cc->conn->prepare(
            "SELECT s.id, s.name, s.admission_number, se.roll_no
             FROM student_enrollments se
             INNER JOIN students s ON s.id = se.student_id AND s.deleted_at IS NULL
             INNER JOIN sections sec ON sec.id = se.section_id
               AND sec.class_id = se.class_id AND sec.deleted_at IS NULL
             WHERE se.session_id = ? AND se.class_id = ? AND se.section_id = ?
               AND se.deleted_at IS NULL
             ORDER BY se.roll_no, s.name"
        );
        $statement->bind_param('iii', $sessionId, $classId, $sectionId);
        $statement->execute();
        $students = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
    }

    result_sheet_respond(true, [
        'sessions' => $sessions,
        'classes' => $classes,
        'sections' => $sections,
        'students' => $students,
        'exams' => $exams
    ], 'Result sheet options loaded.');
} catch (mysqli_sql_exception $exception) {
    error_log('Result sheet options API error: ' . $exception->getMessage());
    result_sheet_respond(false, [], 'Unable to load result sheet options.', 500);
}
?>
