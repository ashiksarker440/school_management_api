<?php
include '../connection.php';
include '../auth_check.php';

$res = [];

if (
    isset($_GET['id']) &&
    ctype_digit((string) $_GET['id']) &&
    isset($_POST['student_id']) &&
    !empty($_POST['student_id']) &&
    isset($_POST['date']) &&
    !empty($_POST['date']) &&
    isset($_POST['status']) &&
    !empty($_POST['status'])
) {
    $id = (int) $_GET['id'];

    // Form theke Present / Absent / Late ashe, database-e number rakhbo
    $statusMap = [
        'present' => 1,
        'absent'  => 0,
        'late'    => 2,
    ];

    $statusKey = strtolower(trim($_POST['status']));

    if (!array_key_exists($statusKey, $statusMap)) {
        echo json_encode([
            'status'  => false,
            'data'    => [],
            'message' => 'Invalid status'
        ]);
        exit;
    }

    // Record ache ki na, ar delete hoyeche ki na
    $existing = $cc->common_select('attendance', '*', ['id' => $id]);

    if (empty($existing['data'][0])) {
        echo json_encode([
            'status'  => false,
            'data'    => [],
            'message' => 'Attendance not found.'
        ]);
        exit;
    }

    $attendance = [
        'student_id'    => (int) $_POST['student_id'],
        'date'          => $_POST['date'],
        'status'        => $statusMap[$statusKey],
    ];

    $res = $cc->common_update('attendance', $attendance, ['id' => $id]);
} else {
    $res['status']  = false;
    $res['data']    = [];
    $res['message'] = 'Student, date and status are required';
}

echo json_encode($res);
?>