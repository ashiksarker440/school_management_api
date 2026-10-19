<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function report_response($status, $data, $message, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

function report_integer($value, $min, $max)
{
    $number = filter_var($value, FILTER_VALIDATE_INT);
    return $number !== false && $number >= $min && $number <= $max ? $number : false;
}

try {
    $type = $_GET['type'] ?? '';
    $classId = trim((string) ($_GET['class_id'] ?? ''));
    $sectionId = trim((string) ($_GET['section_id'] ?? ''));

    if ($type === '') {
        $data = [
            'classes' => [],
            'sections' => [],
            'students' => [],
            'years' => [date('Y')]
        ];

        $yearResult = $cc->conn->query(
            "SELECT DISTINCT YEAR(date) AS year
             FROM attendance
             WHERE deleted_at IS NULL
             ORDER BY year DESC"
        );
        while ($row = $yearResult->fetch_assoc()) {
            $data['years'][] = (string) $row['year'];
        }
        $data['years'] = array_values(array_unique($data['years']));

        $classResult = $cc->conn->query(
            "SELECT DISTINCT se.class_id, c.name AS class_name
             FROM student_enrollments se
             INNER JOIN students s ON s.id = se.student_id
             INNER JOIN classes c ON c.id = se.class_id
             WHERE se.deleted_at IS NULL AND s.deleted_at IS NULL
             ORDER BY c.name"
        );
        while ($row = $classResult->fetch_assoc()) {
            $data['classes'][] = [
                'class_id' => $row['class_id'],
                'class_name' => $row['class_name']
            ];
        }

        if ($classId !== '') {
            $sectionStatement = $cc->conn->prepare(
                "SELECT DISTINCT se.section_id, sections.name AS section_name
                 FROM student_enrollments se
                 INNER JOIN students s ON s.id = se.student_id
                 INNER JOIN sections ON sections.id = se.section_id
                 WHERE se.class_id = ? AND se.deleted_at IS NULL AND s.deleted_at IS NULL
                 ORDER BY sections.name"
            );
            $sectionStatement->bind_param('s', $classId);
            $sectionStatement->execute();
            $sectionResult = $sectionStatement->get_result();
            while ($row = $sectionResult->fetch_assoc()) {
                $data['sections'][] = [
                    'section_id' => $row['section_id'],
                    'section_name' => $row['section_name']
                ];
            }
            $sectionStatement->close();
        }

        if ($classId !== '' && $sectionId !== '') {
            $studentStatement = $cc->conn->prepare(
                "SELECT DISTINCT s.id, s.name, s.admission_number
                 FROM student_enrollments se
                 INNER JOIN students s ON s.id = se.student_id
                 WHERE se.class_id = ? AND se.section_id = ?
                   AND se.deleted_at IS NULL AND s.deleted_at IS NULL
                 ORDER BY s.name"
            );
            $studentStatement->bind_param('ss', $classId, $sectionId);
            $studentStatement->execute();
            $studentResult = $studentStatement->get_result();
            while ($row = $studentResult->fetch_assoc()) {
                $data['students'][] = $row;
            }
            $studentStatement->close();
        }

        report_response(true, $data, 'Report options loaded.');
    }

    $year = report_integer($_GET['year'] ?? null, 1900, 2200);
    $month = report_integer($_GET['month'] ?? null, 1, 12);
    if ($year === false || $month === false || $classId === '') {
        report_response(false, [], 'A valid year, month and class are required.', 400);
    }

    $periodStart = sprintf('%04d-%02d-01', $year, $month);
    $periodEnd = (new DateTimeImmutable($periodStart))->modify('+1 month')->format('Y-m-d');

    if ($type === 'student') {
        $studentId = report_integer($_GET['student_id'] ?? null, 1, PHP_INT_MAX);
        if ($studentId === false || $sectionId === '') {
            report_response(false, [], 'A valid section and student are required.', 400);
        }

        $statement = $cc->conn->prepare(
            "SELECT a.date, a.status
             FROM attendance a
             INNER JOIN (
                 SELECT date, MAX(id) AS id
                 FROM attendance
                 WHERE student_id = ? AND date >= ? AND date < ? AND deleted_at IS NULL
                 GROUP BY date
             ) latest ON latest.id = a.id
             WHERE EXISTS (
                 SELECT 1
                 FROM student_enrollments se
                 WHERE se.student_id = a.student_id
                   AND se.class_id = ? AND se.section_id = ? AND se.deleted_at IS NULL
             )
             ORDER BY a.date"
        );
        $statement->bind_param('issss', $studentId, $periodStart, $periodEnd, $classId, $sectionId);
        $statement->execute();
        $result = $statement->get_result();
        $attendance = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        report_response(true, ['attendance' => $attendance], 'Student attendance report loaded.');
    }

    if ($type === 'class') {
        $statement = $cc->conn->prepare(
            "SELECT a.date,
                    SUM(CASE WHEN a.status = 1 THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN a.status = 0 THEN 1 ELSE 0 END) AS absent,
                    SUM(CASE WHEN a.status = 2 THEN 1 ELSE 0 END) AS late
             FROM attendance a
             INNER JOIN (
                 SELECT date, student_id, MAX(id) AS id
                 FROM attendance
                 WHERE date >= ? AND date < ? AND deleted_at IS NULL
                 GROUP BY date, student_id
             ) latest ON latest.id = a.id
             INNER JOIN (
                 SELECT se.student_id, MAX(se.id) AS id
                 FROM student_enrollments se
                 INNER JOIN students s ON s.id = se.student_id AND s.deleted_at IS NULL
                 WHERE se.deleted_at IS NULL
                 GROUP BY se.student_id
             ) latest_enrollment ON latest_enrollment.student_id = a.student_id
             INNER JOIN student_enrollments se ON se.id = latest_enrollment.id
             WHERE se.class_id = ?
             GROUP BY a.date
             ORDER BY a.date"
        );
        $statement->bind_param('sss', $periodStart, $periodEnd, $classId);
        $statement->execute();
        $result = $statement->get_result();
        $attendance = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        report_response(true, ['attendance' => $attendance], 'Class attendance report loaded.');
    }

    report_response(false, [], 'Invalid report type.', 400);
} catch (mysqli_sql_exception $exception) {
    error_log('Attendance report API error: ' . $exception->getMessage());
    report_response(false, [], 'Unable to load attendance report. Please check the database configuration.', 500);
}
?>
