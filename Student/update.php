<?php
include '../connection.php';

$data = $_POST;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || empty($data['name'])) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Invalid student id or missing name.'
    ]);
    exit;
}

$uploadDir = '../';
$imagePath = '';

if (!empty($_FILES['files']['tmp_name'])) {
    $existingStudent = $cc->common_select('students', 'photo, user_id', ['id' => $id]);
    if (!empty($existingStudent['data'][0]->photo) && file_exists($uploadDir . $existingStudent['data'][0]->photo)) {
        unlink($uploadDir . $existingStudent['data'][0]->photo);
    }

    foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
        if (empty($_FILES['files']['name'][$index])) {
            continue;
        }

        $name = rand() . '_' . basename($_FILES['files']['name'][$index]);
        $targetPath = 'images/student_images/' . $name;

        if (move_uploaded_file($tmpName, $uploadDir . $targetPath)) {
            $imagePath = $targetPath;
        }
    }
}

$student = [
    'admission_number' => $data['admission_number'] ?? '',
    'name' => $data['name'],
    'email' => $data['email'] ?? '',
    'phone' => $data['phone'] ?? '',
    'class_id' => $data['class_id'] ?? '',
    'section' => $data['section'] ?? '',
    'guardian_name' => $data['guardian_name'] ?? '',
    'guardian_relation' => $data['guardian_relation'] ?? '',
    'guardian_phone' => $data['guardian_phone'] ?? '',
    'address' => $data['guardianAddress'] ?? $data['address'] ?? '',
    'status' => 1,
];

if (!empty($imagePath)) {
    $student['photo'] = $imagePath;
}

$userId = null;
$currentStudent = $cc->common_select('students', 'user_id', ['id' => $id]);
if (!empty($currentStudent['data'][0]->user_id)) {
    $userId = $currentStudent['data'][0]->user_id;
}

if ($userId) {
    $user = [
        'email' => $data['email'] ?? '',
        'name' => $data['name'],
        'username' => !empty($data['email']) ? $data['email'] : ($data['admission_number'] ?? ''),
        'role' => 'student',
        'is_active' => 1,
    ];

    if (!empty(trim((string)($data['password'] ?? '')))) {
        $user['password'] = md5($data['password']);
    }

    $userUpdate = $cc->common_update('users', $user, ['id' => $userId]);
    if ($userUpdate['status'] !== true) {
        echo json_encode([
            'status' => false,
            'data' => [],
            'message' => 'Student user could not be updated.'
        ]);
        exit;
    }
}

$res = $cc->common_update('students', $student, ['id' => $id]);

if ($res['status'] !== true) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Student data could not be updated.'
    ]);
    exit;
}

echo json_encode([
    'status' => true,
    'data' => $res['data'],
    'message' => 'Student updated successfully.'
]);