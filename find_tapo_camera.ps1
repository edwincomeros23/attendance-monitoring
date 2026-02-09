# Find Tapo Camera on Network
# This script scans your local network to find the Tapo camera

Write-Host "=== Tapo Camera Finder ===" -ForegroundColor Cyan
Write-Host ""

# Get local IP address
$localIP = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.InterfaceAlias -notlike "*Loopback*" -and $_.PrefixOrigin -eq "Dhcp"}).IPAddress | Select-Object -First 1

if (-not $localIP) {
    $localIP = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.InterfaceAlias -notlike "*Loopback*"}).IPAddress | Select-Object -First 1
}

Write-Host "Your computer's IP: $localIP" -ForegroundColor Green

# Extract network prefix (e.g., 192.168.1)
$networkPrefix = ($localIP -split '\.')[0..2] -join '.'
Write-Host "Scanning network: $networkPrefix.0/24" -ForegroundColor Yellow
Write-Host ""

# Scan common Tapo ports (554 for RTSP, 2020 for Tapo)
Write-Host "Scanning for devices with open ports (this may take a minute)..." -ForegroundColor Cyan

$devices = @()

# Quick ping scan first
1..254 | ForEach-Object -Parallel {
    $ip = "$using:networkPrefix.$_"
    $ping = Test-Connection -ComputerName $ip -Count 1 -Quiet -TimeoutSeconds 1
    if ($ping) {
        # Check RTSP port (554)
        $rtspTest = Test-NetConnection -ComputerName $ip -Port 554 -InformationLevel Quiet -WarningAction SilentlyContinue
        # Check Tapo port (2020)
        $tapoTest = Test-NetConnection -ComputerName $ip -Port 2020 -InformationLevel Quiet -WarningAction SilentlyContinue
        
        if ($rtspTest -or $tapoTest) {
            [PSCustomObject]@{
                IP = $ip
                RTSP = $rtspTest
                Tapo = $tapoTest
            }
        }
    }
} -ThrottleLimit 50 | ForEach-Object {
    $devices += $_
    Write-Host "Found device: $($_.IP) [RTSP: $($_.RTSP), Tapo: $($_.Tapo)]" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== Scan Complete ===" -ForegroundColor Cyan
Write-Host ""

if ($devices.Count -eq 0) {
    Write-Host "No cameras found. Possible reasons:" -ForegroundColor Yellow
    Write-Host "1. Camera might be on a different subnet"
    Write-Host "2. Firewall might be blocking the scan"
    Write-Host "3. Camera ports might be different"
    Write-Host ""
    Write-Host "Try checking your router's admin page (usually 192.168.1.1 or 192.168.0.1)" -ForegroundColor Cyan
    Write-Host "Look for connected devices - the Tapo camera should appear there with its IP address."
} else {
    Write-Host "Found $($devices.Count) potential camera(s):" -ForegroundColor Green
    $devices | ForEach-Object {
        Write-Host ""
        Write-Host "Device IP: $($_.IP)" -ForegroundColor Yellow
        Write-Host "RTSP URL (try these):"
        Write-Host "  Main stream:  rtsp://admin01:admin0101@$($_.IP):554/stream1" -ForegroundColor White
        Write-Host "  Sub stream:   rtsp://admin01:admin0101@$($_.IP):554/stream2" -ForegroundColor White
    }
}

Write-Host ""
Write-Host "=== Next Steps ===" -ForegroundColor Cyan
Write-Host "1. Copy one of the RTSP URLs above"
Write-Host "2. Run: .\setup_tapo_stream.ps1 -CameraIP <IP_ADDRESS>"
Write-Host ""
