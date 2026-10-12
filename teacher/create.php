<?php
include '../connection.php';
$data = $_POST;


$uploadDir = '../';
$image = "";
if (!empty($_FILES['files'])) {
    foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
        $name = rand() . basename($_FILES['files']['name'][$index]);
        $targetPath = 'images/teachers_images/' . $name;

        if (move_uploaded_file($tmpName, $uploadDir . $targetPath)) {
            $teachers['photo'] = $targetPath;
        }
    }
}

$res = [];
if ($data['name']) {

    $teachers['name'] = $data['name'];
    //$teachers['gender'] = $data['gender'];
    $teachers['phone'] = $data['phone'];
    $teachers['email'] = $data['email'];
    $teachers['address'] = $data['address'];
    $teachers['qualification'] = $data['qualification'];
    $teachers['main_subject'] = $data['main_subject'];
    $teachers['salary'] = $data['salary'];
    $teachers['joining_date'] = $data['joining_date'];
    $teachers['created_at'] = date('Y-m-d H:i:s');
    $teachers['status'] = 1;

    $user['email'] = $data['email'];
    $user['name'] = $data['name'];
    $user['username'] = $data['email'];
    $user['role'] = 'teacher';
    $user['is_active'] = 1;
    $user['created_at'] = date('Y-m-d H:i:s');
    $user['password'] = md5($data['password']);

    $res = $cc->common_insert('users', $user);
    if ($res['status'] == true) {
        $teachers['user_id'] = $res['data'];
        $res = $cc->common_insert('teachers', $teachers);
    } else {
        $res = [
            "status" => false,
            "data" => [],
            "message" => "Data not saved"
        ];
    }
}
echo json_encode($res);
