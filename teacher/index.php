<?php
include '../connection.php';
$result = $cc->common_select('teachers');
echo json_encode($result);