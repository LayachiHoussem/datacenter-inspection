# 🏢 Datacenter Inspection & Maintenance Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Live Demo](https://img.shields.io/badge/Live_Demo-Online-00C781?style=for-the-badge&logo=google-chrome&logoColor=white)](https://datacenter-inspection.free.nf)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)

A PHP MVC web application engineered for datacenter infrastructure monitoring, routine equipment inspection, preventive maintenance scheduling, compliance logging, automated reporting, and audit trail tracking.

---

## 🌐 Live Demo

Explore the live demonstration:
👉 **[https://datacenter-inspection.free.nf](https://datacenter-inspection.free.nf)** *(or [http://datacenter-inspection.free.nf](http://datacenter-inspection.free.nf))*

### 🔑 Demo Login Credentials

| Role | Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@datacenter.local` | `Admin123` *(or `admin123`)* |
| **Inspector** | `inspector@datacenter.local` | `password123` |

---

## ⚡ Core Features

- 🛡️ **Role-Based Access Control (RBAC)**: Fine-grained permissions for Admin, Inspector, and Manager user tiers.
- 📅 **Preventive Maintenance Management**:
  - Interactive **Kanban Board** with drag-and-drop status workflows.
  - Multi-view scheduling: **Table View**, **Interactive Monthly Calendar**, and **Kanban Board**.
  - Dynamic recurrence scheduling (daily, weekly, monthly, quarterly, custom interval).
  - Multi-equipment associations per maintenance plan.
  - Instant live search & multi-criteria filtering across equipment, technicians, priorities, and dates.
- 🔍 **Interactive Inspection Workflows**:
  - Structured checklists for CRAC/HVAC Cooling, UPS & Power Distribution, Fire Suppression (VESDA/FM-200), Server Racks, and Security Hardware.
  - Dynamic scoring, status tagging (`PASS`, `WARNING`, `FAIL`), and photo attachments.
- 📊 **Executive Analytics & Reporting**:
  - Real-time facility compliance gauge and equipment uptime tracking.
  - One-click exportable executive PDF inspection reports (powered by Dompdf).
- 🗄️ **Hardware & Rack Inventory**:
  - Track server racks, PDUs, CRAC units, generators, and battery banks across room locations.
- 📝 **Comprehensive Audit Logging**:
  - Immutable audit logs capturing every user mutation, inspection run, and maintenance action.

---

## 🛠️ Technology Stack

- **Backend**: Native PHP 8.0+ (Custom MVC architecture with PSR-4 autoloader)
- **Database**: MySQL / MariaDB (PDO with prepared statements) / SQLite support
- **Frontend**: HTML5, Vanilla JavaScript, Responsive CSS3, FontAwesome Icons
- **Reporting**: Dompdf PDF generation engine

---

## 📁 Directory Structure

```text
datacenter-inspection/
├── app/
│   ├── config/          # Database, constants, and system configuration
│   ├── controllers/     # MVC Controller handlers
│   ├── helpers/         # Authentication, CSRF, validation & core utilities
│   ├── middleware/      # Auth & Role verification middleware
│   ├── models/          # PDO Model classes & business entities
│   ├── services/        # PDF generation, mailer, and file upload services
│   └── views/           # Modular HTML layouts, dashboards & subviews
├── database/            # DDL MySQL schema & sample seed dataset
│   ├── schema.sql       # Database table definitions
│   └── seed.sql         # Initial sample datacenter dataset
├── public/              # Web root (CSS, JS, Uploads, front controller)
│   ├── assets/          # Stylesheets, JavaScript modules, fonts
│   ├── index.php        # Primary application front controller
│   └── .htaccess        # Apache URL rewrite rules
├── storage/             # Generated PDFs, report files, and cached settings
├── index.php            # Root bridge front controller (shared hosting compatibility)
├── .htaccess            # Root Apache URL rewrite configuration
├── composer.json        # Project metadata & autoloader configuration
└── README.md            # Project documentation
```

---

## 🚀 Quick Start & Installation

### 1. Prerequisites
- PHP >= 8.0 with `pdo_mysql`, `gd`, and `mbstring` extensions enabled.
- MySQL >= 5.7 or MariaDB >= 10.3.
- Apache web server with `mod_rewrite` enabled (e.g. WAMP, XAMPP, or LAMP).

### 2. Clone the Repository
```bash
git clone https://github.com/LayachiHoussem/datacenter-inspection.git
cd datacenter-inspection
```

### 3. Database Setup
1. Create a MySQL database (e.g. `datacenter_inspection`).
2. Import the schema and seed files:
   ```bash
   mysql -u root -p datacenter_inspection < database/schema.sql
   mysql -u root -p datacenter_inspection < database/seed.sql
   ```
3. Configure your database connection in [`app/config/database.php`](app/config/database.php) or define environment variables:
   - `DB_HOST`: Database host (default: `127.0.0.1`)
   - `DB_NAME`: Database name (default: `datacenter_inspection`)
   - `DB_USER`: Database username (default: `root`)
   - `DB_PASS`: Database password (default: empty)
   - `DB_PORT`: Database port (default: `3306`)

### 4. Running Locally

Using PHP's built-in development server:
```bash
php -S localhost:8000 -t public
```
Then visit **`http://localhost:8000`** in your browser.

Or with Apache / WAMP / XAMPP:
Point your virtual host DocumentRoot to the `public/` directory (or access the project folder directly via the root `index.php` bridge).

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.
