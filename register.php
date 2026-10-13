<?php
include 'connection.php';
$data = $_POST;

$data['password'] = md5($data['password']);
$data['role'] = 'admin';
unset($data['confirm_password']);
if ($data) {
	if ($data['name'] && $data['email'] && $data['password']) {

		$result = $cc->common_insert("users", $data);
		echo json_encode($result);
	} else {
		echo json_encode(array("message" => "Name, email and password are required.", 'status' => false, 'data' => []));
	}
}

