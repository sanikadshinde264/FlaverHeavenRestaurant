# 🍽️ Flavor Heaven Restaurant

A full-stack restaurant website with online ordering, table reservations, and a complete admin dashboard — built with PHP, MySQL, HTML, CSS, and vanilla JavaScript.

## Features

### Customer-facing
- Responsive restaurant landing page with menu browsing by category (Veg / Non-Veg / Drinks / Desserts)
- Cart-based food ordering with checkout (customer details, delivery date/time, special instructions, payment method)
- Table reservation form
- User signup / login (session-based auth)
- Profile page showing order and reservation history

### Admin panel (`/admin`)
- Separate admin login and signup
- Dashboard with key stats
- Menu management (add / edit / delete items)
- Order management (view items ordered, special instructions, delivery date & time, payment & order status)
- Reservation management
- Payments overview
- User management
- Reports

### Access flow
`access.html` is the entry point where a visitor chooses **User** or **Admin**:
- **User** → `signup.html` (or `login.html` if already registered)
- **Admin** → `admin/signup.php` (or `admin/login.php` if already registered)

## Tech stack
- **Frontend:** HTML, CSS, vanilla JavaScript
- **Backend:** PHP 
- **Database:** MySQL

## Folder structure
```text
FlaverHeavenRestaurant/
├── index.html              Homepage
├── access.html             Choose User or Admin
├── login.html / signup.html         User auth pages
├── login.js / signup.js
├── profile.html / profile.php       User profile & order history
├── logout.php
├── script.js / style.css
├── admin/
│   ├── login.php / login_view.html       Admin auth
│   ├── signup.php / signup_view.html
│   ├── dashboard.php / dashboard.html
│   ├── menu.php / menu.html / menu_form.php / menu_form.html
│   ├── orders.php / orders.html
│   ├── reservations.php / reservations.html
│   ├── payments.php / payments.html
│   ├── users.php / users.html
│   ├── reports.php
│   ├── sidebar.php
│   └── logout.php
├── api/
│   ├── config.php           Database connection settings
│   ├── auth.php             Login / register endpoint
│   ├── session.php
│   ├── menu.php
│   ├── submit_order.php
│   └── submit_reservation.php
├── MySQL_Workbench/
│   ├── db.sql               Full database schema (tables + default admin)
│   └── menu_seed.sql        Sample menu data
└── Images/
```

## Setup (XAMPP)

1. **Install XAMPP** and start **Apache** and **MySQL**.

2. **Copy this project** into your XAMPP `htdocs` folder, e.g.:
   ```
   C:\xampp\htdocs\FlaverHeavenRestaurant
   ```

3. **Create the database.** Open phpMyAdmin (`http://localhost/phpmyadmin`) and import, in order:
   - `MySQL_Workbench/db.sql` — creates the database and all tables
   - `MySQL_Workbench/menu_seed.sql` — adds sample menu items

4. **Set your database credentials** in `api/config.php`:
   ```php
   $host = '127.0.0.1';
   $port = 3306;
   $user = 'root';
   $pass = ''; // <-- change to match your MySQL setup
   $dbName = 'flaverheaven';
   ```
   > If you're using XAMPP's bundled MySQL, the default root password is usually **empty** (`$pass = '';`), not `root`.

5. **Open the site:**
   ```
   http://localhost/FlaverHeavenRestaurant/access.html
   ```

6. **Create an account:**
   - Click **Login** in the navbar → choose **User** or **Admin** on `access.html`.
   - Admin signup only needs a username and password.

## License
This project is for educational purposes.
