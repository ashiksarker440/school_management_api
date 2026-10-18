<?php
include '../connection.php';

$data = $_POST;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || empty($data['exam_name']) || empty($data['date'])) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Invalid exam id, or exam name/date missing.'
    ]);
    exit;
}

$existing = $cc->common_select('exams', 'id', ['id' => $id]);
if (empty($existing['data'])) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Exam not found.'
    ]);
    exit;
}

$exam = [
    'exam_name' => $data['exam_name'],
    'type' => $data['type'] ?? '',
    'class_id' => $data['class_id'] ?? '',
    'subject' => $data['subject'] ?? '',
    'date' => $data['date'],
    'time' => $data['time'] ?? '',
    'marks' => $data['marks'] ?? 0,
    'status' => $data['status'] ?? 1,
];

$res = $cc->common_update('exams', $exam, ['id' => $id]);

if ($res['status'] !== true) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Exam could not be updated.'
    ]);
    exit;
}

echo json_encode([
    'status' => true,
    'data' => $res['data'],
    'message' => 'Exam updated successfully.'
]);
