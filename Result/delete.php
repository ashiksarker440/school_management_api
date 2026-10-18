<?php
include '../connection.php';

$id = $_GET['id'] ?? '';

if (!ctype_digit((string) $id)) {
    echo json_encode(["message" => "Invalid id.", "status" => false]);
    exit;
}

$result = $cc->common_select('results', '*', ['id' => $id]);

if (!empty($result['data'])) {
    $res = $cc->common_delete('results', ['id' => $id]);
} else {
    $res = ["message" => "Result not found.", "status" => false];
}

echo json_encode($res);