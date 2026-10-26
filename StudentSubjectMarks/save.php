<?php
include '../connection.php';
include '../auth_check.php';
require_once 'common.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$filters = student_marks_filters($_POST);
$records = json_decode($_POST['records'] ?? '', true);
$transactionStarted = false;

if (!is_array($records) || !$records) {
    student_marks_respond(false, [], 'Provide at least one student marks entry.', 400);
}

try {
    $markConfig = student_marks_validate_filters($cc->conn, $filters);
    $grades = student_marks_grades($cc->conn);
    $rosterStatement = $cc->conn->prepare(
        "SELECT s.id, s.name
         FROM student_enrollments se
         INNER JOIN students s ON s.id = se.student_id AND s.deleted_at IS NULL
         WHERE se.session_id = ? AND se.class_id = ? AND se.section_id = ?
           AND se.deleted_at IS NULL"
    );
    $rosterStatement->bind_param(
        'iii',
        $filters['session_id'],
        $filters['class_id'],
        $filters['section_id']
    );
    $rosterStatement->execute();
    $roster = [];
    foreach ($rosterStatement->get_result()->fetch_all(MYSQLI_ASSOC) as $student) {
        $roster[(int) $student['id']] = $student['name'];
    }
    $rosterStatement->close();

    $validated = [];
    $seen = [];
    foreach ($records as $record) {
        if (!is_array($record)) {
            student_marks_respond(false, [], 'One or more student marks entries are invalid.', 400);
        }
        $studentId = student_marks_positive_int($record['student_id'] ?? null);
        if (!$studentId || !array_key_exists($studentId, $roster)) {
            student_marks_respond(false, [], 'A submitted student is not enrolled in the selected session, class, and section.', 400);
        }
        if (isset($seen[$studentId])) {
            student_marks_respond(false, [], 'Duplicate student marks entries are not allowed.', 400);
        }
        $seen[$studentId] = true;

        $value = $record['obtained_marks'] ?? '';
        if ($value === '' || $value === null) {
            continue;
        }
        if (!is_numeric($value)) {
            student_marks_respond(false, [
                'student_id' => $studentId,
                'student_name' => $roster[$studentId]
            ], 'Obtained marks must be numeric for ' . $roster[$studentId] . '.', 400);
        }
        $obtainedMarks = (float) $value;
        if (!is_finite($obtainedMarks) || $obtainedMarks < 0 || $obtainedMarks > $markConfig['full_marks']) {
            student_marks_respond(false, [
                'student_id' => $studentId,
                'student_name' => $roster[$studentId]
            ], 'Marks for ' . $roster[$studentId] . ' must be between 0 and ' . $markConfig['full_marks'] . '.', 400);
        }
        $grade = student_marks_grade_for($grades, $obtainedMarks);
        if (!$grade) {
            student_marks_respond(false, [
                'student_id' => $studentId,
                'student_name' => $roster[$studentId]
            ], 'No configured grade range matches marks for ' . $roster[$studentId] . '.', 400);
        }
        $validated[] = [
            'student_id' => $studentId,
            'obtained_marks' => $obtainedMarks,
            'is_pass' => $obtainedMarks >= $markConfig['pass_marks'] ? 1 : 0,
            'grade' => $grade['grade'],
            'gpa_point' => (float) $grade['gpa_point']
        ];
    }

    if (!$validated) {
        student_marks_respond(true, ['inserted' => 0, 'updated' => 0, 'skipped' => count($records)], 'No marks entered; blank rows were skipped.');
    }

    $cc->conn->begin_transaction();
    $transactionStarted = true;
    $findStatement = $cc->conn->prepare(
        'SELECT id FROM student_subject_marks WHERE student_id = ? AND exam_id = ? AND subject_id = ? FOR UPDATE'
    );
    $saveStatement = $cc->conn->prepare(
        'INSERT INTO student_subject_marks
            (student_id, exam_id, subject_id, obtained_marks, is_pass, grade, gpa_point)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            obtained_marks = VALUES(obtained_marks),
            is_pass = VALUES(is_pass),
            grade = VALUES(grade),
            gpa_point = VALUES(gpa_point),
            updated_at = CURRENT_TIMESTAMP'
    );
    $inserted = 0;
    $updated = 0;
    foreach ($validated as $record) {
        $studentId = $record['student_id'];
        $examId = $filters['exam_id'];
        $subjectId = $filters['subject_id'];
        $findStatement->bind_param('iii', $studentId, $examId, $subjectId);
        $findStatement->execute();
        $exists = $findStatement->get_result()->num_rows > 0;

        $obtainedMarks = $record['obtained_marks'];
        $isPass = $record['is_pass'];
        $grade = $record['grade'];
        $gpaPoint = $record['gpa_point'];
        $saveStatement->bind_param(
            'iiidisd',
            $studentId,
            $examId,
            $subjectId,
            $obtainedMarks,
            $isPass,
            $grade,
            $gpaPoint
        );
        $saveStatement->execute();
        if ($exists) {
            $updated++;
        } else {
            $inserted++;
        }
    }
    $findStatement->close();
    $saveStatement->close();
    $cc->conn->commit();
    $transactionStarted = false;

    student_marks_respond(true, [
        'inserted' => $inserted,
        'updated' => $updated,
        'skipped' => count($records) - count($validated)
    ], 'Student marks saved successfully.');
} catch (mysqli_sql_exception $exception) {
    if ($transactionStarted) {
        $cc->conn->rollback();
    }
    error_log('Student subject marks save API error: ' . $exception->getMessage());
    student_marks_respond(false, [], 'Unable to save student marks. No changes were saved.', 500);
}
?>
