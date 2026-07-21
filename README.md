# 🍽️ Flavor Heaven Restaurant

A full-stack restaurant website with online food ordering, table reservations, and a complete admin dashboard — built with PHP, MySQL, HTML, CSS, and vanilla JavaScript.

---

## 🔎 Overview
Flavor Heaven Restaurant is a complete web application for a restaurant business, covering both the **customer-facing side** (browsing the menu, ordering food, booking a table, managing a profile) and the **admin side** (managing menu items, orders, reservations, payments, users, and reports). It uses a PHP + MySQL backend with session-based authentication, and a lightweight vanilla JS/HTML/CSS frontend — no frameworks required.

---

## ❓ Problem Statement
Small restaurants often rely on phone calls, walk-ins, or third-party platforms (with high commission fees) to take orders and reservations, and lack a simple way to manage their menu, track orders, or view business data in one place. This project solves that by providing:
- A branded, self-hosted ordering and reservation website
- A single admin panel to manage the entire restaurant's day-to-day operations
- User accounts so customers can track their own order and reservation history

---

## ✨ Features

### 👤 Customer-facing
- Responsive restaurant landing page with menu browsing by category (Veg / Non-Veg / Drinks / Desserts)
- Cart-based food ordering with checkout (customer details, delivery date/time, special instructions, payment method)
- Table reservation form
- User signup / login (session-based authentication)
- Profile page showing order and reservation history

### 🛠️ Admin panel
- Separate admin login and signup
- Dashboard with key stats
- Menu management (add / edit / delete items)
- Order management (view items ordered, special instructions, delivery date & time, payment & order status)
- Reservation management
- Payments overview
- User management
- Reports

---

## 🗂️ Dataset
This project doesn't use a static dataset — data is created and stored live in MySQL as the app is used, including:
- **Menu items** — seeded initially via `MySQL_Workbench/menu_seed.sql`
- **Users** — created via signup (customer and admin accounts)
- **Orders** — created via the checkout flow
- **Reservations** — created via the table reservation form
- **Payments** — recorded against orders

---

## 🛠️ Tools & Technologies
- **Frontend:** HTML, CSS, vanilla JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local server:** XAMPP (Apache + MySQL)
- **Database design:** MySQL Workbench

---

## ⚙️ Methods / Methodology
1. **Database design:** Modeled the schema (users, admins, menu items, orders, reservations, payments) in MySQL Workbench and exported it to `db.sql`.
2. **Backend API:** Built PHP endpoints under `api/` for authentication, session handling, menu retrieval, order submission, and reservation submission.
3. **Customer frontend:** Built the landing page, menu browsing, cart, checkout, and profile pages with HTML/CSS/JS, calling the PHP API endpoints via fetch requests.
4. **Admin panel:** Built a separate authenticated area under `admin/` with its own login/signup, dashboard, and CRUD pages for menu, orders, reservations, payments, and users.
5. **Session-based auth:** Implemented login/logout and session checks to protect user and admin areas.
6. **Seeding & testing:** Used `menu_seed.sql` to populate sample menu data for local development and testing.

---

## 📁 Project Directory Structure
```text
FlaverHeavenRestaurant/
├── index.html                        Homepage
├── access.html                       Choose User or Admin
├── login.html / login.js             User login
├── signup.html / signup.js / signup.css   User signup
├── profile.html / profile.php        User profile & order history
├── logout.php
├── script.js / style.css             Shared site logic & styling
│
├── admin/
│   ├── login.php / login_view.html        Admin auth
│   ├── signup.php / signup_view.html
│   ├── dashboard.php / dashboard.html
│   ├── menu.php / menu.html / menu_form.php / menu_form.html
│   ├── orders.php / orders.html
│   ├── reservations.php / reservations.html
│   ├── payments.php / payments.html
│   ├── users.php / users.html
│   ├── reports.php
│   ├── sidebar.php
│   ├── admin.css
│   └── logout.php
│
├── api/
│   ├── config.php                    Database connection settings
│   ├── auth.php                      Login / register endpoint
│   ├── session.php
│   ├── menu.php
│   ├── submit_order.php
│   └── submit_reservation.php
│
├── MySQL_Workbench/
│   ├── db.sql                        Full database schema (tables + default admin)
│   └── menu_seed.sql                 Sample menu data
│
└── Images/                           Site graphics (logo, backgrounds, food items)
```

---

## 🖥️ Output
The live application includes:
- **Homepage** — hero banner, menu highlights, and navigation into ordering/reservations
- **Menu & Cart** — browse by category, add items to cart, and check out
- **Table Reservation** — simple form to book a table for a chosen date/time
- **User Profile** — view past orders and reservations
- **Admin Dashboard** — key stats plus full management of menu, orders, reservations, payments, users, and reports
  
---

## 💡 Key Insights
- Splitting the app into distinct **customer** and **admin** flows (via `access.html`) keeps the authentication and permissions model simple and clear.
- Centralizing all data operations through PHP endpoints in `api/` makes the frontend lightweight and keeps database logic out of the client.
- Seeding the menu table separately from the schema (`db.sql` vs. `menu_seed.sql`) makes it easy to reset and reseed data during development/testing.

---

## ✅ Results & Conclusion
Flavor Heaven Restaurant delivers a working, self-hosted alternative to third-party ordering platforms: customers can browse the menu, place orders, and reserve tables, while admins get a single dashboard to manage the entire operation — menu, orders, reservations, payments, and users — without relying on external services.

---

## ▶️ How to Run the Project (XAMPP)

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
---

## 🚀 Future Work
- Add online payment gateway integration (e.g., Razorpay/Stripe) instead of manual payment status entry
- Add email/SMS notifications for order and reservation confirmations
- Add role-based permissions for multiple admin/staff accounts
- Containerize the app (Docker) for easier setup instead of manual XAMPP configuration
- Add automated tests for the API endpoints
- Deploy a live demo version

---

## 👤 Author & Contact
**Sanika Shinde** <br>
📧 [sanikadshinde264@gmail.com] | 🔗 [www.linkedin.com/in/sanikadshinde264] 
