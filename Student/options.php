<?php
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $sessionResult = $cc->conn->query(
        "SELECT id, name
         FROM sessions
         WHERE is_current = 1 AND deleted_at IS NULL
         ORDER BY id DESC
         LIMIT 1"
    );
    $session = $sessionResult->fetch_assoc();

    if (!$session) {
        http_response_code(409);
        echo json_encode([
            'status' => false,
            'data' => [],
            'message' => 'No current academic session is configured.'
        ]);
        exit;
    }

    $classes = [];
    $classResult = $cc->conn->query(
        "SELECT id, name
         FROM classes
         WHERE deleted_at IS NULL
         ORDER BY numeric_no, name"
    );
    while ($row = $classResult->fetch_assoc()) {
        $classes[] = $row;
    }

    $sections = [];
    $classId = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
    if ($classId) {
        $sectionStatement = $cc->conn->prepare(
            "SELECT id, name
             FROM sections
             WHERE class_id = ? AND deleted_at IS NULL
             ORDER BY name"
        );
        $sectionStatement->bind_param('i', $classId);
        $sectionStatement->execute();
        $sectionResult = $sectionStatement->get_result();
        while ($row = $sectionResult->fetch_assoc()) {
            $sections[] = $row;
        }
        $sectionStatement->close();
    }

    echo json_encode([
        'status' => true,
        'data' => [
            'session' => $session,
            'classes' => $classes,
            'sections' => $sections
        ],
        'message' => 'Student form options loaded.'
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log('Student options API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Unable to load student form options.'
    ]);
}
?>
