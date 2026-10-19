<?php
include '../connection.php';
include '../auth_check.php';

$res = [];

if (
    isset($_POST['student_id'], $_POST['date'], $_POST['status']) &&
    !empty($_POST['student_id']) &&
    !empty($_POST['date']) &&
    !empty($_POST['status'])
) {
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

    $attendance = [];
    $attendance['student_id']    = (int) $_POST['student_id'];
    $attendance['date']          = $_POST['date'];
    $attendance['status']        = $statusMap[$statusKey];
    $attendance['created_at']    = date('Y-m-d H:i:s');

    $res = $cc->common_insert('attendance', $attendance);
} else {
    $res['status']  = false;
    $res['data']    = [];
    $res['message'] = 'Student, date and status are required';
}

echo json_encode($res);
?>