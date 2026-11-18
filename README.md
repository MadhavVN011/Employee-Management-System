# Employee Management System

A comprehensive, PHP-based Employee Management System designed to help you efficiently manage and track employee attendance, payroll, schedules, and more. This system provides a simple yet powerful interface for both administrators and employees.

## ✨ Features

This system comes packed with features to streamline your HR processes:

- **Dual Interfaces:** Separate, dedicated panels for Administrators and Employees.
- **Employee Management:** Easily add, edit, view, and remove employee records.
- **Attendance Tracking:** Monitor employee check-in and check-out times. Employees can log attendance using their Employee ID or by scanning a unique QR code.
- **Payroll Management:**
  - Set up and manage payrolls.
  - Track overtime work and calculate pay.
  - Handle cash advances and manage deductions.
- **Workforce Organization:**
  - Create and manage employee schedules.
  - Define and assign position titles and roles.
- **Reporting:** Generate reports for attendance, payroll, and more.

## 🚀 Getting Started

This project is a web-based application built on PHP and MySQL. You can set it up on a local server (like XAMPP, WAMP) or a live web server.

### Run locally with Docker

With Docker and Docker Compose installed, run:

```sh
docker compose up --build -d --wait
```

Open [the employee portal](http://localhost:8080/) or
[the admin panel](http://localhost:8080/admin/). Sign in as `admin` with password
`password`. The sample database imports automatically on the first start.

The database and uploaded employee photos are stored in Docker volumes. Stop
the application with `docker compose down`; these volumes are preserved.

To change the port or local database passwords before the first start, copy
`.env.example` to `.env` and edit it. Database passwords are initialized only
when the database volume is first created. The app listens on localhost only.

After changing application code, run the start command again to rebuild it.
Use `docker compose logs app db` to inspect startup errors.

### Manual installation

#### Prerequisites

- A web server (Apache is recommended)
- PHP 8.2 or newer with `mysqli` (mysqlnd) and `fileinfo` extensions
- MySQL or MariaDB database server

#### Installation Steps

1.  **Clone the Repository:**
    ```sh
    git clone [https://github.com/your-username/Employee-Management-System.git](https://github.com/your-username/Employee-Management-System.git)
    cd Employee-Management-System
    ```

2.  **Create the Database:**
    - Create a new database in your MySQL server (e.g., via phpMyAdmin).
    - Name the database `company_ems`.

3.  **Import the Database Schema:**
    - Find the `.sql` file located in the `/database` folder.
    - Import this file into your `company_ems` database. This will create all the necessary tables and seed some initial data.

4.  **Configure Database Connection:**
    - Set `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME` in your web server's environment, or update the defaults in `./conn.php`.
    - The employee portal and admin panel share this connection configuration.

5.  **Set Timezone (Optional):**
    - To set a default timezone for the application, edit the `./timezone.php` file and set your desired timezone (e.g., `'Asia/Kolkata'`).

6.  **Deploy and Run:**
    - Move the project folder to your web server's root directory (e.g., `htdocs` for XAMPP).
    - Open your web browser and navigate to the project URL.

## 🔑 Access and Usage

### Admin Panel
-   **URL:** `http://your-domain.com/admin`
-   **Username:** `admin`
-   **Password:** `password`

### Employee Portal
-   Employees can check in and check out from the main page (`http://your-domain.com`).
-   Attendance can be logged using an **Employee ID** provided by the administrator or by scanning a **QR Code**.

### Generating QR Codes
The system uses QR codes for quick attendance. To generate one for an employee:
1.  Use a free online QR code generator (like [qrcode-monkey.com](https://www.qrcode-monkey.com)).
2.  Generate the QR code based on the following URL structure:
    `http://your-domain.com/attendance.php?employee={employeeId}`
    (Replace `{employeeId}` with the actual ID of the employee).

## 🛠️ Built With

-   **Backend:** PHP
-   **Frontend:** HTML, CSS, JavaScript
-   **Libraries & Frameworks:**
    -   jQuery 3
    -   Bootstrap 3.3.7
    -   Moment.js
    -   Font Awesome
-   **Fonts:** Google Fonts (Source Code Pro)

## 🤝 How to Contribute

We welcome contributions to improve this project! Here’s how you can help:
-   **Report Bugs:** If you find a bug, please open an issue and provide a detailed description with steps to reproduce it.
-   **Suggest Enhancements:** Have an idea for a new feature or an improvement? Feel free to open an issue to discuss it.
-   **Submit Pull Requests:** We are happy to review and merge pull requests.

## Validation

Run the regression checks with Python 3 and Docker:

```sh
python3 tests/run_regression.py
```

The runner creates temporary PHP 8.2 and MariaDB 10.11 containers, imports the
sample database, and removes the containers after testing. It checks admin
access, SQL parameter binding, attendance, payroll, PDF exports, and an empty
dashboard with strict SQL mode and PHP warnings enabled. It does not use your
application database. The first run downloads the two container images.

For browser checks, install Chrome/Chromium and Node.js/npm, then run:

```sh
python3 tests/run_browser_checks.py
```

This builds the current app and starts a separate, disposable Apache/MariaDB
instance. It checks all admin pages and edit modals in Chrome, exercises uploads
and profile updates, and removes its test containers and volumes. Playwright is
installed into a temporary directory; it does not add dependencies to the app.

Photos must be JPEG, PNG, GIF, or WebP images up to 5 MB. Uploads receive generated
filenames. The Docker Apache configuration blocks executable files in `images/`,
including files uploaded before validation was added. For manual Apache installs,
apply the equivalent upload-directory block in `docker/apache.conf`, or enable
and verify the rules in `images/.htaccess`.

Leaving the profile's new-password field blank keeps the current password.
Assigned schedules and positions must be reassigned before they can be deleted.

Paid attendance covers the overlap with an employee's assigned shift on the
same calendar day. The existing one-hour break applies after four paid hours.
Payroll includes recorded overtime as `hours × rate`, plus regular attendance
pay, less configured deductions and cash advances within the selected period.
The payroll page and PDF reports share this calculation.

The bundled TCPDF engine is pinned to 6.11.4; provenance is recorded in
`tcpdf/UPGRADE.txt`.
