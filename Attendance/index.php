<?php
include '../connection.php';
$result = $cc->common_select('attendance');
echo json_encode($result);