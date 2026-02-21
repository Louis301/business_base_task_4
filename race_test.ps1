# race_test_simple.ps1 — ИСПРАВЛЕННАЯ ВЕРСИЯ
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$ID = 3
$URL = "http://localhost:8080/api/take/$ID"
$COOKIE_FILE = "$env:TEMP\cookies_$ID.txt"
$TEMP_OUTPUT = "$env:TEMP\curl_output_$ID.txt"

Write-Host "?? Авторизация..." -ForegroundColor Cyan

# 1. Авторизация (сохраняем куки в файл)
curl.exe -c $COOKIE_FILE -b $COOKIE_FILE -d "user_id=2&role=master&username=master1" "http://localhost:8080/login" -s -o nul

# 2. Проверка статуса заявки перед тестом
$dbPath = "C:\Apache24\htdocs\repair-service\database\repair.db"
$status = sqlite3 $dbPath "SELECT status FROM requests WHERE id=$ID;"
Write-Host "Статус заявки #$ID перед тестом: $status" -ForegroundColor Yellow

if ($status -ne "assigned") {
    Write-Host "? Заявка не в статусе 'assigned'! Обновите вручную." -ForegroundColor Red
    exit 1
}

# 3. Запуск двух запросов ПОСЛЕДОВАТЕЛЬНО
Write-Host "`n?? Запуск запросов..." -ForegroundColor Cyan

# Функция для выполнения запроса и получения кода + тела
function Invoke-CurlWithCode {
    param($url, $cookieFile, $tempFile)
    
    # Выполняем curl, сохраняем тело в файл, код — в переменную
    $code = curl.exe -b $cookieFile -X POST $url -s -o $tempFile -w "%{http_code}"
    $body = Get-Content $tempFile -Raw -Encoding UTF8
    return @{ Code = $code; Body = $body }
}

# Запрос 1
$result1 = Invoke-CurlWithCode -url $URL -cookieFile $COOKIE_FILE -tempFile $TEMP_OUTPUT
$code1 = $result1.Code
$body1 = $result1.Body

# Минимальная задержка для имитации конкурентности
Start-Sleep -Milliseconds 50

# Запрос 2
$result2 = Invoke-CurlWithCode -url $URL -cookieFile $COOKIE_FILE -tempFile $TEMP_OUTPUT
$code2 = $result2.Code
$body2 = $result2.Body

# 4. Вывод результатов
Write-Host "`n=== Результаты ===" -ForegroundColor Yellow
Write-Host "Запрос 1: HTTP $code1 - $body1" -ForegroundColor $(if($code1 -eq "200"){"Green"}else{"Red"})
Write-Host "Запрос 2: HTTP $code2 - $body2" -ForegroundColor $(if($code2 -eq "200"){"Green"}else{"Red"})

# 5. Проверка финального статуса в БД
$finalStatus = sqlite3 $dbPath "SELECT status FROM requests WHERE id=$ID;"
Write-Host "`nФинальный статус заявки: $finalStatus" -ForegroundColor Cyan

# 6. Анализ
if ((($code1 -eq "200") -and ($code2 -eq "409")) -or (($code1 -eq "409") -and ($code2 -eq "200"))) {
    Write-Host "`n? УСПЕХ: Гонка обработана корректно!" -ForegroundColor Green
} else {
    Write-Host "`n? Ожидался один 200 и один 409. Получено: $code1, $code2" -ForegroundColor Yellow
}

# Очистка
Remove-Item $COOKIE_FILE -ErrorAction SilentlyContinue
Remove-Item $TEMP_OUTPUT -ErrorAction SilentlyContinue