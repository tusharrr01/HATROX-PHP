## HATROX – Luxury Jewelry E‑commerce

HATROX is a **PHP + MySQL** e‑commerce website for a luxury jewelry brand, with a modern, dark UI, product listings, ratings, testimonials, cart and checkout flow, plus an admin dashboard for managing products, orders, comments and users.

---

**Live Demo:** [Click ME !!](https://birthday-wishes-rouge-theta.vercel.app/)

---
# ScreenShot

  ![HATRO:X storefront](docs/screenshot-home.png)

---

### Features

- **Storefront**
  - Hero section with background video and brand logo.
  - New arrivals, top rated, top sellers and top discounts.
  - Product detail pages with dynamic pricing and discounts.
  - Cart management (add / update / cancel orders).
  - Ratings and comments with testimonials slider.
- **Authentication**
  - User registration and login.
  - Separate admin and user sessions.
- **Admin Panel**
  - Dashboard overview.
  - Manage products, orders, comments and users.
  - Approve / hide comments and view top‑performing products.

---

### Tech Stack

- **Backend**: PHP (procedural, `mysqli`)
- **Database**: MySQL (`Database/hatrox_db.sql`, `Database/schema.sql`)
- **Sessions & Security**:
  - Separate `hatrox_user` / `hatrox_admin` sessions.
  - CSRF token helpers and HTML escaping helper.
- **Frontend**:
  - Tailwind‑style utility classes in markup.
  - Vanilla JS for sliders and small interactions.

---

### Project Structure (high level)

```text
.
├─ index.php                 # Redirect entry to public front page
├─ public/                   # Public storefront pages (home, shop, product detail, auth)
├─ admin/                    # Admin dashboard and management pages
├─ includes/                 # Shared layout and DB connection
├─ utilities/                # Cart, ratings, comments, helper endpoints
├─ Database/
│  ├─ hatrox_db.sql          # Main database dump
│  └─ schema.sql             # Schema definition
└─ assets/                   # Images, videos, icons (referenced from views)
```

---

### Getting Started (Local Setup)

#### 1. Prerequisites

- **PHP** 8.1+ (XAMPP, WAMP, Laragon, or standalone PHP)
- **MySQL** 5.7+ / MariaDB
- A web server stack (e.g. **XAMPP** on Windows) is recommended.

#### 2. Clone or copy the project

Place the project under your web root, for example on Windows with XAMPP:

- `C:\xampp\htdocs\hatrox-project`

So you can access it at:

- `http://localhost/hatrox-project/public/index.php`

#### 3. Create the database

1. Start **MySQL** and open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a new database, e.g. `hatrox_db`.
3. Import the SQL file:
   - Select the new database.
   - Go to the **Import** tab.
   - Choose `Database/hatrox_db.sql` (or `schema.sql` if you prefer just the structure).
   - Click **Go** to run the import.

#### 4. Configure database connection

Edit `includes/db_connection.php` and make sure these values match your local setup:

```php
$DB_HOST = 'localhost';
$DB_USER = 'root';      // default XAMPP user
$DB_PASS = '';          // default XAMPP password (empty)
$DB_NAME = 'hatrox_db'; // name of the DB you created
```

If you changed your MySQL user or password, update them here accordingly.

#### 5. How To Run

1. Start **Apache** and **MySQL** in your stack (XAMPP/WAMP/etc.).
2. Open your browser and visit:
   - Storefront: `http://localhost/hatrox-project/public/index.php`
   - Admin: `http://localhost/hatrox-project/admin/index.php` (use whatever admin credentials are defined in your seed data).

---

