@echo off
cd /d C:\Users\Jordan\Documents\GitHub\fittrackCV
call venv\Scripts\activate.bat
python -m uvicorn api:app --host 0.0.0.0 --port 8000
