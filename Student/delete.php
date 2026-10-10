<?php
include '../connection.php';
$id = $_GET['id'];
$student = $cc->common_select('students', '*', ['id' => $id]);
if($student['data'] > 0){
    /* delete student image */
    $imagePath = $student['data'][0]->photo;
    if (!empty($imagePath) && file_exists('../' . $imagePath)) {
        unlink('../' . $imagePath);
    }
    $user_id = $student['data'][0]->user_id;
    $res = $cc->common_delete('users', ['id' => $user_id]);
    $res = $cc->common_delete('students', ['id' => $id]);
}else{
    $res = array("message" => "Student not found.",'status'=>false);
}

echo json_encode($res);