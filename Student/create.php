<?php
include '../connection.php';

$data = $_POST;


$uploadDir = '../';
$image = "";
if (!empty($_FILES['files'])) {
    foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
        $name = rand() . basename($_FILES['files']['name'][$index]);
        $targetPath = 'images/student_images/' . $name;

        if (move_uploaded_file($tmpName, $uploadDir . $targetPath)) {
            $image = $targetPath;
        }
    }
}

$res = [];
if ($data['name']) {


    $student['admission_number'] = $data['admission_number'];
    $student['name'] = $data['name'];
    $student['email'] = $data['email'] ?? '';
    $student['phone'] = $data['phone'] ?? '';
    $student['class_id'] = $data['class_id'] ?? '';
    $student['section'] = $data['section'] ?? '';


    $student['guardian_name'] = $data['guardian_name'] ?? '';
    $student['guardian_relation'] = $data['guardian_relation'] ?? '';
    $student['guardian_phone'] = $data['guardian_phone'] ?? '';
    $student['address'] = $data['address'] ?? '';

    $student['photo'] = $image;
    $student['created_at'] = date('Y-m-d H:i:s');
    $student['status'] = 1;


    $user['email'] = $data['email'] ?? '';
    $user['name'] = $data['name'];
    $user['username'] = !empty($data['email']) ? $data['email'] : $data['admission_number'];
    $user['role'] = 'student';
    $user['is_active'] = 1;
    $user['created_at'] = date('Y-m-d H:i:s');
    $user['password'] = md5($data['password'] ?? '');

    $res = $cc->common_insert('users', $user);
    if ($res['status'] == true) {
        $student['user_id'] = $res['data'];
        $res = $cc->common_insert('students', $student);
    } else {
        $res = [
            "status" => false,
            "data" => [],
            "message" => "Data not saved"
        ];
    }

}

echo json_encode($res);