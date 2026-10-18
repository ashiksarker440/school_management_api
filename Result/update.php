<?php
include '../connection.php';

$data = $_POST;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || empty($data['admission_no']) || empty($data['name'])) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Invalid result id, or admission no/name missing.'
    ]);
    exit;
}

$existing = $cc->common_select('results', 'id', ['id' => $id]);
if (empty($existing['data'])) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Result not found.'
    ]);
    exit;
}

$result = [
    'admission_no' => $data['admission_no'],
    'name' => $data['name'],
    'total' => $data['total'] ?? 0,
    'percentage' => $data['percentage'] ?? 0,
    'gpa' => $data['gpa'] ?? 0,
    'grade' => $data['grade'] ?? '',
    'result' => $data['result'] ?? '', // Pass / Fail
    'merit' => $data['merit'] ?? 0,
];

$res = $cc->common_update('results', $result, ['id' => $id]);

if ($res['status'] !== true) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Result could not be updated.'
    ]);
    exit;
}

echo json_encode([
    'status' => true,
    'data' => $res['data'],
    'message' => 'Result updated successfully.'
]);
