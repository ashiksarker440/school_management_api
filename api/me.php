<?php
require_once 'bootstrap.php';
require_method('GET');

if (!isset($_SESSION['user_id'])) {
    send_json(401, array('message' => 'Please sign in to view your account.'));
}

$db = database_connection();
$userId = (int) $_SESSION['user_id'];
$statement = $db->prepare('SELECT id, full_name, email, created_at FROM auth_users WHERE id = ? LIMIT 1');
if (!$statement) {
    send_json(500, array('message' => 'Could not load your account.'));
}
$statement->bind_param('i', $userId);
$statement->execute();
$statement->bind_result($id, $fullName, $email, $createdAt);

if (!$statement->fetch()) {
    unset($_SESSION['user_id']);
    send_json(401, array('message' => 'Please sign in to view your account.'));
}

send_json(200, array(
    'user' => array(
        'id' => (int) $id,
        'full_name' => $fullName,
        'email' => $email,
        'created_at' => $createdAt,
    ),
));