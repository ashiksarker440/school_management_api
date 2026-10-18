<?php
include '../connection.php';
$data = $_POST;

$res = [];

if (!empty($data['exam_name']) && !empty($data['date'])) {

    $exam['exam_name'] = $data['exam_name'];
    $exam['type'] = $data['type'];
    $exam['class_id'] = $data['class_id'];
    $exam['subject'] = $data['subject'];
    $exam['date'] = $data['date'];
    $exam['time'] = $data['time'];
    $exam['marks'] = $data['marks'];
    $exam['status'] = $data['status'] ?? 1;
    $exam['created_at'] = date('Y-m-d H:i:s');

    $res = $cc->common_insert('exams', $exam);
} else {
    $res = [
        "status" => false,
        "data" => [],
        "message" => "Exam name ar date dorkar"
    ];
}

echo json_encode($res);
