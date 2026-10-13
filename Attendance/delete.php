<?php
include '../connection.php';

$id = $_GET['id'] ?? '';

if (!ctype_digit((string) $id)) {
    echo json_encode(["message" => "Invalid id.", "status" => false]);
    exit;
}

$attendance = $cc->common_select('attendance', '*', ['id' => $id]);

if (!empty($attendance['data'])) {
    $res = $cc->common_delete('attendance', ['id' => $id]);
} else {
    $res = ["message" => "Attendance not found.", "status" => false];
}

echo json_encode($res);
