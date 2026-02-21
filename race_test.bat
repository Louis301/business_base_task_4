
### `race_test.bat` (для Windows)
```batch
@echo off
set ID=2
echo Запуск конкурентных запросов к заявке #%ID%...

start /b curl -X POST http://localhost:8080/api/take/%ID%
start /b curl -X POST http://localhost:8080/api/take/%ID%

timeout /t 2 /nobreak >nul
echo Проверьте базу данных - статус должен быть in_progress