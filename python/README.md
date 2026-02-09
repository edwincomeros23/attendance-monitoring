# Face recognizer (Python)

This folder contains a small Python script that connects to an RTSP camera and performs face recognition using `face_recognition` (dlib) + OpenCV.

Quick start
1. Create a Python 3.8+ virtual environment and activate it.
2. Install dependencies (Windows users: see notes about dlib):

The project provides a helper script that automates venv creation and package installation (recommended). If you prefer to run commands manually, both options are shown.

Option A — automated (recommended on Windows)

```powershell
# open PowerShell (run as your user; Admin not required unless you need to install Python)
cd C:\xampp\htdocs\attendance-monitoring\python
# run the helper which will detect `py`/`python`, create .venv and install requirements
.\python_setup.ps1
```

Option B — manual (works if `python`/`py` is available)

```powershell
cd C:\xampp\htdocs\attendance-monitoring\python
# create venv (use `py -3` if `python` is not on PATH)
py -3 -m venv .venv
# allow script execution for this session if blocked
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope Process -Force
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
python -m pip install -r requirements.txt
```

If you prefer automation, run the helper script (it will create the venv and install packages):

```powershell
# run from c:\xampp\htdocs\attendance-monitoring\python
.\python_setup.ps1
```

Conda alternative (recommended on Windows when building `dlib` is problematic):

```powershell
# using conda (example)
conda create -n facerec python=3.9 -y
conda activate facerec
pip install -r requirements.txt
```

3. Prepare known faces directory (recommended layout):

```
known_faces/
  S001_JohnDoe/
    1.jpg
    2.jpg
  S002_JaneSmith/
    1.jpg
```

4. Run the recognizer (use your RTSP URL):

```powershell
python face_recognizer.py --rtsp "rtsp://admin01:admin0101@10.238.20.224:554/stream2" --known ../known_faces --out ../attendance.json --display
```

Notes
- `face_recognition` requires `dlib`. On Windows it's easiest to install via a prebuilt wheel or use `conda`.
- The script writes recognition events to `attendance.json` (append-style). Use `--post-url` to POST events to your PHP server.

Troubleshooting — common Windows issues

- "python/py not found": run these checks in PowerShell to see what's available:

```powershell
Get-Command python, py -ErrorAction SilentlyContinue
py -3 --version
python --version
```

- If neither `py` nor `python` are found, install Python 3.9+ from https://python.org or via winget:

```powershell
winget install --id Python.Python.3 -e
```

- If `python` launches the Microsoft Store (App Execution Alias), disable the alias:
  Settings → Apps → Advanced app settings → App execution aliases → turn off `python.exe` / `python3.exe` aliases. Then reinstall or point to the installed Python.

- If `face_recognition` fails to build (typical dlib compile errors), use Miniconda and install with pip inside the conda env, or use a prebuilt wheel:

```powershell
# conda example (recommended on Windows)
conda create -n facerec python=3.9 -y
conda activate facerec
pip install -r requirements.txt
```

Next steps after venv & deps

1. Prepare a `known_faces/` folder (one subfolder per student, folder name used as label).
2. Run the recognizer (example uses the RTSP path that worked for you):

```powershell
.
\.venv\Scripts\Activate.ps1
python face_recognizer.py --rtsp "rtsp://admin01:admin0101@10.238.20.224:554/stream2" --known ..\known_faces --out ..\attendance.json --display
```

If you'd like, I can add a small PHP endpoint to receive POST events and mark attendance in the DB.
