<?php
include '../connection.php';

$data = $_POST;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || empty($data['admission_no']) || empty($data['date'])) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Invalid attendance id, or date/admission no missing.'
    ]);
    exit;
}

$existing = $cc->common_select('attendance', 'id', ['id' => $id]);
if (empty($existing['data'])) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Attendance not found.'
    ]);
    exit;
}

$attendance = [
    'date' => $data['date'],
    'admission_no' => $data['admission_no'],
    'name' => $data['name'] ?? '',
    'class_section' => $data['class_section'] ?? '',
    'status' => $data['status'] ?? 1, // 1 = Present, 0 = Absent
];

$res = $cc->common_update('attendance', $attendance, ['id' => $id]);

if ($res['status'] !== true) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Attendance could not be updated.'
    ]);
    exit;
}

echo json_encode([
    'status' => true,
    'data' => $res['data'],
    'message' => 'Attendance updated successfully.'
]);
