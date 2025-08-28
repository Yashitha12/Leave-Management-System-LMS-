# Leave-Management-System-LMS

A web-based Leave Management System built with PHP, MySQL, HTML, CSS, and JavaScript.
It provides organizations with a streamlined way to manage employee leave applications, approvals, balances, and holidays.

Features

Role-based Access

Admin – Manage users, leave types, holidays, initialize balances

Manager – Approve/Reject leave requests for their department

Employee – Apply leave, view balances, track leave history

Leave Applications

Apply for annual, casual, sick, or custom leave types

Upload supporting documents

Approval Workflow

Managers and Admins can approve or reject requests

Automatic deduction from leave balances

Leave Balances

Yearly allocations, carry-over, and usage tracking

Holiday Management

Maintain company-wide holidays

Authentication & Sessions

Secure login with SHA-256 password hashing

Role-based dashboards

Self-Registration (optional)

Employees can register themselves (requires admin approval)

Technology Stack

Frontend: HTML, CSS, JavaScript

Backend: PHP (vanilla)

Database: MySQL (phpMyAdmin for management)

Session Handling: Native PHP sessions

Deployment: XAMPP / WAMP (Apache + MySQL)

Project Structure
lms/
├── config/
│   ├── app.php          # Global settings
│   └── db.php           # Database connection
├── lib/
│   ├── auth.php         # Authentication helpers
│   └── leave.php        # Leave balance functions
├── public/
│   ├── index.php        # Login page
│   ├── register.php     # Employee self-registration
│   ├── dashboard.php    # User dashboard
│   ├── balances.php     # View leave balances
│   ├── leave_history.php# Employee leave history
│   ├── apply_leave.php  # Apply for leave
│   ├── logout.php       # Logout
│   ├── assets/          # CSS/JS/images
│   └── admin/           # Admin-only pages
│       ├── _nav.php         # Admin navigation bar
│       ├── approvals.php    # Approve/Reject leaves
│       ├── balances_init.php# Initialize leave balances
│       ├── users.php        # Manage users
│       ├── leave_types.php  # Manage leave types
│       └── holidays.php     # Manage holidays
└── sql/
    └── schema.sql       # Database schema and seed data
