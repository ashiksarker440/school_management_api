<?php
include '../connection.php';
$data = $_POST;

$res = [];

if (!empty($data['admission_no']) && !empty($data['name'])) {

    $result['admission_no'] = $data['admission_no'];
    $result['name'] = $data['name'];
    $result['total'] = $data['total'];
    $result['percentage'] = $data['percentage'];
    $result['gpa'] = $data['gpa'];
    $result['grade'] = $data['grade'];
    $result['result'] = $data['result']; // Pass / Fail
    $result['merit'] = $data['merit'];
    $result['created_at'] = date('Y-m-d H:i:s');

    $res = $cc->common_insert('results', $result);
} else {
    $res = [
        "status" => false,
        "data" => [],
        "message" => "Admission no ar name dorkar"
    ];
}

echo json_encode($res);
