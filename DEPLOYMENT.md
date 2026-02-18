# Render.com Deployment Guide for WMSU Attendance Monitoring System

## Prerequisites
1. GitHub account (for version control)
2. Render.com account (free tier)
3. Your attendance monitoring code in GitHub

## Step 1: Prepare Your Code

### 1.1 Push to GitHub
```bash
git init
git add .
git commit -m "Initial commit: WMSU Attendance Monitoring System"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/attendance-monitoring.git
git push -u origin main
```

### 1.2 Update Database Connection

Edit `db.php` to detect Render environment:
```php
<?php
if (getenv('RENDER') === 'true') {
    // Render.com environment
    $db_host = getenv('DB_HOST');
    $db_user = getenv('DB_USER');
    $db_password = getenv('DB_PASSWORD');
    $db_name = getenv('DB_NAME');
} else {
    // Local XAMPP environment
    $db_host = 'localhost';
    $db_user = 'root';
    $db_password = '';
    $db_name = 'attendance_monitoring';
}

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
```

## Step 2: Deploy to Render.com

### 2.1 Create New Service
1. Go to https://dashboard.render.com/
2. Click "New +"
3. Select "Web Service"
4. Connect your GitHub repository
5. Choose the main branch

### 2.2 Configure Deployment
- **Name:** `attendance-monitoring`
- **Region:** Singapore (or closest to you)
- **Branch:** main
- **Root Directory:** (leave empty)
- **Runtime:** Docker
- **Build Command:** (leave empty - uses Dockerfile)
- **Start Command:** (leave empty - uses Dockerfile)

### 2.3 Environment Variables
Add these in the Render dashboard:
```
DB_HOST = (auto from MySQL addon)
DB_USER = (auto from MySQL addon)
DB_PASSWORD = (auto from MySQL addon)
DB_NAME = attendance_monitoring
RENDER = true
```

### 2.4 Add MySQL Database
1. In Render dashboard, click "New +"
2. Select "MySQL"
3. Choose free tier
4. Render will auto-populate DB_HOST, DB_USER, DB_PASSWORD

## Step 3: After Deployment

### 3.1 Initialize Database
Once deployed, SSH into your service:
```bash
render shell attendance-monitoring
```

Run database setup:
```bash
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD -e "CREATE DATABASE attendance_monitoring;"
```

Then import your schema:
```bash
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD attendance_monitoring < attendance_db.sql
```

### 3.2 Access Your System
```
https://attendance-monitoring.onrender.com
```

## Step 4: Known Limitations & Workarounds

### Issue 1: Camera Access (HTTPS Required)
✅ **Solved:** Render provides auto-HTTPS via `.onrender.com` domain

### Issue 2: FFmpeg/HLS Streaming
✅ **Works:** FFmpeg runs in container, HLS segments saved to `/var/www/html/stream/`
⚠️ **Note:** Render uses ephemeral storage; segments persist within session

### Issue 3: Face Recognition Models
✅ **Works:** Models in `/models/` are loaded on startup
- Keep model files small (~50MB max)
- Consider caching in S3 if models exceed size

### Issue 4: Known Faces Storage
✅ **Works:** `/known_faces/` directory is preserved
- Render's ephemeral storage clears on redeploy
- For persistence: use Render Disks or AWS S3

## Step 5 (Optional): Add Persistent Storage

If you want known_faces to persist after redeployment:

### Option A: Render Disks (Paid)
### Option B: AWS S3 (Free tier available)
1. Create S3 bucket
2. Update file uploads to use S3
3. Update face loading to fetch from S3

## Common Issues & Fixes

| Issue | Solution |
|-------|----------|
| 504 Gateway Timeout | Render free tier has 30 min timeout; HLS streaming might exceed |
| Database connection fails | Check environment variables match MySQL addon |
| Camera won't stream | Ensure HTTPS is enforced; check CORS headers |
| Known faces not found | Faces uploaded to ephemeral storage; use persistent disk |
| Slow initial load | PHP cold start; normal on free tier |

## Monitoring

1. View logs: `render logs attendance-monitoring`
2. Check health: Render dashboard Status tab
3. Monitor database: MySQL admin in Render dashboard

## Before Going Live (1-2 Month Demo)

✅ Test all CRUD operations
✅ Verify camera streaming works over HTTPS
✅ Check attendance recording and reports
✅ Confirm section management persists
✅ Verify dashboard metrics load
✅ Test PDF export functionality

## After 1-2 Months (Paid Upgrade or Archive)

If happy with system:
- Upgrade to Render paid tier ($12+/month)
- Or keep local XAMPP for school LAN only
- Or migrate to AWS/DigitalOcean

## Support

For Render-specific issues: https://render.com/docs
For your code: Check logs in Render dashboard

---

**Last Updated:** February 2026
**System:** WMSU Attendance Monitoring v1.0
