# Crisis Management System

A comprehensive web-based application for managing crisis services, client assessments, resource allocation, and case management. Built with PHP, MySQL, HTML, CSS, and JavaScript.

## 🌟 Features

### User Roles
- **Clients** - Access assessments, track goals, book resources, communicate with staff
- **Staff** - Manage cases, coordinate services, track incidents, support clients
- **Service Providers** - Receive referrals, manage capacity, communicate with clients
- **Administrators** - System management, user administration, reporting

### Core Functionality

#### Smart Assessments
- **Substance Use Assessment** - Comprehensive evaluation with optional questions
- **Housing/Homelessness Assessment** - Housing needs and barriers evaluation
- **General Needs Assessment** - Overall support needs identification
- All questions are optional - clients only answer what they're comfortable with
- Auto-matching with appropriate service providers based on assessment results

#### Communication
- **Secure Messaging** - Internal messaging system between all users
- **Real-time Notifications** - AJAX-powered instant updates
- **Activity Tracking** - Complete audit log of system actions

#### Resource Management
- **Bed Management** - Track availability and reservations
- **Shower Scheduling** - Book time slots for shower access
- **Laundry Scheduling** - Reserve laundry machine time
- Real-time availability updates

#### Case Management
- Track client cases with priority levels
- Add case notes and updates
- Monitor case progress and outcomes
- Incident reporting and tracking

#### Service Coordination
- Automatic referrals to service providers
- Track referral status and outcomes
- Provider directory with capacity management
- Priority-based routing for urgent needs

### Design Features
- **Responsive Design** - Works on desktop, tablet, and mobile devices
- **Theme Customizer** - Light/dark mode toggle
- **CSS Animations** - Smooth transitions and engaging UI
- **Modern Interface** - Clean, intuitive user experience
- **Accessibility** - Designed with accessibility in mind

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Apache/Nginx web server
- Modern web browser (Chrome, Firefox, Safari, Edge)

## 🚀 Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/acesonder/nov10-crisis.git
cd nov10-crisis
```

### Step 2: Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE crisis_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p crisis_management < database/schema.sql
```

Or using phpMyAdmin:
- Open phpMyAdmin
- Select the `crisis_management` database
- Click "Import"
- Choose `database/schema.sql`
- Click "Go"

### Step 3: Configure the Application

Edit `includes/config.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'crisis_management');
```

### Step 4: Set File Permissions

```bash
chmod 755 includes/
chmod 755 assets/
chmod 777 uploads/
chmod 777 uploads/documents/
chmod 777 uploads/profiles/
```

### Step 5: Web Server Configuration

#### Apache
Create or update `.htaccess` in the root directory:

```apache
RewriteEngine On
RewriteBase /nov10-crisis/

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

### Step 6: Access the Application

Open your web browser and navigate to:
```
http://localhost/nov10-crisis/
```

## 🔐 Default Credentials

### Admin Account
- **Username:** admin
- **Password:** admin123
- **Admin Passcode:** 07977

**⚠️ IMPORTANT:** Change the default password immediately after first login!

## 📖 Quick Start Guide

### For Clients
1. Register on the homepage
2. Complete an assessment to get matched with services
3. Book resources (beds, showers, laundry)
4. Message staff for support
5. Track your goals and progress

### For Staff
1. Log in with staff credentials
2. Review client assessments
3. Create and manage cases
4. Make referrals to service providers
5. Monitor resource utilization

### For Administrators
1. Log in with admin passcode (07977)
2. Monitor system statistics
3. Manage users and service providers
4. Review activity logs
5. Generate reports

## 🛠️ Configuration

### Theme Customization
Modify CSS variables in `assets/css/main.css`:

```css
:root {
    --primary-color: #4a90e2;
    --secondary-color: #7b68ee;
}
```

### Update Intervals
Edit `assets/js/main.js`:

```javascript
const App = {
    updateInterval: 30000, // 30 seconds
};
```

## 🔒 Security

- All inputs are sanitized
- Passwords are hashed with bcrypt
- CSRF protection on forms
- Session security enabled
- Activity logging for auditing

## 📊 Database Structure

Key tables:
- `users` - User accounts and authentication
- `assessments` - Client assessment data
- `cases` - Case management records
- `referrals` - Service provider referrals
- `tasks` - Client goals and tasks
- `messages` - Internal messaging
- `notifications` - Real-time notifications
- `beds`, `shower_slots`, `laundry_slots` - Resource management

## 📝 Project Structure

```
nov10-crisis/
├── assets/          # CSS, JS, images
├── database/        # SQL schema
├── includes/        # PHP includes and AJAX
├── pages/           # Application pages
│   ├── admin/       # Admin pages
│   ├── client/      # Client pages
│   ├── staff/       # Staff pages
│   ├── provider/    # Provider pages
│   └── common/      # Shared pages
├── uploads/         # File uploads
└── index.php        # Landing page
```

## 🐛 Troubleshooting

- **Database errors:** Check credentials in `includes/config.php`
- **Upload errors:** Verify directory permissions
- **Session errors:** Check PHP session configuration
- **AJAX errors:** Check browser console for details

## 📄 License

Created for crisis management and community support services.

---

**Note:** This system handles sensitive information. Follow privacy laws and data protection best practices.