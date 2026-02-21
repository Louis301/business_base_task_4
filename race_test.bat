
@echo off
set ID=2
echo Launching competitive requests for an application #%ID%...

start /b curl -X POST http://localhost:8080/api/take/%ID%
start /b curl -X POST http://localhost:8080/api/take/%ID%

timeout /t 2 /nobreak >nul
echo Check the database - the status should be in_progress