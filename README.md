# Leave Management System (LMS)

A web-based Leave Management System built with **PHP, MySQL, HTML, CSS, and JavaScript**.  
It provides organizations with a streamlined way to manage employee leave applications, approvals, balances, and holidays.

---

## Features

- **Role-based Access**
  - **Admin** – Manage users, leave types, holidays, initialize balances
  - **Manager** – Approve/Reject leave requests for their department
  - **Employee** – Apply leave, view balances, track leave history
- **Leave Applications**
  - Apply for annual, casual, sick, or custom leave types
  - Upload supporting documents
- **Approval Workflow**
  - Managers and Admins can approve or reject requests
  - Automatic deduction from leave balances
- **Leave Balances**
  - Yearly allocations, carry-over, and usage tracking
- **Holiday Management**
  - Maintain company-wide holidays
- **Authentication & Sessions**
  - Secure login with SHA-256 password hashing
  - Role-based dashboards
- **Self-Registration (optional)**
  - Employees can register themselves (requires admin approval)

---

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript  
- **Backend**: PHP (vanilla)  
- **Database**: MySQL (phpMyAdmin for management)  
- **Session Handling**: Native PHP sessions  
- **Deployment**: XAMPP / WAMP (Apache + MySQL)

---

## Installation

### 1. Clone the repository
### 2.Setup Database
### 3.Configure Application
### 4.Run the Application

## Default Accounts

### 1.Admin → admin@example.com / admin123
### 2.manager@example.com / manager123
### 3.emp1@example.com / emp123

## File Structure

lms/
├── config/
│   ├── app.php            # Global app settings
│   └── db.php             # Database connection
├── lib/
│   ├── auth.php           # Authentication helpers (login, logout, session)
│   └── leave.php          # Leave balance functions
├── public/
│   ├── index.php          # Login page
│   ├── register.php       # Employee self-registration
│   ├── dashboard.php      # User dashboard
│   ├── balances.php       # View leave balances
│   ├── leave_history.php  # Employee leave history
│   ├── apply_leave.php    # Apply for leave
│   ├── logout.php         # Logout
│   ├── assets/            # CSS, JS, images
│   └── admin/             # Admin-only pages
│       ├── _nav.php           # Admin navigation bar
│       ├── approvals.php      # Approve/Reject leave requests
│       ├── balances_init.php  # Initialize leave balances
│       ├── users.php          # Manage users
│       ├── leave_types.php    # Manage leave types
│       └── holidays.php       # Manage holidays
└── sql/
    └── schema.sql         # Database schema and seed data


## Database Schema (schema.sql)

-- Database: lms_db

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  emp_no VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('ADMIN','MANAGER','EMPLOYEE') NOT NULL DEFAULT 'EMPLOYEE',
  dept_id INT DEFAULT NULL,
  status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE leave_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(10) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  max_days_per_year FLOAT NOT NULL DEFAULT 0
);

CREATE TABLE holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  holiday_date DATE NOT NULL,
  description VARCHAR(200) NOT NULL
);

CREATE TABLE leave_applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type_id INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  days FLOAT NOT NULL,
  reason TEXT,
  attachment_path VARCHAR(255),
  status ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  approver_id INT DEFAULT NULL,
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  decided_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (type_id) REFERENCES leave_types(id),
  FOREIGN KEY (approver_id) REFERENCES users(id)
);

CREATE TABLE leave_balances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type_id INT NOT NULL,
  year INT NOT NULL,
  allocated FLOAT NOT NULL DEFAULT 0,
  carried_over FLOAT NOT NULL DEFAULT 0,
  used FLOAT NOT NULL DEFAULT 0,
  remaining FLOAT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_balance (user_id, type_id, year),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (type_id) REFERENCES leave_types(id)
);

-- Seed Data
INSERT INTO departments (name) VALUES ('IT'), ('HR'), ('Finance');

INSERT INTO leave_types (code, name, max_days_per_year) VALUES
('AL', 'Annual Leave', 14),
('CL', 'Casual Leave', 7),
('SL', 'Sick Leave', 7);

INSERT INTO users (emp_no, full_name, email, password_hash, role, dept_id, status) VALUES
('EMP001', 'System Admin', 'admin@example.com', SHA2('admin123',256), 'ADMIN', 1, 'ACTIVE'),
('EMP002', 'Department Manager', 'manager@example.com', SHA2('manager123',256), 'MANAGER', 2, 'ACTIVE'),
('EMP003', 'Employee One', 'emp1@example.com', SHA2('emp123',256), 'EMPLOYEE', 3, 'ACTIVE');


