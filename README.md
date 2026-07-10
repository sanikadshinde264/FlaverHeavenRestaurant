<<<<<<< HEAD
# Flavor Heaven - Full Stack Restaurant Project

## What is included
- Responsive restaurant landing page
- Login and sign-up flow connected to PHP + MySQL
- Reservation form saving to the database
- Cart-based ordering flow with order summary
- Payment method, payment date, and payment time saved to the database
- All user activity stored in MySQL tables

## Folder structure
```text
FlaverHeaven/
├── index.html
├── login.html
├── login.js
├── script.js
├── style.css
├── README.md
└── api/
    ├── config.php
    ├── auth.php
    ├── submit_reservation.php
    ├── submit_order.php
    └── db.sql
```

## Step-by-step setup
1. Install XAMPP and start Apache and MySQL.
2. Copy this project folder into the XAMPP htdocs folder:
   - Windows: C:\xampp\htdocs\FlaverHeaven
3. Open phpMyAdmin at http://localhost/phpmyadmin
4. Import the SQL file from [api/db.sql](api/db.sql)
5. Open the app in your browser:
   - http://localhost/FlaverHeaven/login.html

## Database setup
Run the following SQL in phpMyAdmin or MySQL terminal:
```sql
CREATE DATABASE IF NOT EXISTS flaverheaven;
USE flaverheaven;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL,
  phone VARCHAR(25) NOT NULL,
  reservation_date DATE NOT NULL,
  reservation_time TIME NOT NULL,
  guests INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  customer_name VARCHAR(120) NOT NULL,
  mobile VARCHAR(25) NOT NULL,
  address VARCHAR(255) NOT NULL,
  items_json TEXT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(60) NOT NULL,
  payment_status VARCHAR(30) NOT NULL DEFAULT 'paid',
  payment_date DATE NOT NULL,
  payment_time TIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Important note
If your MySQL username/password is different from the default XAMPP values, update the database settings in [api/config.php](api/config.php).

## How the flow works
1. User opens the login page.
2. User signs in or creates an account.
3. The account details are stored in the users table.
4. User browses the menu, clicks cards, and the selected dishes are added to the cart.
5. User checks out from the order section.
6. Reservation data and order data are stored in the MySQL database.
=======
# FlaverHeavenRestaurant
>>>>>>> f848c4e1df85f2c64dd60c6d117421369bfaaddf
