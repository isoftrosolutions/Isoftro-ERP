<?php
require_once 'c:/Apache24/htdocs/erp/config/config.php';
$db = getDBConnection();
$stmt = $db->query("DESCRIBE institute_modules");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
