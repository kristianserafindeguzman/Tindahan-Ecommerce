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

### Frontend
- Quasar Framework (Vue 3)
- JavaScript
- Axios
- Vue Router
- Vite

### Backend
- Laravel 13
- PHP 8.4+
- REST API

### Database
- MySQL

### Data Science / Machine Learning
- Python
- Random Forest Algorithm

### Mapping & GIS
- Leaflet.js
- OpenStreetMap API

### Development Tools
- Laragon
- Composer
- Node.js & NPM
- Git
- GitHub

---

# 📂 Project Structure

```
multi-vendor-ecommerce/
│
├── backend/              # Laravel API
│   ├── app/
│   ├── routes/
│   ├── database/
│   └── ...
│
├── frontend/             # Quasar Application
│   ├── src/
│   ├── public/
│   └── ...
│
├── README.md
└── .gitignore
```

---

# 🚀 Local Development Setup

Follow these steps to set up the project on your local machine.

## 1. Prerequisites

Install the following software:

- Laragon (PHP 8.4+ & MySQL)
- Composer
- Node.js (v22.22.0 or later)
- NPM
- Git

---

## 2. Clone the Repository

```bash
cd C:\laragon\www

git clone https://GRP9-Capstone-Project@dev.azure.com/GRP9-Capstone-Project/multi-vendor-ecommerce/_git/multi-vendor-ecommerce

cd multi-vendor-ecommerce
```

---

# ⚙️ Backend Setup (Laravel)

Navigate to the backend folder.

```bash
cd backend
```

Install Laravel dependencies.

```bash
composer install
```

Create the environment file.

```bash
copy .env.example .env
```

Generate the application key.

```bash
php artisan key:generate
```

Update your `.env` database configuration.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tindahan_db
DB_USERNAME=root
DB_PASSWORD=
```

If Laragon is using port 8080:

```env
APP_URL=http://localhost:8080
```

Otherwise:

```env
APP_URL=http://localhost
```

Run the database migrations.

```bash
php artisan migrate
```

Start the Laravel server.

```bash
php artisan serve
```

Laravel API will be available at:

```
http://127.0.0.1:8000
```

---

# 💻 Frontend Setup (Quasar)

Open another terminal.

```bash
cd frontend
```

Install frontend dependencies.

```bash
npm install
```

Run the development server.

```bash
quasar dev
```

Quasar will run at:

```
http://localhost:9000
```

---

# 🔗 Frontend ↔ Backend Connection

The frontend communicates with Laravel through Axios.

File:

```
frontend/src/boot/axios.js
```

Example configuration:

```javascript
import { boot } from 'quasar/wrappers'
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api'
})

export default boot(({ app }) => {
  app.config.globalProperties.$api = api
})

export { api }
```

Example API route:

```
GET /api/test
```

Expected response:

```json
{
    "message": "Laravel API is working!"
}
```

---

# 🗄️ Database Setup

1. Open phpMyAdmin.
2. Create a database named:

```
tindahan_db
```

3. Run Laravel migrations.

```bash
cd backend

php artisan migrate
```

---

# ▶️ Running the Project

Open two terminals.

### Terminal 1

```bash
cd backend

php artisan serve
```

### Terminal 2

```bash
cd frontend

quasar dev
```

Open:

Frontend

```
http://localhost:9000
```

Backend API

```
http://127.0.0.1:8000
```

---

# 🌿 Git Workflow

## Branch Structure

- **main** — Production-ready code.
- **dev** — Shared development branch.

Never commit directly to `main` or `dev`.

---

## Feature Branch Workflow

Update your local repository.

```bash
git checkout dev
git pull origin dev
```

Create a feature branch.

```bash
git checkout -b feature/login-interface
```

Commit your work.

```bash
git add .
git commit -m "Feat: Implemented secure multi-vendor login page layout"
```

Push your branch.

```bash
git push -u origin feature/login-interface
```

Create a Pull Request targeting:

```
base: dev ← compare: feature/login-interface
```

Once reviewed and approved, merge into `dev`.

---

# 📌 Future Enhancements

- AI-powered demand forecasting
- Sales analytics dashboard
- Vendor performance reports
- Payment gateway integration
- Delivery tracking
- Product reviews and ratings
- Push notifications
- Progressive Web App (PWA)
- Mobile application

---

# 📄 License

This project is developed for academic and capstone purposes.