# Simple Tapo Camera Scanner
# Scans your network for devices with open RTSP port (554)

Write-Host "=== Tapo Camera Scanner ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Scanning 192.168.254.x network for cameras..." -ForegroundColor Yellow
Write-Host "This will take about 1-2 minutes..." -ForegroundColor Yellow
Write-Host ""

$foundDevices = @()

for ($i = 1; $i -le 254; $i++) {
    $ip = "192.168.254.$i"
    
    # Show progress every 25 IPs
    if ($i % 25 -eq 0) {
        Write-Host "Scanned $i addresses..." -ForegroundColor Gray
    }
    
    # Quick ping test first
    $ping = Test-Connection -ComputerName $ip -Count 1 -Quiet -TimeoutSeconds 1 2>$null
    
    if ($ping) {
        Write-Host "Device found at $ip - checking ports..." -ForegroundColor Yellow
        
        # Check RTSP port 554
        try {
            $tcpClient = New-Object System.Net.Sockets.TcpClient
            $connect = $tcpClient.BeginConnect($ip, 554, $null, $null)
            $wait = $connect.AsyncWaitHandle.WaitOne(1000, $false)
            
            if ($wait) {
                $tcpClient.EndConnect($connect)
                $foundDevices += $ip
                Write-Host "✓ Camera found at $ip (RTSP port 554 open)" -ForegroundColor Green
            }
            $tcpClient.Close()
        } catch {}
    }
}

Write-Host ""
Write-Host "=== Scan Complete ===" -ForegroundColor Cyan
Write-Host ""

if ($foundDevices.Count -gt 0) {
    Write-Host "Found $($foundDevices.Count) camera(s)!" -ForegroundColor Green
    Write-Host ""
    
    foreach ($ip in $foundDevices) {
        Write-Host "Camera IP: $ip" -ForegroundColor Yellow
        Write-Host "RTSP URLs to try:" -ForegroundColor Cyan
        Write-Host "  1. rtsp://admin01:admin0101@${ip}:554/stream1" -ForegroundColor White
        Write-Host "  2. rtsp://admin01:admin0101@${ip}:554/stream2" -ForegroundColor White
        Write-Host "  3. rtsp://admin01:admin0101@${ip}/stream1" -ForegroundColor White
        Write-Host ""
        Write-Host "To setup this camera, run:" -ForegroundColor Green
        Write-Host "  .\setup_tapo_stream.ps1 -CameraIP $ip" -ForegroundColor Yellow
        Write-Host ""
    }
} else {
    Write-Host "No cameras found with open RTSP port." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Please try:" -ForegroundColor Cyan
    Write-Host "1. Check Tapo app on your phone (Settings > Device Info > IP Address)"
    Write-Host "2. Check your router admin page (http://192.168.254.254)"
    Write-Host "3. Make sure the camera is powered on and connected"
    Write-Host ""
}
