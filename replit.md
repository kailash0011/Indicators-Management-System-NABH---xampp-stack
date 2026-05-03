# NABH Indicators Management System

A web-based quality indicators management system for NABH-accredited hospitals, built with PHP 8.2 and MySQL 8.0.

## Architecture

- **Backend**: PHP 8.2 built-in server (development), served on port 5000
- **Database**: MySQL 8.0 running locally via Unix socket at `/home/runner/mysql_run/mysql.sock`
- **Frontend**: Server-rendered PHP with Tailwind CSS (CDN)

## Project Structure

```
nabh/
├── admin/          — Admin panel pages (departments, users, indicators, reports, audit)
├── ajax/           — JSON API endpoints
├── assets/css/     — CSS styles
├── incharge/       — Department in-charge pages
├── includes/
│   ├── auth.php    — Authentication functions
│   ├── config.php  — Database & app configuration
│   └── functions.php
├── sql/
│   ├── schema.sql  — Database & table DDL
│   └── seed.sql    — Departments, indicators, and assignment seed data
├── start.sh        — Startup script (MySQL init + seeding + PHP server)
├── router.php      — PHP built-in server router
├── run_setup.php   — CLI script to create default users
├── my.cnf          — MySQL configuration file
├── setup.php       — One-click web installer
├── index.php       — Login page
├── dashboard.php   — Post-login router
└── logout.php
```

## Key Configuration

- **MySQL socket**: `/home/runner/mysql_run/mysql.sock`
- **MySQL data dir**: `/home/runner/mysql_data`
- **PHP server**: `0.0.0.0:5000`
- **BASE_URL**: empty string (app served from root `/`)
- **Database**: `nabh_indicators`

## How It Starts

The `start.sh` script:
1. Initializes MySQL data directory (first run only)
2. Starts `mysqld` using `my.cnf`
3. Waits for MySQL to be ready
4. Creates `nabh_indicators` DB + runs `schema.sql` + `seed.sql` (first run only)
5. Creates default users via `run_setup.php` (first run only)
6. Launches PHP built-in server on `0.0.0.0:5000`

## Default Credentials

| Role | Username | Password |
|------|----------|----------|
| Administrator | `admin` | `admin123` |
| Quality Officer | `quality` | `quality123` |
| Dept. In-charge | `med_incharge`, `surg_incharge`, etc. | `dept123` |

## Workflow

- **Start application**: `bash /home/runner/workspace/start.sh` on port 5000 (webview)
