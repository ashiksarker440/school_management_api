<?php
require_once 'bootstrap.php';
require_method('POST');
$input = read_json_body();

$email = isset($input['email']) && is_string($input['email']) ? strtolower(trim($input['email'])) : '';
$password = isset($input['password']) && is_string($input['password']) ? $input['password'] : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    send_json(422, array('message' => 'Enter your email and password.'));
}

$db = database_connection();
$statement = $db->prepare('SELECT id, full_name, email, password_hash FROM auth_users WHERE email = ? LIMIT 1');
if (!$statement) {
    send_json(500, array('message' => 'Could not prepare the sign-in request.'));
}
$statement->bind_param('s', $email);
$statement->execute();
$statement->bind_result($userId, $fullName, $userEmail, $passwordHash);

if (!$statement->fetch() || !password_verify($password, $passwordHash)) {
    send_json(401, array('message' => 'Email or password is incorrect.'));
}

$statement->close();
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $userId;

send_json(200, array(
    'message' => 'Signed in successfully.',
    'user' => array('id' => (int) $userId, 'full_name' => $fullName, 'email' => $userEmail),
));