<#
python_setup.ps1

Automates creation of a venv and installation of requirements for the face recognizer.

Usage (PowerShell):
  .\python_setup.ps1

This script will:
- detect `py` or `python` on PATH
- create `.venv` in the current folder if missing
- install/upgrade pip, setuptools, wheel inside the venv
- attempt to install packages from requirements.txt

If installation of `face_recognition`/`dlib` fails on Windows, the script prints guidance to use conda or a prebuilt wheel.
#>

Set-StrictMode -Version Latest
Write-Host "Python environment helper for face_recognizer" -ForegroundColor Cyan

function Find-Python {
    $candidates = @('py -3', 'python', 'python3')
    foreach ($c in $candidates) {
        try {
            $ver = & cmd /c "$c --version" 2>&1
            if ($LASTEXITCODE -eq 0 -and $ver) {
                return $c
            }
        } catch { }
    }
    return $null
}

$pycmd = Find-Python
if (-not $pycmd) {
    Write-Host "No python launcher found. Please install Python 3.8+ (https://python.org) or use Miniconda." -ForegroundColor Yellow
    exit 1
}

Write-Host "Using python command: $pycmd"

# Create venv
if (-not (Test-Path .venv)) {
    Write-Host "Creating virtual environment .venv..."
    & cmd /c "$pycmd -m venv .venv" 
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Failed to create venv. Try installing Python or use conda." -ForegroundColor Red
        exit 2
    }
} else {
    Write-Host ".venv already exists, skipping creation."
}

# Path to pip inside venv
$venvPip = Join-Path -Path (Join-Path -Path (Get-Location) -ChildPath '.venv') -ChildPath 'Scripts\pip.exe'
if (-not (Test-Path $venvPip)) {
    Write-Host "pip not found inside .venv. Ensure venv was created correctly." -ForegroundColor Red
    exit 3
}

Write-Host "Upgrading pip, setuptools, wheel in venv..."
& "$venvPip" install --upgrade pip setuptools wheel

if (-not (Test-Path "requirements.txt")) {
    Write-Host "requirements.txt not found in current folder. Expected: ./requirements.txt" -ForegroundColor Yellow
    exit 4
}

Write-Host "Installing requirements from requirements.txt..."
& "$venvPip" install -r requirements.txt

if ($LASTEXITCODE -ne 0) {
    Write-Host "Installation finished with errors. If installation failed while building dlib, consider using Miniconda or installing a prebuilt dlib wheel." -ForegroundColor Yellow
    Write-Host "Conda example (recommended on Windows):`n  conda create -n facerec python=3.9`n  conda activate facerec`n  pip install -r requirements.txt" -ForegroundColor Cyan
    exit 5
}

Write-Host "Setup complete. Activate the venv with:`n  .\.venv\Scripts\Activate.ps1" -ForegroundColor Green
exit 0
