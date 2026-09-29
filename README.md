# Course Enrollment System

A complete PHP and MySQL web application for managing student course enrollments, applications, document uploads, and payments.

## Features

### Student Features
- User registration and authentication
- Browse course catalogue
- Apply for courses with document upload
- Two-stage payment process (registration fee + programme fee)
- Personal dashboard with application tracking
- Real-time progress updates
- Email notifications

### Admin Features
- Secure admin portal
- Application management dashboard
- Payment verification system
- Document review
- User management
- Statistics and reporting

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (Apache)

### Setup Instructions

1. **Extract Files**
   - Extract all files to your web server directory
   - For XAMPP/WAMP: `htdocs/course-enrollment/`
   - For production: `/var/www/html/course-enrollment/`

2. **Database Setup**
   ```bash
   # Import the database schema
   mysql -u root -p < database.sql
   ```
   
   Or manually:
   - Open phpMyAdmin
   - Create a new database named `course_enrollment`
   - Import the `database.sql` file

3. **Configure Database Connection**
   
   Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');          // Your MySQL username
   define('DB_PASS', '');              // Your MySQL password
   define('DB_NAME', 'course_enrollment');
   ```

4. **Configure Site URL**
   
   Edit `config/database.php`:
   ```php
   define('SITE_URL', 'http://localhost/course-enrollment');
   ```

5. **Set File Permissions**
   ```bash
   chmod 755 -R .
   chmod 777 -R assets/uploads
   ```

6. **Configure Email (Optional)**
   
   For email notifications, update SMTP settings in `config/database.php`:
   ```php
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USER', 'your-email@gmail.com');
   define('SMTP_PASS', 'your-app-password');
   ```

7. **Access the Application**
   - Main Site: `http://localhost/course-enrollment/`
   - Admin Portal: `http://localhost/course-enrollment/admin/`

## Default Credentials

### Admin Login
- **Username:** admin
- **Password:** admin123
- **URL:** http://localhost/course-enrollment/admin/

### Test Student Account
Create a new account through the registration page or use these credentials if you create them:
- **Email:** student@test.com
- **Password:** password123

## Directory Structure

```
course-enrollment/
├── admin/                      # Admin portal
│   ├── dashboard.php          # Admin dashboard
│   ├── index.php              # Admin login
│   └── logout.php             # Admin logout
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   ├── js/
│   │   └── main.js            # JavaScript functions
│   └── uploads/
│       └── documents/         # Uploaded files (auto-created)
├── config/
│   └── database.php           # Database configuration
├── includes/
│   └── functions.php          # Helper functions
├── index.php                  # Landing page
├── register.php               # Student registration
├── login.php                  # Student login
├── logout.php                 # Student logout
├── dashboard.php              # Student dashboard
├── courses.php                # Course listing
├── apply.php                  # Course application form
├── payment.php                # Payment processing
└── database.sql               # Database schema
```

## Usage Guide

### For Students

1. **Registration**
   - Visit the homepage and click "Get Started" or "Sign Up"
   - Fill in personal details
   - Submit to create account

2. **Browse Courses**
   - Navigate to "Courses" in the menu
   - View available courses with details
   - Click "View Details & Apply" on desired course

3. **Apply for Course**
   - Complete application form with personal and academic details
   - Upload required documents:
     - University Transcript
     - Personal Statement
     - Proof of Identity
   - Submit application

4. **Payment Process**
   - Stage 1: Pay registration fee (£15,000)
   - Upload payment screenshot for verification
   - Wait for admin confirmation (24-48 hours)
   - Stage 2: Pay programme fee
   - Upload payment screenshot
   - Await final confirmation

5. **Track Progress**
   - View application status on dashboard
   - Monitor payment verification
   - Access course materials once enrolled

### For Administrators

1. **Login**
   - Access admin portal at `/admin/`
   - Enter admin credentials

2. **Dashboard Overview**
   - View total applications, users, enrollments
   - See pending payment verifications
   - Monitor system statistics

3. **Verify Payments**
   - Review payment screenshots
   - Click "View Screenshot" to examine evidence
   - Click "Confirm Payment" to approve
   - System automatically updates application status

4. **Manage Applications**
   - View all applications in table format
   - Check document upload status
   - Monitor payment status
   - View detailed application information

## Security Features

- Password hashing using PHP's password_hash()
- SQL injection prevention with PDO prepared statements
- XSS protection with htmlspecialchars()
- CSRF protection via session management
- Secure file upload validation
- Admin-only access control
- Session-based authentication

## Customization

### Changing Colors
Edit `assets/css/style.css`:
```css
:root {
    --primary-orange: #FF8C42;    /* Main brand color */
    --primary-dark: #2C3E50;      /* Dark theme color */
    --secondary-blue: #3498DB;    /* Accent color */
}
```

### Adding Courses
1. Login to admin portal
2. Navigate to database
3. Insert into `courses` table:
```sql
INSERT INTO courses (title, description, registration_fee, programme_fee, duration, start_date)
VALUES ('Your Course', 'Description', 15000.00, 35000.00, '6 months', '2026-03-01');
```

### Email Templates
Edit email messages in:
- `register.php` (Welcome email)
- `payment.php` (Payment confirmation)
- Admin notification emails

## Troubleshooting

### Database Connection Error
- Check MySQL is running
- Verify database credentials in `config/database.php`
- Ensure database `course_enrollment` exists

### File Upload Errors
- Check `assets/uploads/documents/` directory exists
- Verify directory has write permissions (777)
- Check PHP `upload_max_filesize` in php.ini

### Email Not Sending
- Configure SMTP settings correctly
- For Gmail, use App Password, not regular password
- Check firewall allows SMTP port (587/465)

### Cannot Access Admin Panel
- Clear browser cookies/cache
- Verify admin account exists in database
- Check session settings in `config/database.php`

## Development

### Adding New Features
1. Create new PHP file
2. Include database and functions:
   ```php
   require_once 'config/database.php';
   require_once 'includes/functions.php';
   ```
3. Add navigation links in appropriate files
4. Update database schema if needed

### Database Backup
```bash
mysqldump -u root -p course_enrollment > backup.sql
```

## Production Deployment

1. **Change Credentials**
   - Update all default passwords
   - Change admin credentials in database
   - Use strong passwords

2. **Enable HTTPS**
   - Obtain SSL certificate
   - Update SITE_URL to https://
   - Set session.cookie_secure = 1

3. **Secure Uploads**
   - Move uploads outside web root
   - Implement virus scanning
   - Add file type validation

4. **Performance**
   - Enable OPcache
   - Implement caching
   - Optimize database queries
   - Use CDN for static assets

5. **Monitoring**
   - Set up error logging
   - Implement backup schedule
   - Monitor disk space for uploads

## Support

For issues or questions:
- Check this README
- Review code comments
- Contact: info@wardiere.com

## License

Copyright © 2026 Indo-Euro Synchronization Pvt Ltd
All rights reserved.

---

Built with ❤️ using PHP & MySQL
