<?php
require_once 'bootstrap.php';
require_method('POST');

$_SESSION = array();
$cookie = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
session_destroy();

send_json(200, array('message' => 'Signed out successfully.'));