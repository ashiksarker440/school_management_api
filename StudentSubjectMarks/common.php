<?php
function student_marks_respond($status, $data, $message, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

function student_marks_positive_int($value)
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    return $id !== false && $id > 0 ? $id : null;
}

function student_marks_filters($source)
{
    return [
        'session_id' => student_marks_positive_int($source['session_id'] ?? null),
        'class_id' => student_marks_positive_int($source['class_id'] ?? null),
        'section_id' => student_marks_positive_int($source['section_id'] ?? null),
        'subject_id' => student_marks_positive_int($source['subject_id'] ?? null),
        'exam_id' => student_marks_positive_int($source['exam_id'] ?? null)
    ];
}

function student_marks_validate_filters($conn, $filters)
{
    foreach ($filters as $value) {
        if (!$value) {
            student_marks_respond(false, [], 'Session, class, section, subject, and exam are required.', 400);
        }
    }

    $statement = $conn->prepare(
        "SELECT esm.full_marks, esm.pass_marks
         FROM exam_subject_marks esm
         INNER JOIN exams e ON e.id = esm.exam_id AND e.deleted_at IS NULL
         INNER JOIN classes c ON c.id = esm.class_id AND c.deleted_at IS NULL
         INNER JOIN subjects sub ON sub.id = esm.subject_id AND sub.deleted_at IS NULL
         INNER JOIN sessions ses ON ses.id = ? AND ses.deleted_at IS NULL
         INNER JOIN sections sec ON sec.id = ? AND sec.class_id = esm.class_id AND sec.deleted_at IS NULL
         WHERE esm.exam_id = ? AND esm.class_id = ? AND esm.subject_id = ?
           AND esm.deleted_at IS NULL
         LIMIT 1"
    );
    $statement->bind_param(
        'iiiii',
        $filters['session_id'],
        $filters['section_id'],
        $filters['exam_id'],
        $filters['class_id'],
        $filters['subject_id']
    );
    $statement->execute();
    $marks = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$marks) {
        student_marks_respond(false, [], 'The selected session, class, section, exam, and subject combination is invalid. Confirm that the subject is configured for this exam and class.', 400);
    }

    $fullMarks = (float) $marks['full_marks'];
    $passMarks = (float) $marks['pass_marks'];
    if ($fullMarks <= 0 || $passMarks < 0 || $passMarks > $fullMarks) {
        student_marks_respond(false, [], 'The configured full marks or pass marks are invalid.', 409);
    }

    return [
        'full_marks' => $fullMarks,
        'pass_marks' => $passMarks
    ];
}

function student_marks_grades($conn)
{
    $result = $conn->query(
        'SELECT grade, min_mark, max_mark, gpa_point
         FROM grade_scales
         WHERE deleted_at IS NULL
         ORDER BY min_mark, max_mark'
    );
    $grades = $result->fetch_all(MYSQLI_ASSOC);
    if (!$grades) {
        student_marks_respond(false, [], 'No active grade scale is configured.', 409);
    }

    $previousMax = null;
    foreach ($grades as $grade) {
        $minimum = (float) $grade['min_mark'];
        $maximum = (float) $grade['max_mark'];
        if ($minimum > $maximum || ($previousMax !== null && $minimum <= $previousMax)) {
            student_marks_respond(false, [], 'The configured grade scale contains invalid or overlapping ranges.', 409);
        }
        $previousMax = $maximum;
    }

    return $grades;
}

function student_marks_grade_for($grades, $obtainedMarks)
{
    foreach ($grades as $grade) {
        if ($obtainedMarks >= (float) $grade['min_mark'] && $obtainedMarks <= (float) $grade['max_mark']) {
            return $grade;
        }
    }
    return null;
}
?>
