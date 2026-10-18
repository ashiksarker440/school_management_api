<?php
include '../connection.php';
$result = $cc->common_select('results');
echo json_encode($result);