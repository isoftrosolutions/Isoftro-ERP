<?php
require_once __DIR__ . '/../config/config.php';

$db = getDBConnection();

// List all tables to see what we have
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "All Tables:\n" . implode(", ", $tables) . "\n\n";

function describeTable($db, $table) {
    echo "--- $table ---\n";
    try {
        $stmt = $db->query("DESCRIBE `$table`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  {$row['Field']} ({$row['Type']}) - {$row['Null']} - {$row['Key']}\n";
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

$interesting = ['timetable_slots', 'batches', 'teachers', 'subjects', 'courses', 'staff', 'rooms'];
foreach ($interesting as $t) {
    if (in_array($t, $tables)) {
        describeTable($db, $t);
    } else {
        echo "Table $t not found.\n\n";
    }
}
