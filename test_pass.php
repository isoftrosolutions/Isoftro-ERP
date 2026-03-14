<?php
require 'config/config.php';
$db = getDBConnection();
$stmt = $db->query("SELECT email, password_hash, status FROM users WHERE email='nepalcyberfirm@gmail.com'");
$user = $stmt->fetch(\PDO::FETCH_ASSOC);
print_r($user);
echo "Verifying Hamro@123: " . (password_verify('Hamro@123', $user['password_hash']) ? 'Yes' : 'No') . "\n";
