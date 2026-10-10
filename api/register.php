<?php
require_once 'bootstrap.php';
require_method('POST');
$input = read_json_body();

$fullName = isset($input['full_name']) && is_string($input['full_name']) ? trim($input['full_name']) : '';
$email = isset($input['email']) && is_string($input['email']) ? strtolower(trim($input['email'])) : '';
$password = isset($input['password']) && is_string($input['password']) ? $input['password'] : '';

if ($fullName === '' || strlen($fullName) > 100) {
    send_json(422, array('message' => 'Enter a name with no more than 100 characters.'));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    send_json(422, array('message' => 'Enter a valid email address.'));
}
if (strlen($password) < 8 || strlen($password) > 72) {
    send_json(422, array('message' => 'Choose a password between 8 and 72 characters.'));
}

$db = database_connection();
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$statement = $db->prepare('INSERT INTO auth_users (full_name, email, password_hash) VALUES (?, ?, ?)');
if (!$statement) {
    send_json(500, array('message' => 'Could not prepare the registration request.'));
}
$statement->bind_param('sss', $fullName, $email, $passwordHash);

if (!$statement->execute()) {
    if ($statement->errno === 1062) {
        send_json(409, array('message' => 'An account with this email already exists.'));
    }
    send_json(500, array('message' => 'Could not create the account.'));
}

send_json(201, array(
    'message' => 'Account created. You can now sign in.',
    'user' => array(
        'id' => $statement->insert_id,
        'full_name' => $fullName,
        'email' => $email,
    ),
));