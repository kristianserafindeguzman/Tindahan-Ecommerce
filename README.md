```markdown
# Tindahan: Machine Learning-Driven Multi-Vendor E-Commerce Platform

A comprehensive multi-vendor web application designed to optimize localized sari-sari store operations, digitalize physical sales workflows, and streamline livelihood monitoring. The system integrates predictive modeling via a Random Forest algorithm and interactive geospatial tracking to support cooperative stakeholders and local administrators.

---

## 👥 Development Team

- **De Guzman, Kristian Serafin I.** 
- **Dueñas, Earl Dann C.** 
- **Fabula, Lawrence Joe B.** 
- **Gabitan, Angelo** 

---

## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend Framework:** PHP (Laravel Framework)
- **Database Engine:** MySQL
- **Data Science / ML:** Python (Random Forest Algorithm)
- **Mapping & GIS:** Leaflet.js & OpenStreetMap API
- **Environment & Hosting:** Laragon / Git / GitHub

---

## Local Development Setup

Follow these exact steps to clone the repository and establish your local working environment.

### 1. Prerequisites
Before starting, ensure your machine has the following tools installed globally:
- **Laragon** (Latest version running PHP 8.4+ and MySQL)
- **Composer** (PHP dependency manager)
- **Node.js & NPM** (For compiling frontend assets and running Vite)
- **Git**

---

### 2. Step-by-Step Installation

Open your terminal or command prompt, navigate to your local Laragon web directory (`C:\laragon\www`), and execute the following commands sequentially:

#### Step A: Clone the Codebase
Clone the project from GitHub into your local directory:
```bash
git clone [https://github.com/kristianserafindeguzman/multi-vendor-ecommerce.git](https://github.com/kristianserafindeguzman/multi-vendor-ecommerce.git)
cd multi-vendor-ecommerce

```

#### Step B: Install Package Dependencies

Install the required framework packages for both the backend (Composer) and frontend (NPM):

```bash
# Install PHP dependencies
composer install

# Install and build Javascript/CSS dependencies
npm install
npm run dev

```

#### Step C: Set Up Your Local Environment File (.env)

1. In your project's root directory, look for a file named `.env.example`.
2. Duplicate this file or rename a copy of it to exactly `.env`.
3. Open the `.env` file in VS Code and locate the database configurations (around lines 22-27). Change them to match our project schema layout:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tindahan_db
DB_USERNAME=root
DB_PASSWORD=

```


4. Network Port Adjustment: If your local Laragon web server runs into a Port 80 conflict and you had to switch your Apache port to `8080`, make sure you also update line 5 of your `.env` file:
```env
APP_URL=http://localhost:8080

```


But if your Laragon server successfully runs on standard Port 80, leave it as `APP_URL=http://localhost`.

#### Step D: Establish Your Local Database

1. Open your web browser and navigate to your local phpMyAdmin dashboard (`http://localhost:8080/phpmyadmin6/` or `http://localhost/phpmyadmin/`).
2. Log in using the username `root` and leave the password field completely blank.
3. Click the **Databases** tab at the top left.
4. Under **Create database**, type `tindahan_db` in the name field and click **Create**.

#### Step E: Generate App Security Key & Run Migrations

Go back to your primary VS Code terminal inside the project folder and run the final setup scripts:

```bash
# Generate unique application encryption key
php artisan key:generate

# Build all framework and system architecture tables inside MySQL
php artisan migrate

```

---

## 🌿 Core Branching and Git Workflow

To maintain a clean codebase and prevent layout or merge conflicts, the team strictly adheres to this branch policy:

* **`main` branch:** Holds production-ready, fully tested milestones. **Never** commit or push code directly to this branch.
* **`development` branch:** The shared integration sandbox. Pull the latest state from this branch before beginning any feature assignment.
* **Feature Branches:** Create a separate tracking branch for individual tasks using the following command layout:
```bash
git checkout -b feature/your-feature-name

```


*Example:* `git checkout -b feature/login-interface`. Always merge back to the `development` branch via an approved Pull Request.

```

```