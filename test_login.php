<?php
require 'vendor/autoload.php';
require 'config/config.php';

$auth = new \App\Http\Controllers\AuthController();
$_POST['email'] = 'nepalcyberfirm@gmail.com';
$_POST['password'] = 'Hamro@123'; // The default test password typically used
$result = $auth->login();

echo json_encode($result, JSON_PRETTY_PRINT);
