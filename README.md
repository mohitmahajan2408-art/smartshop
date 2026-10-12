# ShopSmart India — Complete Full-Stack E-Commerce Platform
> **BCA Semester V Field Project**  
> *Tagline: "Everything You Need, All in One Place."*

---

## 1. Project Overview

**ShopSmart India** is a production-ready, responsive, full-stack Indian e-commerce marketplace built specifically as a BCA Semester V Field Project. It provides a multi-category shopping experience designed for Indian consumers with native Indian Rupees (₹) currency formatting, multi-language support (English, Marathi मराठी, Hindi हिंदी), real-time stock control, transactional order placement, an administrator dashboard, and unique algorithmic features including **Smart Budget Mode**, **Side-by-Side Product Comparison**, **Best Value Scores**, **Eco-Friendly Sustainability Scores**, and **Review Trust Scores**.

---

## 2. Technology Stack

* **Frontend:**
  * HTML5, CSS3, Modern Vanilla JavaScript (ES6+)
  * Responsive Mobile-First Grid System & Flexbox
  * Web Speech API (Voice Search) & Drag-and-drop Image Search Demo
  * Fetch API for real-time Cart & Wishlist asynchronous updates
* **Backend:**
  * PHP 8.2 (compatible with PHP 8.0+)
  * Object-Oriented Session, Database, and Rate Limiter Architecture
  * Role-Based Access Control (RBAC: Customer & Administrator)
* **Database:**
  * MySQL / MariaDB 10.4+
  * PDO with Prepared Statements (100% SQL-injection safe)
  * ACID Transactions for order placement and stock inventory decrements
* **Local Development Environment:**
  * XAMPP on Windows (Apache, PHP, MariaDB)

---

## 3. ShopSmart Brand Identity

* **Brand Colors:**
  * Navy Blue (`#0f233a`, `#0a192f`, `#1e3a8a`)
  * Vibrant Orange (`#f97316`, `#ea580c`)
  * Light Neutral Backgrounds (`#f8fafc`, `#f1f5f9`)
  * Green Sustainability Accent (`#10b981`)
* **Vector Logo:** Custom SVG shopping bag combining a stylized 'S' lettermark and shopping cart wheels (`assets/images/logo.svg`).
* **Session-Aware Splash Screen:** Displays on initial entry, smoothly transitions to homepage, and avoids repeated display using `sessionStorage`.

---

## 4. Default Seed Credentials

| Role | Email / Login | Password | Capabilities |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin@shopsmart.in` | `Admin@ShopSmart2026` | Full Admin Dashboard, Catalog, Orders, Reviews, Reports, Audit Logs |
| **Demo Customer 1** | `customer@shopsmart.in` | `Customer@123` | Browsing, Cart, Checkout, Order Tracking, Expense Tracker, Reviews |
| **Demo Customer 2** | `priya.patel@example.com` | `Customer@123` | Orders, Saved Addresses, Wishlist |

---

## 5. Local Installation on Windows (XAMPP)

Follow these step-by-step instructions to set up and run the application locally:

### Step 1: Install XAMPP
Ensure XAMPP is installed on your Windows machine with PHP 8.x and MySQL (typically at `C:\xampp`).

### Step 2: Deploy Code to `htdocs`
Copy the `smartshop_workpace` folder to your XAMPP web root directory:
```powershell
Copy-Item -Path "c:\Users\HP\OneDrive\Desktop\smartshop_workpace" -Destination "C:\xampp\htdocs\ShopSmart" -Recurse
```

### Step 3: Start Services
Open **XAMPP Control Panel**:
1. Click **Start** next to **Apache**.
2. Click **Start** next to **MySQL**.

### Step 4: Import Database
You can initialize the database using either of two methods:

#### Method A: Automated CLI / Browser Setup (Recommended)
Open Command Prompt / PowerShell:
```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\ShopSmart\setup_db.php"
```
Or open in your browser:
```
http://localhost/ShopSmart/setup_db.php
```
This script automatically connects to MySQL, creates `shopsmart_db`, and imports the complete schema with 14 categories, 25+ brands, and 60+ sample products!

#### Method B: phpMyAdmin Manual Import
1. Open `http://localhost/phpmyadmin/` in your browser.
2. Click on **New** and create a database named `shopsmart_db` with collation `utf8mb4_unicode_ci`.
3. Select `shopsmart_db`, go to the **Import** tab.
4. Choose the file `C:\xampp\htdocs\ShopSmart\database.sql` and click **Import**.

### Step 5: Launch the Application
* **Storefront Homepage:** [http://localhost/ShopSmart/](http://localhost/ShopSmart/)
* **Administrator Portal:** [http://localhost/ShopSmart/admin/login.php](http://localhost/ShopSmart/admin/login.php)
* **Smart Budget Optimizer:** [http://localhost/ShopSmart/budget.php](http://localhost/ShopSmart/budget.php)
* **Product Catalog:** [http://localhost/ShopSmart/products.php](http://localhost/ShopSmart/products.php)

---

## 6. Running Automated Tests

To verify application integrity, Indian currency formatting, algorithms, and security controls:
```powershell
& "C:\xampp\php\php.exe" test_runner.php
```
*Output: All 13 unit and integration tests passing with 0 errors.*

---

## 7. Troubleshooting Guide

1. **Port 80 Conflict (Apache won't start):**
   * *Cause:* World Wide Web Publishing Service (IIS) or Skype is using port 80.
   * *Fix:* In XAMPP Control Panel, click **Config** for Apache &rarr; `httpd.conf` &rarr; change `Listen 80` to `Listen 8080`. Then access `http://localhost:8080/ShopSmart/`.
2. **Port 3306 Conflict (MySQL won't start):**
   * *Cause:* Another MySQL instance is already running as a Windows service.
   * *Fix:* Open Windows Services (`services.msc`), stop `MySQL80`, and restart MySQL in XAMPP.
3. **Database Connection Error (`SQLSTATE[HY000] [2002]`):**
   * Verify that MySQL is running in XAMPP Control Panel. Check `config/db.php` if your local MySQL has a root password.

---

## 8. Features & Academic Demonstrations

* **Fully Implemented Features:**
  * Multi-language switcher (English, Marathi, Hindi) with persistent session/cookie.
  * Real-time shopping cart with inventory stock ceilings and free shipping calculation.
  * 3-Step Indian Checkout with saved addresses, state/PIN validation.
  * Transactional order processing with inventory movement logs.
  * Order status tracking with status milestones (Confirmed &rarr; Packed &rarr; Shipped &rarr; Delivered).
  * Customer cancellation & 7-day return request workflows.
  * Monthly Shopping Expense Tracker with user-defined budget and progress bar.
  * Admin dashboard with KPI metrics, product management, stock alerts, review moderation, and CSV exports.
* **Demonstration Modes (Clearly Labeled):**
  * **Payment Gateway:** Simulated Indian payment flow (UPI ID, Card, NetBanking, COD). No real bank funds deducted.
  * **Voice Search:** Web Speech API integration with fallbacks for browsers without speech support.
  * **Image Search:** Upload interface with pre-categorized sample matching.
  * **Password Reset:** Generates and displays secure recovery token directly on screen for testing without SMTP mail servers.
