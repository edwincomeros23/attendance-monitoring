@echo off
cd /d "C:\xampp\htdocs\attendance-monitoring\ffmpeg-8.0-essentials_build\bin"

REM HLS streaming with 30-segment buffer (60 seconds at 2sec/segment)
REM Proper flags: delete_segments removes old segments, live_start_index keeps manifest fresh
ffmpeg.exe -i rtsp://admin01:admin123@192.168.1.5:554/stream1 ^
-fflags +genpts -use_wallclock_as_timestamps 1 ^
-vf "scale=1280:-2" -r 15 ^
-c:v libx264 -preset veryfast -tune zerolatency -g 30 ^
-b:v 1200k -maxrate 1500k -bufsize 2400k ^
-c:a aac -ar 44100 -ac 1 -b:a 64k ^
-f hls -hls_time 2 -hls_list_size 30 -hls_flags delete_segments ^
"C:\xampp\htdocs\attendance-monitoring\stream\index.m3u8"
pause
