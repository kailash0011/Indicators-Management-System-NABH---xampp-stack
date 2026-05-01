# NABH Indicators Management System

A web-based quality indicators management system for NABH-accredited hospitals, built with PHP and MySQL (XAMPP stack).

---

## Requirements

| Component | Version |
|-----------|---------|
| XAMPP (or Apache + PHP + MySQL) | PHP ≥ 7.4, MySQL ≥ 5.7 |
| PHP extensions | `pdo_mysql`, `mbstring` |
| Web browser | Any modern browser |

---

## Installation (XAMPP)

### 1 — Clone / Copy the project

Place the project folder inside XAMPP's web root and name it **`nabh`**:

```
C:\xampp\htdocs\nabh\   (Windows)
/opt/lampp/htdocs/nabh/ (Linux)
```

Your directory should look like:

```
nabh/
├── includes/
├── sql/
├── setup.php
├── index.php
└── ...
```

### 2 — Start XAMPP services

Open the XAMPP Control Panel and start **Apache** and **MySQL**.

### 3 — Run the one-click installer

Open your browser and go to:

```
http://localhost/nabh/setup.php
```

Click **"Run Setup Now"**. The installer will:

1. Connect to MySQL (root / no password — default XAMPP).
2. Create the `nabh_indicators` database.
3. Create all required tables from `sql/schema.sql`.
4. Insert departments and indicators from `sql/seed.sql`.
5. Create default user accounts.

> **First-time visit to `index.php`?**  
> If the database has not been set up yet, the login page automatically redirects you to `setup.php`.

### 4 — Log in

After setup completes, go to:

```
http://localhost/nabh/index.php
```

---

## Default Credentials

| Role | Username | Password |
|------|----------|----------|
| Administrator | `admin` | `admin123` |
| Quality Officer | `quality` | `quality123` |
| Medicine In-charge | `med_incharge` | `dept123` |
| Surgery In-charge | `surg_incharge` | `dept123` |
| OBG In-charge | `obg_incharge` | `dept123` |
| Paediatrics In-charge | `paed_incharge` | `dept123` |
| Emergency In-charge | `emrg_incharge` | `dept123` |
| ICU In-charge | `icu_incharge` | `dept123` |
| OT In-charge | `ot_incharge` | `dept123` |
| Lab In-charge | `lab_incharge` | `dept123` |
| Radiology In-charge | `rad_incharge` | `dept123` |
| Blood Bank In-charge | `bb_incharge` | `dept123` |
| Pharmacy In-charge | `pharm_incharge` | `dept123` |
| CSSD In-charge | `cssd_incharge` | `dept123` |
| Dietetics In-charge | `diet_incharge` | `dept123` |
| Dialysis In-charge | `dial_incharge` | `dept123` |
| NICU In-charge | `nicu_incharge` | `dept123` |

> ⚠️ **Change all passwords** after your first login in a production environment.

---

## Manual SQL Import (alternative to setup.php)

If you prefer to import manually via phpMyAdmin:

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`.
2. Click **"Import"** → choose `sql/schema.sql` → click **Go**.
3. Select the newly created `nabh_indicators` database.
4. Click **"Import"** again → choose `sql/seed.sql` → click **Go**.
5. Create users by running `setup.php` (step 4 of the installer only creates users; the DB/tables must already exist).

---

## Configuration

Edit `includes/config.php` to change database credentials or the hospital name:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // default XAMPP password is empty
define('DB_NAME', 'nabh_indicators');

define('HOSPITAL_NAME', 'City General Hospital');
define('BASE_URL',      '/nabh');  // change if the folder is named differently
```

---

## Troubleshooting

| Error | Cause | Fix |
|-------|-------|-----|
| `SQLSTATE[42S02]: Table 'nabh_indicators.departments' doesn't exist` | Database not set up | Run `http://localhost/nabh/setup.php` |
| `Database connection failed: …` | MySQL not running or wrong credentials | Start MySQL in XAMPP; check `DB_USER`/`DB_PASS` in `config.php` |
| Blank page / 404 on login | `BASE_URL` mismatch | Set `BASE_URL` in `config.php` to match the folder name (e.g. `/nabh`) |
| Page redirects to `setup.php` on every visit | Tables missing | Re-run the setup installer |

---

## Project Structure

```
nabh/
├── admin/          — Admin panel pages (departments, users, indicators, reports)
├── ajax/           — JSON API endpoints
├── assets/         — CSS / JS assets
├── incharge/       — Department in-charge pages
├── includes/
│   ├── auth.php    — Authentication functions
│   ├── config.php  — Database & app configuration
│   └── functions.php
├── sql/
│   ├── schema.sql  — Database & table DDL
│   └── seed.sql    — Departments, indicators, and assignment seed data
├── setup.php       — One-click installer
├── index.php       — Login page
├── dashboard.php   — Post-login router
└── logout.php
```