#!/usr/bin/env python3
"""
Simple RTSP face recognizer using face_recognition + OpenCV.

Usage:
  python face_recognizer.py --rtsp "rtsp://user:pass@host:554/stream" --known ./known_faces --out attendance.json

Notes:
 - Prepare a folder with known faces. Recommended layout:
     ./known_faces/StudentID_Name/1.jpg
     ./known_faces/StudentID_Name/2.jpg
   The folder name will be used as the label (StudentID_Name).
 - face_recognition depends on dlib. On Windows installing via pip may be slow; see README.
 - The script writes matched entries to the output JSON file (append mode, deduplicates per run timestamp).
 - Optionally, set --post-url to POST recognition events to a server endpoint (JSON payload).
"""
import os
import sys
import time
import json
import argparse
from datetime import datetime

import cv2
import face_recognition
import numpy as np
import requests


def load_known_faces(known_dir):
    known_encodings = []
    known_labels = []
    if not os.path.isdir(known_dir):
        print(f"Known faces directory not found: {known_dir}")
        return known_encodings, known_labels

    for label in sorted(os.listdir(known_dir)):
        label_dir = os.path.join(known_dir, label)
        if not os.path.isdir(label_dir):
            continue
        for fname in os.listdir(label_dir):
            path = os.path.join(label_dir, fname)
            if not os.path.isfile(path):
                continue
            try:
                img = face_recognition.load_image_file(path)
                encs = face_recognition.face_encodings(img)
                if len(encs) > 0:
                    known_encodings.append(encs[0])
                    known_labels.append(label)
                    print(f"Loaded {label}/{fname}")
                else:
                    print(f"Warning: no face found in {path}")
            except Exception as e:
                print(f"Error loading {path}: {e}")

    print(f"Total known faces loaded: {len(known_encodings)}")
    return known_encodings, known_labels


def mark_attendance(out_file, label, timestamp=None, post_url=None):
    if timestamp is None:
        timestamp = datetime.utcnow().isoformat()
    entry = {"label": label, "timestamp": timestamp}
    try:
        data = []
        if os.path.exists(out_file):
            with open(out_file, "r", encoding="utf-8") as f:
                try:
                    data = json.load(f)
                except Exception:
                    data = []
        data.append(entry)
        with open(out_file, "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2)
        print(f"Marked: {label} @ {timestamp}")
    except Exception as e:
        print(f"Failed to write attendance: {e}")

    if post_url:
        try:
            r = requests.post(post_url, json=entry, timeout=5)
            print(f"Posted to {post_url}: {r.status_code}")
        except Exception as e:
            print(f"Failed to POST to {post_url}: {e}")


def run(rtsp, known_dir, out_file, post_url=None, display=False, tolerance=0.6):
    known_encodings, known_labels = load_known_faces(known_dir)
    if len(known_encodings) == 0:
        print("No known faces loaded. Exiting.")
        return

    print(f"Opening RTSP stream: {rtsp}")
    cap = cv2.VideoCapture(rtsp)
    if not cap.isOpened():
        print("Failed to open RTSP stream. Exiting.")
        return

    seen = set()
    try:
        while True:
            ret, frame = cap.read()
            if not ret:
                print("No frame, waiting 0.5s")
                time.sleep(0.5)
                continue

            # reduce size for speed
            small = cv2.resize(frame, (0, 0), fx=0.5, fy=0.5)
            rgb = small[:, :, ::-1]

            boxes = face_recognition.face_locations(rgb, model='hog')
            encodings = face_recognition.face_encodings(rgb, boxes)

            for enc, box in zip(encodings, boxes):
                # compare
                dists = face_recognition.face_distance(known_encodings, enc)
                if len(dists) == 0:
                    continue
                idx = np.argmin(dists)
                if dists[idx] <= tolerance:
                    label = known_labels[idx]
                    # dedupe by label & minute
                    key = (label, datetime.utcnow().strftime('%Y%m%d%H%M'))
                    if key not in seen:
                        seen.add(key)
                        mark_attendance(out_file, label, datetime.utcnow().isoformat(), post_url=post_url)
                    name = label
                else:
                    name = 'Unknown'

                # draw box on original frame (scale coordinates)
                top, right, bottom, left = box
                top *= 2; right *= 2; bottom *= 2; left *= 2
                cv2.rectangle(frame, (left, top), (right, bottom), (0, 255, 0), 2)
                cv2.putText(frame, name, (left, top - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0,255,0), 2)

            if display:
                cv2.imshow('face-recognizer', frame)
                if cv2.waitKey(1) & 0xFF == ord('q'):
                    break

    except KeyboardInterrupt:
        print('Interrupted')
    finally:
        cap.release()
        cv2.destroyAllWindows()


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--rtsp', required=True)
    p.add_argument('--known', default='known_faces')
    p.add_argument('--out', default='attendance.json')
    p.add_argument('--post-url', default=None, help='Optional URL to POST detection events')
    p.add_argument('--display', action='store_true', help='Show video window')
    p.add_argument('--tolerance', type=float, default=0.6)
    args = p.parse_args()

    run(args.rtsp, args.known, args.out, post_url=args.post_url, display=args.display, tolerance=args.tolerance)


if __name__ == '__main__':
    main()
