<#
stream_start.ps1

Usage (PowerShell):
  .\stream_start.ps1 -Rtsp "rtsp://user:pass@10.0.0.1:554/stream1" -Ffmpeg "C:\path\to\ffmpeg.exe" -OutDir "C:\xampp\htdocs\thesis2\stream"

This script starts ffmpeg to convert an RTSP stream to HLS segments in the given OutDir so Apache can serve them.
#>

param(
  [string]$Rtsp = "rtsp://admin01:admin0101@192.168.254.146:554/stream1",
  [string]$Ffmpeg = "C:\\xampp\\htdocs\\attendance-monitoring\\ffmpeg-8.0-essentials_build\\bin\\ffmpeg.exe",
  [string]$OutDir = "C:\\xampp\\htdocs\\attendance-monitoring\\stream"
)

Write-Host "RTSP -> HLS helper"
Write-Host "RTSP: $Rtsp"
Write-Host "ffmpeg: $Ffmpeg"
Write-Host "OutDir: $OutDir"

if (-not (Test-Path $OutDir)) {
  Write-Host "Creating output directory: $OutDir"
  New-Item -ItemType Directory -Path $OutDir -Force | Out-Null
}

$seg = Join-Path $OutDir "segment_%03d.ts"
$m3u8 = Join-Path $OutDir "index.m3u8"
$log = Join-Path $OutDir "ffmpeg.log"

Write-Host "Clearing old HLS artifacts"
Remove-Item -Path (Join-Path $OutDir "*.ts") -Force -ErrorAction SilentlyContinue
Remove-Item -Path $m3u8 -Force -ErrorAction SilentlyContinue
Remove-Item -Path $log -Force -ErrorAction SilentlyContinue

Write-Host "Starting ffmpeg (logs -> $log). Press Ctrl+C to stop."

# Re-encode to stabilize timestamps for HLS and keep bitrate reasonable for the server.
# hls_list_size 30 = 60 seconds buffer (30 segments * 2 sec each) prevents buffer starvation
# delete_segments removes old TS files, live_start_index keeps playback stable
& $Ffmpeg `
  -nostdin `
  -rtsp_transport tcp `
  -i $Rtsp `
  -fflags +genpts `
  -use_wallclock_as_timestamps 1 `
  -vf "scale=1280:-2" `
  -r 15 `
  -c:v libx264 `
  -preset veryfast `
  -tune zerolatency `
  -x264-params "scenecut=0:open_gop=0" `
  -g 30 `
  -keyint_min 30 `
  -b:v 1200k `
  -maxrate 1500k `
  -bufsize 2400k `
  -c:a aac `
  -ar 44100 `
  -ac 1 `
  -b:a 64k `
  -f hls `
  -hls_time 2 `
  -hls_list_size 30 `
  -hls_flags "delete_segments" `
  -hls_segment_filename "$seg" `
  "$m3u8" 2>&1 | Tee-Object -FilePath $log

Write-Host "ffmpeg exited. See $log for details."
