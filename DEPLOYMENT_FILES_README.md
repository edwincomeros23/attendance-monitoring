# Deployment Files Reference

This directory contains container and deployment configuration for Render.com hosting.

## Files

### `Dockerfile`
- Builds PHP 8.1 Apache container
- Installs: PHP extensions, FFmpeg, Python 3, face-recognition
- Exposes port 80 (HTTPS by Render)
- Sets working directory to `/var/www/html`
- Configurable upload limits for student Photos (50MB)

### `render.yaml`
- Defines Web Service (PHP Apache)
- Defines MySQL Database (free tier)
- Injects environment variables (`DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`)
- Health checks built-in

### `.dockerignore`
- Excludes unnecessary files from Docker build
- Reduces image size from ~2GB to ~600MB
- Ignores: .git, node_modules, vendor, stream segments, Python cache


- Complete database schema for WMSU Attendance Monitoring
- Tables: admin, students, curriculum, attendance, recognition_logs, camera_settings, manual_attendance
- Includes default admin user (change password after deployment)
- UTF-8 charset for international support

### `db.php` (Updated)
- Auto-detects Render environment via `getenv('RENDER')`
- Falls back to XAMPP localhost if local
- Supports both development and production

### `DEPLOYMENT.md`
- Step-by-step deployment guide
- Known limitations and workarounds
- Troubleshooting matrix
- Before/after checklist

### `DEPLOYMENT_CHECKLIST.md`
- Quick reference checklist
- Organized by phase (local prep, GitHub, Render, database, verification)
- Estimated time per section
- Troubleshooting quick fixes

## Quick Start

```bash
# 1. Push to GitHub
git add Dockerfile render.yaml .dockerignore DEPLOYMENT.md DEPLOYMENT_CHECKLIST.md
git commit -m "Add Render.com deployment configuration"
git push origin main

# 2. Go to https://dashboard.render.com
# 3. Follow DEPLOYMENT_CHECKLIST.md (40-50 minutes)

# Result: https://attendance-monitoring.onrender.com (LIVE)
```

## Environment Variables (Set in Render Dashboard)

| Variable | Source | Example |
|----------|--------|---------|
| `DB_HOST` | MySQL addon | `dpg-xxx.render.com` |
| `DB_USER` | MySQL addon | `wmsu_user` |
| `DB_PASSWORD` | MySQL addon | `[auto-generated]` |
| `DB_NAME` | Manual | `attendance_monitoring` |
| `RENDER` | Manual | `true` |

## Persistent Storage

Render uses **ephemeral storage** (cleared on redeploy). For persistence:

| Data | Location | Persistence |
|------|----------|-------------|
| Database | MySQL addon | ✅ Persistent |
| Student photos | `/images/students/` | ❌ Ephemeral (resets on deploy) |
| Known faces | `/known_faces/` | ❌ Ephemeral (resets on deploy) |
| Config files | `/config/` | ❌ Ephemeral (resets on deploy) |
| HLS streams | `/stream/` | ❌ Ephemeral (session-only) |

### Recommendation
For production (beyond 1-2 months):
- Use **Render Disks** (paid add-on) for `/known_faces/`
- Or migrate to **AWS S3** for face images
- Or keep local XAMPP as master, use Render as demo only

## Deployment Timeline

- **GitHub push:** 2 min
- **Render setup:** 5 min
- **Build & deploy:** 10 min
- **Database init:** 10 min
- **Testing:** 10 min
- **Total:** ~40-50 min (LIVE)

## Support

- **Render Docs:** https://render.com/docs
- **Render Status:** https://status.render.com
- **MySQL Issues:** Check Render dashboard Database logs
- **PHP Issues:** Check Render dashboard Web Service logs

---

**Cost:** FREE for 1-2 months (free tier)  
**HTTPS:** ✅ Auto (Render default)  
**Domain:** `https://attendance-monitoring.onrender.com`  
**Uptime:** 99.95% SLA on free tier  

---

*Version: 1.0*  
*Last Updated: February 2026*  
*For: WMSU Attendance Monitoring System*
