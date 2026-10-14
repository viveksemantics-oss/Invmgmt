# Printing Management System (Invoice Management)

A robust, full-featured PHP-based ERP/management system designed for commercial printing hubs. It automates printing job tracking, paper stock management, production stage logging (plate making, UV printing, lamination), customer invoicing, payments ledger, and real-time inventory reporting.

---

## Key Features
- **General Job Cards & Costing:** Real-time interactive calculation of plate making and title print charges, GST, and net margins.
- **Production Workflow Management:** Tracks jobs sequentially across Plate Making, UV Printing, Lamination, and Billing.
- **Dynamic Inventory Stock Ledger:** Integrates manual stock-in, automated job consumption, and stock-out adjustments (waste/sample logging) with real-time balance calculations.
- **Client & Vendor Directory:** CRUD modal for managing active parties (customers/suppliers).
- **Billing & Invoice Engine:** Formulates printable customer invoices from completed jobs, records incremental payments, and displays real-time dues.
- **Aesthetic Dashboard:** Glassmorphism UI cards with dynamic KPI statistics and status summaries.
- **Automated Health Suite:** Integration testing tool to programmatically verify critical business flows.
- **Security & Integrity:** Structured CSRF token protection on all forms, credential externalization using `.env` environment files, and error audit logging.

---

## Technical Stack
- **Backend:** PHP 8.2+
- **Database:** MySQL / MariaDB (using PDO)
- **Frontend CSS:** Bootstrap 5 (Vanilla CSS, custom enhancements)
- **Frontend JS:** Vanilla Javascript, jQuery (for Bootstrap modals)

---

## Setup & Installation

### 1. Prerequisites
- Install **XAMPP** (with PHP 8.2+ and MySQL) or equivalent stack.
- Make sure Apache and MySQL services are running.

### 2. Database Import
1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a new database named `printing_management`.
3. Import the initial database schema from your seed file (or use phpMyAdmin's Import feature with the `.sql` schema).
4. Verify that tables such as `users`, `parties`, `paper_sizes`, `gsm_master`, `printing_jobs`, `invoices` are created.

### 3. Environment Configuration
Copy the `.env.example` file to `.env` in the root folder:
```bash
cp .env.example .env
```
Open `.env` and verify the values:
```ini
DB_HOST=localhost
DB_NAME=printing_management
DB_USER=root
DB_PASSWORD=
```

### 4. Running Locally
Using the built-in PHP development server, execute the following command from the root of the project:
```bash
php -S localhost:8000
```
Then, open your browser and navigate to `http://localhost:8000/`.

---

## Default Login Credentials
- **Username:** `admin`
- **Password:** `admin123`

---

## Running Integration Health Tests
The project features an automated, warning-free integration test suite to verify database connection health, calculator accuracy, status progression, and stock deduction calculations:
```bash
php run_tests.php
```
On success, it prints:
```text
==============================================
       ALL SANITY TESTS PASSED SUCCESSFULLY!  
==============================================
```

---

## Directory Structure
```text
├── assets/
│   ├── css/          # Core style.css layout rules
│   └── js/           # script.js handling Bootstrap Collapse logic
├── config/
│   └── database.php  # PDO setup, .env variables parsing, utility helpers
├── includes/
│   ├── header.php    # Shared navigation header
│   ├── footer.php    # Closing tags, script registers
│   ├── sidebar.php   # Absolute-path sidebar navigation links
│   └── functions.php # Security helpers (CSRF tokens validation)
├── modules/
│   ├── dashboard/    # KPI metrics lists
│   ├── jobs/         # Creation, edit, details, print layout for jobs
│   ├── paper/        # Sizes, GSM and client masters
│   ├── stock/        # Stock-in & manual stock-out
│   ├── production/   # Plate making, UV, and Lamination tracking
│   ├── billing/      # Invoice generator, viewer, payments recording
│   └── reports/      # Financial reports, job reports, stock reports
├── .env              # Active connection credentials
├── .env.example      # Credentials template
├── run_tests.php     # Programmatic health suite
└── README.md         # Setup and guide documentation
```
