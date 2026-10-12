<?php
include '../connection.php';

$data = $_POST;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || empty($data['name'])) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Invalid teachers id or missing name.'
    ]);
    exit;
}

$uploadDir = '../';
$imagePath = '';

if (!empty($_FILES['files']['tmp_name'])) {
    $existingTeachers = $cc->common_select('Teachers', 'photo, user_id', ['id' => $id]);
    if (!empty($existingTeachers['data'][0]->photo) && file_exists($uploadDir . $existingTeachers['data'][0]->photo)) {
        unlink($uploadDir . $existingTeachers['data'][0]->photo);
    }

    foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
        if (empty($_FILES['files']['name'][$index])) {
            continue;
        }

        $name = rand() . '_' . basename($_FILES['files']['name'][$index]);
        $targetPath = 'images/Teachers_images/' . $name;

        if (move_uploaded_file($tmpName, $uploadDir . $targetPath)) {
            $imagePath = $targetPath;
        }
    }
}

$teacher = [
    'email' => $data['email'] ?? '',
    'phone' => $data['phone'] ?? '',
    'qualification' => $data['qualification'] ?? '',
    'main_subject' => $data['main_subject'] ?? '',
    'salary' => $data['salary'] ?? '',
    'joining_date' => $data['joining_date'] ?? '',
    'address' => $data['teacherAddress'] ?? $data['address'] ?? ''
];


if (!empty($imagePath)) {
    $teacher['photo'] = $imagePath;
}

$userId = null;
$currentTeacher = $cc->common_select('Teachers', 'user_id', ['id' => $id]);
if (!empty($currentTeachers['data'][0]->user_id)) {
    $userId = $currentTeachers['data'][0]->user_id;
}

if ($userId) {
    $user = [
        'email' => $data['email'] ?? '',
        'username' => !empty($data['email']) ? $data['email'] : ($data['phone'] ?? ''),
        'role' => 'teacher',
        'is_active' => 1,
    ];

    if (!empty(trim((string) ($data['password'] ?? '')))) {
        $user['password'] = md5($data['password']);
    }

    $userUpdate = $cc->common_update('users', $user, ['id' => $userId]);
    if ($userUpdate['status'] !== true) {
        echo json_encode([
            'status' => false,
            'data' => [],
            'message' => 'Teacher user could not be updated.'
        ]);
        exit;
    }
}

$res = $cc->common_update('teachers', $teacher, ['id' => $id]);

if ($res['status'] !== true) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'Teachers data could not be updated.'
    ]);
    exit;
}

echo json_encode([
    'status' => true,
    'data' => $res['data'],
    'message' => 'Teachers updated successfully.'
]);