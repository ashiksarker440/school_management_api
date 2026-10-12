<?php
include '../connection.php';
$id = $_GET['id'];
$teachers = $cc->common_select('teachers', '*', ['id' => $id]);
if ($teachers['data'] > 0) {
    /* delete teachers image */
    $imagePath = $teachers['data'][0]->photo;
    if (!empty($imagePath) && file_exists('../' . $imagePath)) {
        unlink('../' . $imagePath);
    }
    $user_id = $teachers['data'][0]->user_id;
    $res = $cc->common_delete('users', ['id' => $user_id]);
    $res = $cc->common_delete('teachers', ['id' => $id]);
} else {
    $res = array("message" => "teachers not found.", 'status' => false);
}

echo json_encode($res);