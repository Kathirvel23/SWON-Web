# Start SWON Web on http://127.0.0.1:8080
$php = 'C:\xampp\php\php.exe'
if (-not (Test-Path $php)) { $php = 'php' }

Write-Host "SWON Web -> http://127.0.0.1:8080"
Write-Host "Press Ctrl+C to stop."
& $php -S 127.0.0.1:8080 -t public public/router.php
