# Quick Guide: Find Your Tapo Camera IP Address

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "   HOW TO FIND YOUR TAPO CAMERA IP" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "METHOD 1: Use Tapo App (Easiest)" -ForegroundColor Green
Write-Host "-----------------------------------"
Write-Host "1. Open the Tapo app on your phone"
Write-Host "2. Tap on your camera"
Write-Host "3. Tap the gear icon (Settings)"
Write-Host "4. Scroll down to 'Device Info'"
Write-Host "5. Look for 'IP Address'"
Write-Host ""

Write-Host "METHOD 2: Check Your Router" -ForegroundColor Green
Write-Host "-----------------------------------"
Write-Host "1. Open a web browser"
Write-Host "2. Go to one of these addresses:"
Write-Host "   - http://192.168.1.1"
Write-Host "   - http://192.168.0.1"
Write-Host "   - http://192.168.254.254"
Write-Host "   - http://10.0.0.1"
Write-Host "3. Login with your router credentials"
Write-Host "4. Look for 'Connected Devices' or 'DHCP Client List'"
Write-Host "5. Find a device named like 'Tapo' or 'TP-Link'"
Write-Host ""

Write-Host "METHOD 3: Use Network Scanner" -ForegroundColor Green
Write-Host "-----------------------------------"
Write-Host "Run the automatic scanner:"
Write-Host "   .\find_tapo_camera.ps1" -ForegroundColor Yellow
Write-Host ""

Write-Host "METHOD 4: Use ARP Command" -ForegroundColor Green
Write-Host "-----------------------------------"
Write-Host "Checking ARP table for recently connected devices..."
Write-Host ""

arp -a | Select-String "192.168|10.0" | ForEach-Object {
    Write-Host $_ -ForegroundColor White
}

Write-Host ""
Write-Host "Look for an IP address that appears to be a camera (usually has a MAC address starting with common TP-Link prefixes)"
Write-Host ""

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "   ONCE YOU HAVE THE IP ADDRESS" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Run this command (replace <IP> with your camera's IP):" -ForegroundColor Yellow
Write-Host ""
Write-Host "   .\setup_tapo_stream.ps1 -CameraIP <IP>" -ForegroundColor Green
Write-Host ""
Write-Host "Example:" -ForegroundColor Yellow
Write-Host "   .\setup_tapo_stream.ps1 -CameraIP 192.168.1.100" -ForegroundColor Green
Write-Host ""

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
