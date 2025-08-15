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

### Prerequisites

- A web server (Apache is recommended)
- PHP 5.6 or newer
- MySQL or MariaDB database server

### Installation Steps

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
    - Open the following two files in a text editor:
      - `./conn.php`
      - `./admin/includes/conn.php`
    - Update the database connection variables (`$host`, `$user`, `$password`, `$dbname`) in both files to match your server's credentials.

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