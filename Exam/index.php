<?php
include '../connection.php';
$result = $cc->common_select('Exams');
echo json_encode($result);