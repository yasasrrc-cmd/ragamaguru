RAGAMAGURU APPOINTMENT SYSTEM
===============================

INSTALLATION INSTRUCTIONS
-------------------------

1. DATABASE SETUP:
   - Open phpMyAdmin or your MySQL client
   - Create a new database named 'ragamaguru_appointments'
   - Import the 'database.sql' file
   - Default admin login will be created: username: admin, password: admin123

2. CONFIGURATION:
   - Open config.php
   - Update database credentials if different:
     * DB_HOST (default: localhost)
     * DB_USER (default: root)
     * DB_PASS (default: empty)
   - Update SMS API settings:
     * SMS_API_KEY: Your Richmo API token
     * SMS_FROM_MASK: Your sender mask (default: Ragamaguru)

3. UPLOAD FILES:
   - Upload all PHP files to your web server
   - Ensure proper permissions for file access

4. ACCESS THE SYSTEM:
   - Navigate to: http://yourwebsite.com/login.php
   - Login with default credentials
   - Change admin password immediately

FILE STRUCTURE
--------------
- config.php            : Database and system configuration
- database.sql          : Database schema
- login.php             : Admin login page
- logout.php            : Logout functionality
- dashboard.php         : Main dashboard
- header.php            : Navigation header
- style.css             : All CSS styles

CUSTOMERS:
- customers.php         : List all customers
- customer_add.php      : Add new customer
- customer_view.php     : View customer details & history
- customer_edit.php     : Edit customer information
- customer_delete.php   : Delete customer

APPOINTMENTS:
- appointments.php      : List/Calendar view
- appointment_add.php   : Book new appointment
- appointment_view.php  : View appointment details
- appointment_edit.php  : Edit appointment
- appointment_delete.php: Delete appointment

SERVICES:
- services.php          : Manage services (Add/Edit/Delete)

INVOICES:
- invoices.php          : List all invoices
- invoice_create.php    : Create new invoice
- invoice_view.php      : View & print invoice
- invoice_edit.php      : Edit invoice
- invoice_delete.php    : Delete invoice

REPORTS:
- reports.php           : Daily schedules, revenue, statistics

FEATURES
--------
✓ Admin login system
✓ Customer management with mobile verification
✓ Service management (Add/Edit/Delete)
✓ Appointment booking with calendar
✓ Treatment history tracking
✓ Invoice generation and printing
✓ SMS notifications via Richmo API
✓ Search customers by mobile number
✓ View past treatment details
✓ Daily schedule reports
✓ Revenue reports
✓ Service performance analytics

SMS INTEGRATION
---------------
The system uses Richmo SMS API for:
- Customer mobile verification
- Appointment confirmations
- Reminders

Format: https://portal.richmo.lk/api/v1/sms/send/?dst=947xxxxxxxx&from=[mask]&msg=[message]&key=[token]

SECURITY NOTES
--------------
1. Change default admin password immediately
2. Keep config.php secure
3. Use HTTPS in production
4. Regular database backups
5. Update SMS API key in config.php

SYSTEM REQUIREMENTS
-------------------
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- cURL enabled for SMS functionality

SUPPORT
-------
For issues or questions, contact your developer

DEFAULT LOGIN
-------------
Username: admin
Password: admin123

IMPORTANT: Change this password after first login!