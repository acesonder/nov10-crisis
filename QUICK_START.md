# Crisis Management System - Quick Start Guide

Get your Crisis Management System up and running in minutes!

## 🚀 5-Minute Setup

### Prerequisites Checklist
- [ ] PHP 7.4+ installed
- [ ] MySQL/MariaDB running
- [ ] Web server (Apache/Nginx) configured
- [ ] Project files downloaded/cloned

### Step 1: Database (2 minutes)

```bash
# Create database
mysql -u root -p
CREATE DATABASE crisis_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Import schema
mysql -u root -p crisis_management < database/schema.sql
```

✅ **Verify:** Log into phpMyAdmin and confirm you see 20+ tables

### Step 2: Configure (1 minute)

Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Your MySQL username
define('DB_PASS', 'your_password'); // Your MySQL password
define('DB_NAME', 'crisis_management');
define('SITE_URL', 'http://localhost/nov10-crisis'); // Your URL
```

### Step 3: Permissions (1 minute)

```bash
chmod 777 uploads/
chmod 777 uploads/documents/
chmod 777 uploads/profiles/
```

### Step 4: Access (1 minute)

Open browser: `http://localhost/nov10-crisis/`

✅ **Success!** You should see the landing page

## 🔐 First Login

### Admin Access
1. Click "Admin Access" on homepage
2. Enter:
   - **Passcode:** `07977`
   - **Username:** `admin`
   - **Password:** `admin123`
3. **IMPORTANT:** Immediately go to Profile → Change Password

### Create Test Accounts

**Quick Client Account:**
1. Homepage → "Get Started"
2. Fill form, select "Client" role
3. Register and login

**Quick Staff Account (as admin):**
1. Admin Dashboard → Users → Add User
2. Set role to "Staff"
3. Save

## 📝 Initial Data Setup

### Add Service Providers (5 minutes)

Sample providers are already in the database:
- Hope Recovery Center (substance abuse)
- Safe Haven Shelter (emergency housing)
- New Beginnings (transitional housing)
- Community Health Clinic (healthcare)
- Job Training Center (employment)

**To add more:**
Admin Dashboard → Add Provider

### Add Resources (5 minutes)

**Option 1: SQL (Fast)**

```sql
-- Add beds
INSERT INTO beds (facility_name, bed_number, bed_type, status) VALUES
('Main Shelter', 'A101', 'Single', 'available'),
('Main Shelter', 'A102', 'Single', 'available'),
('Main Shelter', 'A103', 'Single', 'available');

-- Add shower slots (today)
INSERT INTO shower_slots (slot_name, slot_time, duration_minutes, status, booking_date) VALUES
('Shower 1', '08:00:00', 15, 'available', CURDATE()),
('Shower 1', '08:30:00', 15, 'available', CURDATE()),
('Shower 1', '09:00:00', 15, 'available', CURDATE()),
('Shower 2', '08:00:00', 15, 'available', CURDATE()),
('Shower 2', '08:30:00', 15, 'available', CURDATE());

-- Add laundry slots (today)
INSERT INTO laundry_slots (machine_number, slot_time, duration_minutes, status, booking_date) VALUES
('1', '09:00:00', 60, 'available', CURDATE()),
('1', '10:00:00', 60, 'available', CURDATE()),
('2', '09:00:00', 60, 'available', CURDATE()),
('2', '10:00:00', 60, 'available', CURDATE());
```

**Option 2: Admin Panel** (Coming in future updates)

## 🧪 Test the System (10 minutes)

### Test 1: Complete Assessment
1. Login as client
2. Dashboard → "New Assessment"
3. Choose "Substance Use Assessment"
4. Fill out some questions (all optional)
5. Submit
6. ✅ Check: Referrals created automatically

### Test 2: Book Resource
1. Logged in as client
2. Go to "Resources"
3. Find available shower slot
4. Click "Book"
5. ✅ Check: Appears in "My Bookings"

### Test 3: Send Message
1. Logged in as client
2. Go to "Messages"
3. Click "New Message"
4. Select staff member
5. Send message
6. ✅ Check: Message in "Sent" folder
7. Login as staff
8. ✅ Check: Message in staff inbox

### Test 4: Task Management
1. Login as staff
2. Create new task for client
3. Login as client
4. Go to "Tasks & Goals"
5. Update task status
6. ✅ Check: Status updated

### Test 5: Notifications
1. Perform any action (send message, complete task)
2. Wait 30 seconds
3. ✅ Check: Notification badge updates
4. Click notifications
5. ✅ Check: See new notification

## 🎨 Customize Your Instance

### Change Theme Colors

Edit `assets/css/main.css`:

```css
:root {
    --primary-color: #4a90e2;    /* Your brand color */
    --secondary-color: #7b68ee;  /* Accent color */
}
```

### Change Site Name

Edit `includes/config.php`:

```php
define('SITE_NAME', 'Your Organization Name');
```

Update `pages/` header files to use your organization name.

### Change Admin Passcode

1. Edit `includes/config.php`:
   ```php
   define('ADMIN_PASSCODE', 'YOUR_NEW_CODE'); // 5 digits
   ```

2. Update database:
   ```sql
   UPDATE admin_settings 
   SET setting_value = 'YOUR_NEW_CODE' 
   WHERE setting_key = 'admin_passcode';
   ```

### Customize Update Interval

Edit `assets/js/main.js`:

```javascript
const App = {
    updateInterval: 30000, // Change to your preference (milliseconds)
};
```

## 📱 Mobile Testing

1. Open on mobile device or resize browser
2. ✅ Check: Navigation collapses to hamburger menu
3. ✅ Check: Cards stack vertically
4. ✅ Check: Forms are touch-friendly
5. ✅ Check: All features accessible

## 🔧 Troubleshooting

### "Database connection failed"
**Fix:** Check `includes/config.php` credentials

### "Permission denied" on uploads
**Fix:** `chmod 777 uploads/` and subdirectories

### CSS/JS not loading
**Fix:** Check `SITE_URL` in `config.php` matches your actual URL

### AJAX not working
**Fix:** 
1. Check browser console (F12)
2. Verify you're logged in
3. Check PHP error log

### Session errors
**Fix:**
```bash
# Check PHP session directory
php -i | grep session.save_path
# Ensure it's writable
```

## 📊 Daily Operations Checklist

### Morning
- [ ] Check system status (Admin Dashboard)
- [ ] Review overnight incidents
- [ ] Check resource availability
- [ ] Respond to urgent messages

### During Day
- [ ] Process new assessments
- [ ] Review and act on referrals
- [ ] Update case notes
- [ ] Monitor resource bookings

### Evening
- [ ] Review day's activity log
- [ ] Backup database
- [ ] Plan next day's resources
- [ ] Close completed tasks

## 🎓 Training New Users

### For Clients (5 minutes)
1. Show registration process
2. Demonstrate assessment completion
3. Explain resource booking
4. Show how to send messages
5. Review task tracking

### For Staff (15 minutes)
1. Tour dashboard overview
2. Show case creation workflow
3. Demonstrate referral process
4. Explain task assignment
5. Review incident reporting
6. Show resource management

### For Providers (10 minutes)
1. Show referral inbox
2. Explain accept/reject process
3. Demonstrate messaging
4. Show capacity management
5. Review client list

### For Admins (20 minutes)
1. Complete system tour
2. User management
3. Provider management
4. System monitoring
5. Activity logs
6. Backup procedures

## 📈 Growth Roadmap

### Week 1
- [ ] Complete setup and testing
- [ ] Train initial staff
- [ ] Add service providers
- [ ] Configure resources

### Week 2
- [ ] Onboard first clients
- [ ] Test full workflow
- [ ] Gather feedback
- [ ] Make adjustments

### Month 1
- [ ] Review usage statistics
- [ ] Optimize workflows
- [ ] Add more providers
- [ ] Expand resources

### Ongoing
- [ ] Weekly database backups
- [ ] Monthly user review
- [ ] Quarterly system updates
- [ ] Annual security audit

## 🔐 Security Hardening (Production)

### Before Going Live:
1. ✅ Change admin password
2. ✅ Change admin passcode
3. ✅ Enable HTTPS
4. ✅ Disable error display
5. ✅ Set up regular backups
6. ✅ Configure firewall
7. ✅ Review user permissions
8. ✅ Test disaster recovery

### Production config.php:
```php
// Disable error display
error_reporting(0);
ini_set('display_errors', 0);

// Enable secure cookies (with HTTPS)
ini_set('session.cookie_secure', 1);
```

## 💡 Pro Tips

### Efficiency Tips
- Use keyboard shortcuts (Tab to navigate forms)
- Bookmark frequently used pages
- Set up browser notifications
- Use the search function in tables
- Bulk operations where available

### Best Practices
- Complete assessments thoroughly but respect optional questions
- Update task status promptly
- Respond to messages within 24 hours
- Keep case notes current
- Review notifications regularly

### Admin Tips
- Monitor activity logs for unusual patterns
- Keep providers' capacity updated
- Regularly backup database
- Test disaster recovery plan
- Keep documentation updated

## 📞 Getting Help

### Resources
- `README.md` - Complete documentation
- `SETUP_GUIDE.md` - Detailed setup instructions
- `FEATURES.md` - Feature overview
- Code comments - Inline documentation

### Support Channels
- GitHub Issues - Bug reports and feature requests
- System logs - Technical troubleshooting
- Activity logs - User action review
- Admin dashboard - System health monitoring

## ✅ Launch Checklist

### Pre-Launch
- [ ] Database configured and tested
- [ ] Admin password changed
- [ ] Admin passcode changed
- [ ] Service providers added
- [ ] Resources configured
- [ ] Staff accounts created
- [ ] System tested end-to-end
- [ ] Backups configured
- [ ] HTTPS enabled
- [ ] Security hardened

### Launch Day
- [ ] Final system test
- [ ] Staff briefing
- [ ] Monitor for issues
- [ ] Be available for support
- [ ] Document any issues
- [ ] Celebrate! 🎉

### Post-Launch (First Week)
- [ ] Daily check-ins with staff
- [ ] Monitor system performance
- [ ] Address user feedback
- [ ] Fine-tune workflows
- [ ] Update documentation
- [ ] Plan improvements

---

## 🎯 Success Metrics

Track these to measure success:
- Number of active clients
- Assessments completed
- Referrals made
- Resources utilized
- Messages sent
- Tasks completed
- User satisfaction

**You're now ready to make a difference!** 🌟

For detailed information, see:
- **Technical Setup:** `SETUP_GUIDE.md`
- **Full Documentation:** `README.md`  
- **Feature Details:** `FEATURES.md`
