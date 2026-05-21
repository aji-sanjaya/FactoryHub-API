# FactoryHub - API

**FactoryHub - API** is a web-based front-end portal built as a third-party application for the open-source ERP ecosystem. The application serves as an operational front end for business users, while the main transactional backbone remains **iDempiere v12**.

In this architecture, FactoryHub - API provides a more focused user experience for day-to-day operations, approvals, reporting, and document handling, while iDempiere acts as the core ERP back end responsible for master data, accounting logic, document lifecycle, and enterprise transaction processing.

## Project Overview

This project is being prepared for implementation at **PT Adyawinsa Plastic Industries (API)** in **2026**. The current scope covers a growing set of operational modules that support procurement, warehouse, sales, finance, approvals, and reporting workflows. The application is intended to continue evolving after the initial rollout, with additional modules and refinements planned as business requirements mature.

## What Is iDempiere?

**iDempiere** is an open-source ERP and CRM platform designed for integrated enterprise operations. It provides the core business engine for areas such as procurement, sales, inventory, accounting, workflow, and document processing. In this project, **iDempiere v12** functions as the system of record and transactional back end, while FactoryHub - API delivers a tailored web interface aligned with the operational needs of the implementation.

## Current Functional Scope

Based on the menu structure and active application routes in this repository, the implemented modules currently include:

* **Dashboards** for procurement, petty cash, sales, delivery, and warehouse monitoring
* **Procurement workflows** including Purchase Requisition, Purchase Order, Material Receipt, Approval PR, and Approval PO
* **Sales and distribution workflows** including Sales Order, Delivery Schedule, Customer Shipment, and related document processing
* **Finance workflows** including AP Invoice, AP Payment, AR Invoice, AR Receipt, Petty Cash Request, and Petty Cash Closing
* **Reporting features** including Procurement Report, Purchase Order Report, Aging AP Invoice Report, and Aging AR Invoice Report
* **Supporting capabilities** such as authentication, role-based access flow, document printing, attachment handling, journal export, and selected repost processes

## Update Log

Recent project updates are tracked below.

| Timestamp | Description |
| --- | --- |
| 2026-05-21 19:11:26 WIB | Update Module Requisition dan Purchase Order |

## Technology Stack

FactoryHub - API is currently built on:

* **Laravel 12** for the application framework and backend orchestration
* **Tailwind CSS v4** for the user interface layer
* **Alpine.js** for lightweight client-side interactivity
* **Vite** for frontend asset bundling and development workflow

## 📋 Requirements
To set up FactoryHub - API, make sure your environment includes:

* **PHP 8.2+**
* **Composer** (PHP dependency manager)
* **Node.js 18+** and **npm** (for compiling frontend assets)
* **Database** - Works with SQLite (default), MySQL, or PostgreSQL

### Check Your Environment

Verify your installations:

```bash
php -v
composer -V
node -v
npm -v
```

## 🚀 Quick Start Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/aji-sanjaya/FactoryHub-API.git
cd FactoryHub-API
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

This command will install all Laravel dependencies defined in `composer.json`.

### Step 3: Install Node.js Dependencies

```bash
npm install
```

Or if you prefer yarn or pnpm:

```bash
# Using yarn
yarn install

# Using pnpm
pnpm install
```

### Step 4: Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

**For Windows users:**

```bash
copy .env.example .env
```

**Or create it programmatically:**

```bash
php -r "file_exists('.env') || copy('.env.example', '.env');"
```

### Step 5: Generate Application Key

```bash
php artisan key:generate
```

This creates a unique encryption key for your application.

### Step 6: Configure Database

#### Option A: Using MySQL/PostgreSQL

Update your `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=factoryhub_api
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Create the database:

```bash
# MySQL
mysql -u root -p -e "CREATE DATABASE factoryhub_api;"

# PostgreSQL
createdb factoryhub_api
```

Run migrations:

```bash
php artisan migrate
```

### Step 7: (Optional) Seed the Database

If you want application menu and supporting seed data:

```bash
php artisan db:seed
```

### Step 8: Storage Link

Create a symbolic link for file storage:

```bash
php artisan storage:link
```

## 🏃 Running the Application

### Development Mode (Recommended)

The easiest way to start development is using the built-in script:

```bash
composer run dev
```

This single command starts:
- ✅ Laravel development server (http://localhost:8000)
- ✅ Vite dev server for hot module reloading
- ✅ Queue worker for background jobs
- ✅ Log monitoring

**Access your application at:** [http://localhost:8000](http://localhost:8000)

### Manual Development Setup

If you prefer to run services individually in separate terminal windows:

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Frontend Assets:**
```bash
npm run dev
```

### Building for Production

#### Build Frontend Assets

```bash
npm run build
```

#### Optimize Laravel

```bash
# Clear and cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

#### Production Environment

Update your `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```


## 🧪 Testing

Run the test suite using Pest:

```bash
composer run test
```

Or manually:

```bash
php artisan test
```

Run with coverage:

```bash
php artisan test --coverage
```

Run specific tests:

```bash
php artisan test --filter=ExampleTest
```

## 📜 Available Commands

### Composer Scripts

```bash
# Start development environment
composer run dev

# Run tests
composer run test

# Code formatting (if configured)
composer run format

# Static analysis (if configured)
composer run analyze
```

### NPM Scripts

```bash
# Start Vite dev server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Lint JavaScript/TypeScript
npm run lint

# Format code
npm run format
```

### Artisan Commands

```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migrations with seeding
php artisan migrate:fresh --seed

# Generate application key
php artisan key:generate

# Clear all caches
php artisan optimize:clear

# Cache everything for production
php artisan optimize

# Create symbolic link for storage
php artisan storage:link

# Start queue worker
php artisan queue:work

# List all routes
php artisan route:list

# Create a new controller
php artisan make:controller YourController

# Create a new model
php artisan make:model YourModel -m

# Create a new migration
php artisan make:migration create_your_table
```

## 📁 Project Structure

```
FactoryHub-API/
├── app/                    # Application logic
│   ├── Http/              # Controllers, Middleware, Requests
│   ├── Models/            # Eloquent models
│   └── Providers/         # Service providers
├── bootstrap/             # Framework bootstrap files
├── config/                # Configuration files
├── database/              # Migrations, seeders, factories
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── public/                # Public assets (entry point)
│   ├── build/            # Compiled assets (generated)
│   └── index.php         # Application entry point
├── resources/             # Views and raw assets
│   ├── css/              # Stylesheets (Tailwind)
│   ├── js/               # JavaScript files (Alpine.js)
│   └── views/            # Blade templates
├── routes/                # Route definitions
│   ├── web.php           # Web routes
│   ├── api.php           # API routes
│   └── console.php       # Console routes
├── storage/               # Logs, cache, uploads
│   ├── app/
│   ├── framework/
│   └── logs/
├── tests/                 # Pest test files
│   ├── Feature/
│   └── Unit/
├── .env.example           # Example environment file
├── artisan                # Artisan CLI
├── composer.json          # PHP dependencies
├── package.json           # Node dependencies
├── vite.config.js         # Vite configuration
└── tailwind.config.js     # Tailwind configuration
```

## 🐛 Troubleshooting

### Common Issues

#### "Class not found" errors
```bash
composer dump-autoload
```

#### Permission errors on storage/bootstrap/cache
```bash
chmod -R 775 storage bootstrap/cache
```

#### NPM build errors
```bash
rm -rf node_modules package-lock.json
npm install
```

#### Clear all caches
```bash
php artisan optimize:clear
```

#### Database connection errors
- Check `.env` database credentials
- Ensure database server is running
- Verify database exists

## 🔄 Update Log

### [2025-12-29]
- Added Date Picker in Statistics Chart

## License

Refer to our [LICENSE](https://tailadmin.com/license) page for more information.
