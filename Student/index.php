<?php
include '../connection.php';
$result = $cc->common_select('students');
echo json_encode($result);