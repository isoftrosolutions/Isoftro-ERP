# Delete Debug Scripts
# This script removes all debug scripts from the ERP root directory

# PHP Debug Scripts
$phpScripts = @(
    "debug_db.php",
    "debug_db_v2.php",
    "debug_db_v3.php",
    "debug_db_v4.php",
    "debug_db_v5.php",
    "debug_db_v6.php",
    "debug_db_v7.php",
    "debug_db_v8.php",
    "debug_db_v9.php",
    "debug_db_v10.php",
    "debug_db_v11.php",
    "debug_db_v12.php",
    "debug_teachers.php"
)

# Python Debug/Check Scripts
$pythonScripts = @(
    "check_all_sql.py",
    "check_services_sql.py",
    "check_sql_joins.py",
    "deep_check_sql.py",
    "detect_bad_queries.py",
    "master_check_sql.py"
)

$allScripts = $phpScripts + $pythonScripts
$deletedCount = 0
$notFoundCount = 0

Write-Host "Starting debug scripts deletion..." -ForegroundColor Yellow
Write-Host ""

foreach ($script in $allScripts) {
    if (Test-Path $script) {
        Remove-Item $script -Force
        Write-Host "[DELETED] $script" -ForegroundColor Green
        $deletedCount++
    } else {
        Write-Host "[NOT FOUND] $script" -ForegroundColor DarkGray
        $notFoundCount++
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deletion Complete!" -ForegroundColor Cyan
Write-Host "Deleted: $deletedCount files" -ForegroundColor Green
Write-Host "Not Found: $notFoundCount files" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
