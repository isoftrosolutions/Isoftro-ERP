<?php
require_once 'c:/Apache24/htdocs/erp/config/config.php';
$db = getDBConnection();
$stmt = $db->query("SELECT * FROM modules");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['id']} | {$row['name']} | {$row['slug']}\n";
}
