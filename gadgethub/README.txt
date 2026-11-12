GADGETHUB - GADGET MANAGEMENT SYSTEM
Stack: PHP 8+, MySQL/MariaDB, HTML, CSS, JavaScript
Designed for local XAMPP setup.

INSTALLATION
1. Extract the gadgethub folder into C:\xampp\htdocs\
2. Open XAMPP Control Panel and start Apache and MySQL.
3. Open http://localhost/phpmyadmin
4. Click Import, choose database.sql from this project, then click Import/Go.
5. Open config.php and confirm:
   host = localhost
   db = gadgethub
   user = root
   pass = '' (default XAMPP password; change if yours differs)
6. Visit http://localhost/gadgethub/setup_admin.php once to create the admin account.
   Default login: admin / Admin@12345. Delete setup_admin.php after it succeeds.
7. Visit http://localhost/gadgethub/ and sign in.

DATABASE TABLES (5)
categories, brands, suppliers, products, sales

FEATURES
- Dashboard with product count, total stock, low-stock count and today's sales
- Product create, read, update, delete
- Category, brand and supplier create/read/delete
- Sales recording with transaction-safe stock deduction
- Search/filter tables
- Responsive layout

NOTES
- The inventory dashboard requires signing in with an administrator account.
- Change the temporary admin password before using this on a public server.
- Product image URL accepts externally hosted images.
- Sales are recorded as one product per transaction to keep the database to five tables.
