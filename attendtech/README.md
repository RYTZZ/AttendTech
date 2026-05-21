# AttendTech — Automated Online Attendance Monitoring System

**Bicol University Gubat Campus | Academic Year 2025–2026**

A complete PHP-based automated attendance system using QR code technology for secondary high school students.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ with PDO |
| Database | MySQL (via XAMPP) |
| Frontend | HTML5, CSS3, Bootstrap 5 |
| Scanner | html5-qrcode v2.3.8 |
| Charts | Chart.js v4 |
| Tables | DataTables 1.13 |
| Alerts | SweetAlert2 |
| Fonts | DM Sans + Space Grotesk |

---

## Setup Instructions

### 1. Requirements
- XAMPP (PHP 8.0+, MySQL 5.7+, Apache)
- Modern browser with camera support (Chrome recommended)

### 2. Installation

**Step 1** — Copy the `attendance-system` folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\attendance-system\
```

**Step 2** — Start **Apache** and **MySQL** in XAMPP Control Panel.

**Step 3** — Import the database:
1. Open `http://localhost/phpmyadmin`
2. Create a new database named `attendance_system`
3. Click **Import** and upload `database/attendance_system.sql`
4. Click **Go**

**Step 4** — Configure database (if needed):
- Open `config/database.php`
- Set your MySQL username/password if different from defaults (`root` / empty password)

**Step 5** — Access the system:
```
http://localhost/attendance-system/
```

---

## Default Login

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `password` |

> **Important:** Change the password immediately after first login via **Settings → Change Password**.

---

## File Structure

```
attendance-system/
├── assets/
│   ├── css/style.css           # Main stylesheet
│   ├── js/
│   │   ├── app.js              # Core JS (sidebar, clock, notifications)
│   │   ├── scanner.js          # QR scanning logic
│   │   └── charts.js           # Chart.js initializers
│   ├── uploads/                # Student photos (auto-created)
│   ├── qr_codes/               # Generated QR codes (auto-created)
│   └── images/                 # System logo
│
├── config/
│   └── database.php            # DB credentials + PDO factory
│
├── includes/
│   ├── auth.php                # Login/logout/session guards
│   ├── functions.php           # Shared helper functions
│   ├── header.php              # HTML head + scripts
│   ├── navbar.php              # Top navigation bar
│   ├── sidebar.php             # Left sidebar menu
│   ├── footer.php              # JS includes + closing tags
│   └── session.php             # PHP session initializer
│
├── admin/
│   ├── dashboard.php           # Main dashboard with stats & charts
│   ├── students.php            # Student list with filters
│   ├── add_student.php         # Add student form
│   ├── edit_student.php        # Edit student form
│   ├── scanner.php             # QR camera scanner
│   ├── attendance.php          # Daily attendance records
│   ├── reports.php             # Weekly & monthly reports
│   ├── notifications.php       # Notification log
│   ├── settings.php            # System configuration
│   └── print_id.php            # Student QR ID card printer
│
├── ajax/
│   ├── scan_qr.php             # QR scan processor (Time In/Out logic)
│   ├── fetch_dashboard.php     # Live dashboard data
│   ├── fetch_attendance.php    # Attendance records AJAX
│   ├── save_student.php        # Create student AJAX
│   ├── update_student.php      # Update student AJAX
│   ├── delete_student.php      # Delete student AJAX
│   ├── notifications.php       # Notification fetch/mark-read
│   ├── export_excel.php        # CSV export (daily/weekly/monthly)
│   └── export_pdf.php          # Printable PDF report
│
├── database/
│   └── attendance_system.sql   # Full database schema + seed data
│
├── index.php                   # Redirect to dashboard
├── login.php                   # Admin login page
├── logout.php                  # Session destroy + redirect
├── .htaccess                   # Apache security rules
└── README.md                   # This file
```

---

## Features

### QR Scanner Logic
| Scan | Action |
|---|---|
| 1st scan | Records **Time In** |
| 2nd scan | Records **Time Out** |
| 3rd scan | **Rejected** — duplicate |
| Scan after late threshold | Marked **Late** |

### Attendance Statuses
- **Present** — Scanned on time
- **Late** — Scanned after the configured late threshold (default: 7:30 AM)
- **Absent** — Not scanned at all

### Exports
- **CSV/Excel** — Daily, Weekly, Monthly attendance exports
- **PDF** — Printable daily attendance report with summary stats

---

## Configurable Settings (via admin panel)

- School name and address
- Academic year
- **Late time threshold** (time after which students are marked late)
- System logo
- Timezone
- Admin password

---

## Security Features

- PHP session-based authentication
- Password hashing with `password_hash()` (bcrypt)
- PDO prepared statements (SQL injection prevention)
- Session regeneration on login
- AJAX endpoint protection (session check)
- File upload type and size validation
- `.htaccess` directory listing disabled

---

## Browser Requirements

- Google Chrome 60+ (recommended for camera access)
- Mozilla Firefox 60+
- Microsoft Edge 79+
- HTTPS required for camera access on non-localhost deployments

---

## Notes for Production Deployment

1. Set `DB_PASS` to a strong password in `config/database.php`
2. Change the default admin password immediately
3. Use HTTPS — QR scanning requires camera permissions which browsers block on HTTP (except localhost)
4. Set proper folder permissions: `assets/uploads/` and `assets/qr_codes/` should be writable (`chmod 755`)
5. Update `.htaccess` `RewriteBase` to match your subdirectory path

---

*AttendTech v1.0 — Built for Bicol University Gubat Campus*
