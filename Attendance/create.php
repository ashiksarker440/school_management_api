<?php
include '../connection.php';
$data = $_POST;

$res = [];

if (!empty($data['admission_no']) && !empty($data['date'])) {

    $attendance['date'] = $data['date'];
    $attendance['admission_no'] = $data['admission_no'];
    $attendance['name'] = $data['name'];
    $attendance['class_section'] = $data['class_section'];
    $attendance['status'] = $data['status']; // jemon: 1 = Present, 0 = Absent
    $attendance['created_at'] = date('Y-m-d H:i:s');

    $res = $cc->common_insert('attendance', $attendance);
} else {
    $res = [
        "status" => false,
        "data" => [],
        "message" => "Date ar admission no dorkar"
    ];
}

echo json_encode($res);
