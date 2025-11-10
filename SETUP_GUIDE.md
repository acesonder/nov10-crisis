# Crisis Management System - Complete Setup Guide

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Installation Steps](#installation-steps)
3. [Initial Configuration](#initial-configuration)
4. [First-Time Setup](#first-time-setup)
5. [Testing the System](#testing-the-system)
6. [Common Issues](#common-issues)

## System Requirements

### Server Requirements
- **PHP**: Version 7.4 or higher
  - Required extensions: mysqli, json, session, fileinfo
- **MySQL/MariaDB**: Version 5.7+ / 10.2+
- **Web Server**: Apache 2.4+ or Nginx 1.14+
- **Disk Space**: Minimum 500MB (more for file uploads)

### Client Requirements
- Modern web browser (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- JavaScript enabled
- Cookies enabled

## Installation Steps

### Step 1: Download and Extract Files

```bash
# Clone from GitHub
git clone https://github.com/acesonder/nov10-crisis.git

# Or download and extract ZIP file
cd /var/www/html/
unzip nov10-crisis.zip
cd nov10-crisis
```

### Step 2: Set Up Database

#### Option A: Using MySQL Command Line

```bash
# Log into MySQL
mysql -u root -p

# Create database
CREATE DATABASE crisis_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user (optional but recommended)
CREATE USER 'crisis_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON crisis_management.* TO 'crisis_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u root -p crisis_management < database/schema.sql
```

#### Option B: Using phpMyAdmin

1. Open phpMyAdmin in your browser
2. Click "New" to create a new database
3. Name: `crisis_management`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"
6. Select the `crisis_management` database
7. Click "Import" tab
8. Choose file: `database/schema.sql`
9. Click "Go"

### Step 3: Configure Application

Edit `includes/config.php`:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');          // Usually 'localhost'
define('DB_USER', 'crisis_user');        // Your database username
define('DB_PASS', 'your_secure_password'); // Your database password
define('DB_NAME', 'crisis_management');  // Database name

// Site Configuration
define('SITE_NAME', 'Crisis Management System');
define('SITE_URL', 'http://your-domain.com/nov10-crisis'); // Update with your URL

// Admin Passcode (change for security!)
define('ADMIN_PASSCODE', '07977'); // Change this!

// File Uploads
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB
?>
```

### Step 4: Set File Permissions

#### On Linux/Mac:

```bash
# Navigate to project directory
cd /var/www/html/nov10-crisis

# Set directory permissions
chmod 755 includes/
chmod 755 assets/
chmod 755 pages/
chmod 755 database/

# Set upload directory permissions (writable)
chmod 777 uploads/
chmod 777 uploads/documents/
chmod 777 uploads/profiles/

# Create uploads subdirectories if they don't exist
mkdir -p uploads/documents
mkdir -p uploads/profiles
chmod 777 uploads/documents
chmod 777 uploads/profiles
```

#### On Windows (XAMPP/WAMP):

- Right-click on `uploads` folder → Properties → Security
- Edit permissions to allow "Full Control" for the web server user
- Apply to all subfolders

### Step 5: Web Server Configuration

#### Apache Configuration

Create/edit `.htaccess` in project root:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /nov10-crisis/
    
    # Force HTTPS (enable in production)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Prevent directory browsing
    Options -Indexes
    
    # Security headers
    <IfModule mod_headers.c>
        Header set X-Content-Type-Options "nosniff"
        Header set X-Frame-Options "SAMEORIGIN"
        Header set X-XSS-Protection "1; mode=block"
        Header set Referrer-Policy "strict-origin-when-cross-origin"
    </IfModule>
</IfModule>

# Protect sensitive files
<FilesMatch "(config\.php|\.sql)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

Enable required Apache modules:

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo service apache2 restart
```

#### Nginx Configuration

Add to your Nginx server block (`/etc/nginx/sites-available/default`):

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/nov10-crisis;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /uploads {
        alias /var/www/html/nov10-crisis/uploads;
    }

    # Security: Deny access to sensitive files
    location ~ /(config\.php|\.sql) {
        deny all;
    }

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

Restart Nginx:

```bash
sudo nginx -t  # Test configuration
sudo service nginx restart
```

## Initial Configuration

### Security Checklist

1. **Change Default Password**
   - Login as admin (username: `admin`, password: `admin123`)
   - Go to Profile → Change Password
   - Set a strong password

2. **Change Admin Passcode**
   - Edit `includes/config.php`
   - Change `ADMIN_PASSCODE` from '07977' to a secure 5-digit code
   - Update database: `UPDATE admin_settings SET setting_value = 'NEW_CODE' WHERE setting_key = 'admin_passcode';`

3. **Enable HTTPS** (Production)
   - Obtain SSL certificate (Let's Encrypt recommended)
   - Update `.htaccess` or Nginx config to force HTTPS
   - Update `SITE_URL` in `includes/config.php`

4. **Configure Error Reporting**
   - In production, set in `includes/config.php`:
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

### Initial Data Setup

1. **Add Service Providers**
   - Login as admin
   - Go to Admin Dashboard → Add Provider
   - Add organizations and their services
   - Set capacity and available slots

2. **Create Staff Accounts**
   - Admin Dashboard → Add User
   - Create accounts for case managers and staff
   - Assign appropriate roles

3. **Add Sample Data** (Optional)
   - Create test client account
   - Complete sample assessments
   - Test referral workflow

## First-Time Setup

### Access the System

1. Open web browser
2. Navigate to: `http://localhost/nov10-crisis/` (or your domain)
3. You should see the landing page

### Admin Login

1. Click "Admin Access" button
2. Enter:
   - **Passcode**: 07977 (or your changed code)
   - **Username**: admin
   - **Password**: admin123
3. Click "Admin Sign In"
4. **IMMEDIATELY** go to Profile and change the password

### Create Test Accounts

#### Staff Account
1. Admin Dashboard → Users → Add User
2. Fill in details:
   - Username: staff_test
   - Email: staff@test.com
   - Role: Staff
   - Password: test123
3. Save

#### Client Account
Option 1: Register from homepage
- Click "Get Started"
- Fill registration form
- Select "Client" role

Option 2: Admin creates account
- Admin Dashboard → Users → Add User
- Set role as "Client"

### Configure Resources

#### Add Beds
```sql
-- Run in phpMyAdmin or MySQL:
INSERT INTO beds (facility_name, bed_number, bed_type, status) VALUES
('Main Shelter', 'A101', 'Single', 'available'),
('Main Shelter', 'A102', 'Single', 'available'),
('Main Shelter', 'B201', 'Bunk', 'available');
```

#### Add Shower Slots
```sql
INSERT INTO shower_slots (slot_name, slot_time, duration_minutes, status, booking_date) VALUES
('Shower 1', '08:00:00', 15, 'available', CURDATE()),
('Shower 1', '08:30:00', 15, 'available', CURDATE()),
('Shower 2', '08:00:00', 15, 'available', CURDATE());
```

#### Add Laundry Slots
```sql
INSERT INTO laundry_slots (machine_number, slot_time, duration_minutes, status, booking_date) VALUES
('1', '09:00:00', 60, 'available', CURDATE()),
('1', '10:00:00', 60, 'available', CURDATE()),
('2', '09:00:00', 60, 'available', CURDATE());
```

## Testing the System

### Test Workflow

1. **Client Registration & Assessment**
   - Register as new client
   - Complete substance use assessment
   - Verify auto-referrals are created
   - Check that notifications are sent

2. **Resource Booking**
   - Book a shower slot
   - Book a laundry slot
   - Verify bookings appear in "My Bookings"

3. **Messaging**
   - Send message to staff
   - Login as staff
   - Verify message received
   - Reply to client

4. **Task Management**
   - Login as staff
   - Create task for client
   - Login as client
   - Update task status
   - Verify updates

5. **Case Management**
   - Create case as staff
   - Add case notes
   - Track progress
   - Close case

### Verify Core Functions

```bash
# Check database connection
php -r "require 'includes/config.php'; echo 'Database: ' . (isset($conn) ? 'Connected' : 'Failed');"

# Check file permissions
ls -la uploads/

# Test PHP version
php -v

# Check MySQL connection
mysql -u crisis_user -p -e "USE crisis_management; SHOW TABLES;"
```

## Common Issues

### Issue: Database Connection Failed

**Solutions:**
1. Verify database credentials in `includes/config.php`
2. Check MySQL service is running: `sudo service mysql status`
3. Test connection: `mysql -u crisis_user -p`
4. Verify database exists: `SHOW DATABASES;`

### Issue: File Upload Errors

**Solutions:**
1. Check directory permissions: `ls -la uploads/`
2. Verify PHP upload settings in `php.ini`:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```
3. Restart web server after changes

### Issue: Session Errors

**Solutions:**
1. Check session directory permissions
2. Verify session.save_path in `php.ini`
3. Clear browser cookies
4. Check PHP session settings:
   ```bash
   php -i | grep session
   ```

### Issue: 404 Errors on Pages

**Solutions:**
1. Verify `.htaccess` exists (Apache)
2. Check mod_rewrite is enabled: `sudo a2enmod rewrite`
3. Verify RewriteBase matches your path
4. Check Nginx configuration

### Issue: CSS/JS Not Loading

**Solutions:**
1. Check file paths in browser console (F12)
2. Verify `SITE_URL` in config.php
3. Check file permissions on assets directory
4. Clear browser cache

### Issue: AJAX Requests Failing

**Solutions:**
1. Check browser console for errors
2. Verify user is logged in
3. Check AJAX endpoint paths
4. Verify PHP session is active

### Issue: Slow Performance

**Solutions:**
1. Enable PHP OPcache
2. Optimize MySQL queries
3. Add database indexes
4. Enable compression in web server
5. Optimize images and assets

## Maintenance

### Regular Backups

```bash
# Daily database backup
mysqldump -u crisis_user -p crisis_management > backup_$(date +%Y%m%d).sql

# Weekly full backup
tar -czf backup_$(date +%Y%m%d).tar.gz /var/www/html/nov10-crisis
```

### Update Application

```bash
cd /var/www/html/nov10-crisis
git pull origin main
# Or download new version and extract

# Check for database updates
mysql -u crisis_user -p crisis_management < database/updates.sql
```

### Monitor Logs

```bash
# Apache error log
tail -f /var/log/apache2/error.log

# Nginx error log
tail -f /var/log/nginx/error.log

# PHP error log
tail -f /var/log/php_errors.log
```

## Support

For additional help:
- Check README.md for feature documentation
- Review code comments in PHP files
- Contact system administrator
- Open issue on GitHub repository

---

**Installation Complete!** 🎉

Your Crisis Management System is now ready to use. Don't forget to:
- ✅ Change default admin password
- ✅ Update admin passcode
- ✅ Configure service providers
- ✅ Set up regular backups
- ✅ Enable HTTPS in production
