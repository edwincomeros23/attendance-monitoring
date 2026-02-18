# Render.com Deployment Checklist

## Local Preparation (Before GitHub Push)

### Step 1: Database Configuration
- [ ] Locate `db.php` in project root
- [ ] Verify it now detects Render environment via `getenv('RENDER')`
- [ ] Confirm it falls back to XAMPP localhost if not in Render

### Step 2: File Structure Check
- [ ] `/models/` contains face recognition models (should exist)
- [ ] `/known_faces/` exists (structure: S_no{ID}_{NAME}/)
- [ ] `/stream/` exists for HLS segments
- [ ] `/images/students/` exists for student photos
- [ ] `.dockerignore` is present (excludes storage files)

### Step 3: Authentication
- [ ] `auth.php` and `auth/` folder exist
- [ ] `index.php` guards with session checks
- [ ] Admin login works in local XAMPP

---

## GitHub Setup (5 minutes)

### Step 4: Create GitHub Repository
```bash
cd c:\xampp\htdocs\attendance-monitoring
git init
git add .
git commit -m "Initial commit: WMSU Attendance Monitoring v1.0"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/attendance-monitoring.git
git push -u origin main
```

### Step 5: Verify GitHub Push
- [ ] Go to https://github.com/YOUR_USERNAME/attendance-monitoring
- [ ] Confirm main branch has all files
- [ ] Verify Dockerfile and render.yaml are present

---

## Render.com Setup (15 minutes)

### Step 6: Create Render Account
- [ ] Sign up at https://render.com (free tier)
- [ ] Verify email
- [ ] No credit card needed for free tier

### Step 7: Connect GitHub to Render
1. Login to https://dashboard.render.com
2. Click "New +" → "Web Service"
3. Select "Connect a repository"
4. Choose `attendance-monitoring` repository
5. Click "Install" if prompted for GitHub integration

### Step 8: Configure Web Service
- **Name:** `attendance-monitoring`
- **Environment:** `Docker`
- **Region:** Singapore (or closest to you)
- **Branch:** `main`
- **Build Command:** (leave empty)
- **Start Command:** (leave empty)
- **Plan:** Free

### Step 9: Add MySQL Database
1. Click "New +" → "MySQL"
2. Enter details:
   - **Name:** `mysql`
   - **Database:** `attendance_monitoring`
   - **Username:** Auto-generated
   - **Password:** Auto-generated
   - **Region:** Same as Web Service
   - **Plan:** Free (1 month)

### Step 10: Configure MySQL in Web Service
Render auto-links MySQL. Verify in Web Service "Environment" tab:
- [ ] `DB_HOST` is set (looks like `dpg-xxx.render.com`)
- [ ] `DB_USER` is set
- [ ] `DB_PASSWORD` is set
- [ ] `DB_NAME` = `attendance_monitoring`

---

## Database Initialization (10 minutes)

### Step 11: Connect to MySQL
```bash
# Via terminal/PowerShell
mysql -h dpg-xxx.render.com -u [DB_USER] -p [DB_PASSWORD] -e "USE attendance_monitoring; SHOW TABLES;"
```
Or use Render dashboard MySQL admin.

### Step 12: Import Schema
```bash
mysql -h dpg-xxx.render.com -u [DB_USER] -p [DB_PASSWORD] attendance_monitoring < attendance_db.sql
```

### Step 13: Verify Database
- [ ] Run from MySQL CLI: `SHOW TABLES;`
- [ ] Should see: admin, students, curriculum, attendance, recognition_logs, camera_settings, manual_attendance

### Step 14: Create Admin User
```sql
INSERT INTO admin (username, email, password) VALUES 
('admin', 'your_email@wmsu.edu.ph', PASSWORD('your_secure_password'));
```

---

## Deployment Verification (10 minutes)

### Step 15: Wait for Deployment
- [ ] Go to Render dashboard
- [ ] Check Web Service build status
- [ ] Wait for "Live" status (usually 5-10 minutes)
- [ ] Note deployment URL: `https://attendance-monitoring.onrender.com`

### Step 16: Test Website
1. Open browser: `https://attendance-monitoring.onrender.com`
2. Should see login page
3. Click "Sign Up" or "Sign In"
4. Test with:
   - **Username:** `admin`
   - **Password:** `[from Step 14]`

### Step 17: Test Core Features
- [ ] Login successful
- [ ] Dashboard loads without errors
- [ ] Navigation sidebar appears
- [ ] Database connectivity confirmed (no "Connection failed" errors)
- [ ] Check Render logs: `render logs` or dashboard Logs tab

---

## Troubleshooting

| Error | Fix |
|-------|-----|
| "Database connection failed" | Verify DB_HOST, DB_USER, DB_PASSWORD in Render env vars |
| "Page not found (404)" | Check URL is `https://` (not `http://`) |
| Blank white page | Check Render logs for PHP errors |
| HLS stream not loading | FFmpeg should be installed; check Docker logs |
| Slow initial load | Render free tier has cold starts; normal on first request |

---

## After Successful Deployment

### Keep System Running 1-2 Months
- Free Render tier lasts 1-2 months per month
- No credit card charge
- No inactivity sleep (unlike Railway)

### Before 1-2 Months Expire
- **Option A:** Upgrade to Paid ($7+/month)
- **Option B:** Archive and keep local XAMPP only
- **Option C:** Redeploy to DigitalOcean ($5/month)

---

## Render Dashboard Links

| Item | Link |
|------|------|
| Web Service Logs | https://dashboard.render.com (Web Service) |
| MySQL Management | https://dashboard.render.com (Database) |
| Health Status | https://dashboard.render.com (Status) |
| Environment Variables | https://dashboard.render.com (Environment) |

---

**Total Time:** ~40-50 minutes
**Cost:** FREE for 1-2 months
**Next:** Monitor logs daily for errors

---

*Last Updated: February 2026*
*System: WMSU Attendance Monitoring v1.0*
