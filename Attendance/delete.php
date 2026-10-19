<?php
include '../connection.php';
include '../auth_check.php';

$res = [];

if (isset($_GET['id']) && ctype_digit((string) $_GET['id'])) {
    $id = (int) $_GET['id'];

    // Already delete hoye gele ba record na thakle
    $attendance = $cc->common_select('attendance', '*', [
        'id' => $id
    ]);

    if (!empty($attendance['data'])) {
        $res = $cc->common_update('attendance', [
            'deleted_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $id
        ]);
    } else {
        $res['status'] = false;
        $res['message'] = 'Attendance not found.';
    }
} else {
    $res['status'] = false;
    $res['message'] = 'Invalid attendance ID';
}

echo json_encode($res);
?>