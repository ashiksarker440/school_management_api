<?php
include '../connection.php';
$data = json_decode(file_get_contents('php://input'), true);
$res = [];
if ($data['name']) {

    $teacher['name'] = $data['name'];
    //$teacher['gender'] = $data['gender'];
    $teacher['phone'] = $data['phone'];
    $teacher['email'] = $data['email'];
    $teacher['address'] = $data['address'];
    $teacher['qualification'] = $data['qualification'];
    //$teacher['main_subject'] = $data['main_subject'];
    $teacher['salary'] = $data['salary'];
    $teacher['joining_date'] = $data['joining_date'];
    // $teacher['photo'] = $data['photo'];
    $teacher['created_at'] = date('Y-m-d H:i:s');
    $teacher['status'] = 1;

    $user['email'] = $data['email'];
    $user['name'] = $data['name'];
    $user['username'] = $data['email'];
    $user['role'] = 'teacher';
    $user['is_active'] = 1;
    $user['created_at'] = date('Y-m-d H:i:s');
    $user['password'] = md5($data['password']);

    $res = $cc->common_insert('users', $user);
    if ($res['status'] == true) {
        $teacher['user_id'] = $res['data'];
        $res = $cc->common_insert('teachers', $teacher);
    } else {
        $res = [
            "status" => false,
            "data" => [],
            "message" => "Data not saved"
        ];
    }


}
echo json_encode($res);
