<#
stream_start.ps1

Usage (PowerShell):
  .\stream_start.ps1 -Rtsp "rtsp://user:pass@10.0.0.1:554/stream1" -Ffmpeg "C:\path\to\ffmpeg.exe" -OutDir "C:\xampp\htdocs\thesis2\stream"

This script starts ffmpeg to convert an RTSP stream to HLS segments in the given OutDir so Apache can serve them.
#>

param(
  [string]$Rtsp = "rtsp://admin01:admin0101@10.238.20.224:554/stream1",
  [string]$Ffmpeg = "C:\\xampp\\htdocs\\thesis2\\ffmpeg-8.0-essentials_build\\bin\\ffmpeg.exe",
  [string]$OutDir = "C:\\xampp\\htdocs\\thesis2\\stream"
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

Write-Host "Starting ffmpeg (logs -> $log). Press Ctrl+C to stop."

# Try to copy the stream to HLS segments; if that fails you can switch to re-encoding (see note below)
& $Ffmpeg -rtsp_transport tcp -i $Rtsp -c:v copy -c:a aac -f hls -hls_time 2 -hls_list_size 6 -hls_flags delete_segments+append_list -hls_segment_filename "$seg" "$m3u8" 2>&1 | Tee-Object -FilePath $log

Write-Host "ffmpeg exited. See $log for details."

# If the above fails due to incompatible codec, try re-encoding like this (uncomment and run separately):
# & $Ffmpeg -rtsp_transport tcp -i $Rtsp -c:v libx264 -preset veryfast -b:v 1200k -c:a aac -f hls -hls_time 2 -hls_list_size 6 -hls_flags delete_segments+append_list -hls_segment_filename "$seg" "$m3u8" 2>&1 | Tee-Object -FilePath $log
