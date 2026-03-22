<?php
require_once 'c:/Apache24/htdocs/erp/config/config.php';
$db = getDBConnection();
$stmt = $db->query("SHOW TABLES");
foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) {
    if(strpos($t, 'mod') !== false || strpos($t, 'ten') !== false) {
        echo "$t\n";
    }
}
