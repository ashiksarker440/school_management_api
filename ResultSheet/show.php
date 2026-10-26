<?php
include '../connection.php';
include '../auth_check.php';
require_once 'common.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$sessionId = result_sheet_id($_GET['session_id'] ?? null);
$classId = result_sheet_id($_GET['class_id'] ?? null);
$sectionId = result_sheet_id($_GET['section_id'] ?? null);
$examId = result_sheet_id($_GET['exam_id'] ?? null);
$studentId = result_sheet_id($_GET['student_id'] ?? null);

if (!$sessionId || !$classId || !$sectionId || !$examId || !$studentId) {
    result_sheet_respond(false, [], 'Session, class, section, exam, and student are required.', 400);
}

try {
    $enrollmentStatement = $cc->conn->prepare(
        "SELECT s.id AS student_id, s.name AS student_name, s.admission_number,
                se.roll_no, c.name AS class_name, sec.name AS section_name,
                ses.name AS session_name, e.exam_name
         FROM student_enrollments se
         INNER JOIN students s ON s.id = se.student_id AND s.deleted_at IS NULL
         INNER JOIN classes c ON c.id = se.class_id AND c.deleted_at IS NULL
         INNER JOIN sections sec ON sec.id = se.section_id AND sec.class_id = se.class_id
           AND sec.deleted_at IS NULL
         INNER JOIN sessions ses ON ses.id = se.session_id AND ses.deleted_at IS NULL
         INNER JOIN exams e ON e.id = ? AND e.deleted_at IS NULL
         WHERE se.session_id = ? AND se.class_id = ? AND se.section_id = ?
           AND se.student_id = ? AND se.deleted_at IS NULL
         LIMIT 1"
    );
    $enrollmentStatement->bind_param('iiiii', $examId, $sessionId, $classId, $sectionId, $studentId);
    $enrollmentStatement->execute();
    $student = $enrollmentStatement->get_result()->fetch_assoc();
    $enrollmentStatement->close();

    if (!$student) {
        result_sheet_respond(false, [], 'The selected student is not enrolled in the selected session, class, and section, or the exam is unavailable.', 404);
    }

    $subjectStatement = $cc->conn->prepare(
        "SELECT sub.id AS subject_id, sub.name AS subject_name, sub.code AS subject_code,
                esm.full_marks, esm.pass_marks, marks.obtained_marks, marks.is_pass,
                marks.grade, marks.gpa_point
         FROM exam_subject_marks esm
         INNER JOIN subjects sub ON sub.id = esm.subject_id AND sub.deleted_at IS NULL
         LEFT JOIN student_subject_marks marks
           ON marks.student_id = ? AND marks.exam_id = esm.exam_id
          AND marks.subject_id = esm.subject_id
         WHERE esm.exam_id = ? AND esm.class_id = ? AND esm.deleted_at IS NULL
         ORDER BY sub.name"
    );
    $subjectStatement->bind_param('iii', $studentId, $examId, $classId);
    $subjectStatement->execute();
    $subjects = $subjectStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $subjectStatement->close();
    if (!$subjects) {
        result_sheet_respond(false, [], 'No subjects are configured for this exam and class.', 404);
    }

    $gradeScales = $cc->conn->query(
        'SELECT grade, min_mark, max_mark, gpa_point
         FROM grade_scales WHERE deleted_at IS NULL ORDER BY min_mark, max_mark'
    )->fetch_all(MYSQLI_ASSOC);
    if (!$gradeScales) {
        result_sheet_respond(false, [], 'No active grade scale is configured.', 409);
    }

    $totalObtained = 0.0;
    $totalFullMarks = 0.0;
    $gpaTotal = 0.0;
    $enteredCount = 0;
    $allPassed = true;
    foreach ($subjects as &$subject) {
        $fullMarks = (float) $subject['full_marks'];
        $passMarks = (float) $subject['pass_marks'];
        $totalFullMarks += $fullMarks;
        if ($subject['obtained_marks'] === null) {
            $subject['result_status'] = 'Not entered';
            $subject['is_pass'] = null;
            continue;
        }
        $obtained = (float) $subject['obtained_marks'];
        $totalObtained += $obtained;
        $gpaTotal += (float) $subject['gpa_point'];
        $enteredCount++;
        $subject['result_status'] = (int) $subject['is_pass'] === 1 ? 'Pass' : 'Fail';
        if ((int) $subject['is_pass'] !== 1 || $obtained < $passMarks) {
            $allPassed = false;
        }
    }
    unset($subject);

    $complete = $enteredCount === count($subjects);
    $percentage = $totalFullMarks > 0 ? ($totalObtained / $totalFullMarks) * 100 : 0;
    $overallGrade = null;
    if ($complete) {
        foreach ($gradeScales as $scale) {
            if ($percentage >= (float) $scale['min_mark'] && $percentage <= (float) $scale['max_mark']) {
                $overallGrade = $scale['grade'];
                break;
            }
        }
    }

    $student['total_obtained'] = round($totalObtained, 2);
    $student['total_full_marks'] = round($totalFullMarks, 2);
    $student['percentage'] = round($percentage, 2);
    $student['overall_gpa'] = $complete && $enteredCount > 0
        ? round($gpaTotal / $enteredCount, 2)
        : null;
    $student['overall_grade'] = $overallGrade;
    $student['overall_result'] = !$complete
        ? 'Incomplete'
        : ($allPassed ? 'Pass' : 'Fail');

    result_sheet_respond(true, [
        'student' => $student,
        'subjects' => $subjects,
        'grade_scales' => $gradeScales
    ], 'Result sheet loaded.');
} catch (mysqli_sql_exception $exception) {
    error_log('Result sheet API error: ' . $exception->getMessage());
    result_sheet_respond(false, [], 'Unable to load the student result sheet.', 500);
}
?>
