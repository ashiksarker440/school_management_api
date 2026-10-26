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
    $statement = $cc->conn->prepare('DELETE FROM student_subject_marks WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $deleted = $statement->affected_rows;
    $statement->close();
    if (!$deleted) {
        student_marks_respond(false, [], 'Student marks record not found.', 404);
    }
    student_marks_respond(true, ['id' => $id], 'Student marks deleted successfully.');
} catch (mysqli_sql_exception $exception) {
    error_log('Student subject marks delete API error: ' . $exception->getMessage());
    student_marks_respond(false, [], 'Unable to delete student marks.', 500);
}
?>
