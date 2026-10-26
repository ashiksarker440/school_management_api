<?php
include '../connection.php';
include '../auth_check.php';
require_once 'common.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$id = student_marks_positive_int($_GET['id'] ?? null);
$filters = student_marks_filters($_POST);
$studentId = student_marks_positive_int($_POST['student_id'] ?? null);
$value = $_POST['obtained_marks'] ?? '';
$transactionStarted = false;

if (!$id || !$studentId || $value === '' || $value === null || !is_numeric($value)) {
    student_marks_respond(false, [], 'A valid record ID, student, filters, and obtained marks are required.', 400);
}

try {
    $config = student_marks_validate_filters($cc->conn, $filters);
    $grades = student_marks_grades($cc->conn);
    $obtainedMarks = (float) $value;
    if (!is_finite($obtainedMarks) || $obtainedMarks < 0 || $obtainedMarks > $config['full_marks']) {
        student_marks_respond(false, [], 'Obtained marks must be between 0 and ' . $config['full_marks'] . '.', 400);
    }
    $grade = student_marks_grade_for($grades, $obtainedMarks);
    if (!$grade) {
        student_marks_respond(false, [], 'No configured grade range matches the obtained marks.', 400);
    }

    $cc->conn->begin_transaction();
    $transactionStarted = true;
    $checkStatement = $cc->conn->prepare(
        "SELECT marks.id
         FROM student_subject_marks marks
         INNER JOIN student_enrollments se
           ON se.student_id = marks.student_id
          AND se.session_id = ? AND se.class_id = ? AND se.section_id = ?
          AND se.deleted_at IS NULL
         WHERE marks.id = ? AND marks.student_id = ?
           AND marks.exam_id = ? AND marks.subject_id = ?
         LIMIT 1 FOR UPDATE"
    );
    $checkStatement->bind_param(
        'iiiiiii',
        $filters['session_id'],
        $filters['class_id'],
        $filters['section_id'],
        $id,
        $studentId,
        $filters['exam_id'],
        $filters['subject_id']
    );
    $checkStatement->execute();
    $recordExists = $checkStatement->get_result()->num_rows > 0;
    $checkStatement->close();
    if (!$recordExists) {
        $cc->conn->rollback();
        $transactionStarted = false;
        student_marks_respond(false, [], 'Record not found or student is not enrolled in the selected filters.', 404);
    }

    $statement = $cc->conn->prepare(
        "UPDATE student_subject_marks
         SET obtained_marks = ?, is_pass = ?, grade = ?,
             gpa_point = ?, updated_at = CURRENT_TIMESTAMP
         WHERE id = ?"
    );
    $isPass = $obtainedMarks >= $config['pass_marks'] ? 1 : 0;
    $gpaPoint = (float) $grade['gpa_point'];
    $statement->bind_param(
        'disdi',
        $obtainedMarks,
        $isPass,
        $grade['grade'],
        $gpaPoint,
        $id
    );
    $statement->execute();
    $statement->close();
    $cc->conn->commit();
    $transactionStarted = false;
    student_marks_respond(true, ['id' => $id], 'Student marks updated successfully.');
} catch (mysqli_sql_exception $exception) {
    if ($transactionStarted) {
        $cc->conn->rollback();
    }
    error_log('Student subject marks update API error: ' . $exception->getMessage());
    student_marks_respond(false, [], 'Unable to update student marks.', 500);
}
?>
