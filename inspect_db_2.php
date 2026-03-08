<?php
require_once 'c:/Apache24/htdocs/erp/config/config.php';
require_once 'c:/Apache24/htdocs/erp/vendor/autoload.php';

$tables = ['students', 'fee_records', 'payment_transactions'];
$db = getDBConnection();

foreach ($tables as $table) {
    echo "\n--- $table ---\n";
    $stmt = $db->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} | {$row['Type']}\n";
    }
}
