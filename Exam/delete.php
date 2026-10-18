<?php
include '../connection.php';

$id = $_GET['id'] ?? '';

if (!ctype_digit((string) $id)) {
    echo json_encode(["message" => "Invalid id.", "status" => false]);
    exit;
}

$exam = $cc->common_select('exams', '*', ['id' => $id]);

if (!empty($exam['data'])) {
    $res = $cc->common_delete('exams', ['id' => $id]);
} else {
    $res = ["message" => "Exam not found.", "status" => false];
}

echo json_encode($res);