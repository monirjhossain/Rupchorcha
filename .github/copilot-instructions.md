
# Copilot Instructions for Rupchorcha Ecommerce Codebase

## 🏗️ Big Picture Architecture
- **Monorepo:**
  - `backend/`: Laravel (Bagisto-based) API, admin, and business logic. Custom code in `app/`, Bagisto core in `packages/Webkul/` (do not modify core).
  - `frontend/`: React app (Create React App) for customer UI. Talks to backend via REST API (`/api/*`).
- **Data Flow:**
  - Backend manages all business data (orders, products, users) via Eloquent models and Bagisto repositories.
  - Frontend fetches data from backend APIs, displays, and submits forms.
- **Service Boundaries:**
  - API endpoints in `backend/routes/api.php` (see also `API_DOCUMENTATION.md`).
  - Web routes in `backend/routes/web.php` for server-rendered pages.

## ⚡ Developer Workflows
- **Backend:**
  - Run server: `php artisan serve` (or `php artisan serve --host=0.0.0.0` for LAN)
  - Migrate DB: `php artisan migrate` (migrations in `database/migrations/`)
  - Seed DB: `php artisan db:seed`
  - Run tests: `vendor/bin/phpunit` (config: `phpunit.xml`)
  - Build assets: `npm run dev` or `npm run production` (see `webpack.mix.js`)
- **Frontend:**
  - Start dev server: `npm start` in `frontend/`
  - Run tests: `npm test` in `frontend/`
  - Build: `npm run build` in `frontend/`

## 🛠️ Project-Specific Conventions
- **Custom code:**
  - Place new controllers in `app/Http/Controllers/Frontend/` (web) or `app/Http/Controllers/API/` (API for React)
  - Views in `resources/views/frontend/`
  - Models in `app/Models/`
  - Add routes in `routes/web.php` (web) or `routes/api.php` (API)
- **Bagisto core:**
  - Use repositories from `packages/Webkul/*/src/Repositories/` in your controllers
  - Do **not** modify anything in `packages/Webkul/` directly
- **API:**
  - Return JSON from API controllers using `response()->json([...])`
  - Enable CORS in `config/cors.php` for frontend-backend communication
- **Configuration:**
  - App/env config in `.env` and `config/`
  - External services in `config/services.php`

## 🔗 Integration Points & Dependencies
- **Backend:**
  - Laravel, Bagisto, Webkul packages (`composer.json`)
  - CORS for React (`config/cors.php`)
  - JWT auth via Sanctum (`config/sanctum.php`)
- **Frontend:**
  - React, dependencies in `frontend/package.json`
  - Communicates with backend via REST API

## 📁 Key Files & Navigation
- `app/Http/Controllers/Frontend/` – Web controllers
- `app/Http/Controllers/API/` – API controllers
- `app/Models/` – Custom models
- `resources/views/frontend/` – Views
- `routes/web.php` – Web routes
- `routes/api.php` – API routes
- `config/` – All configuration
- `packages/Webkul/` – Bagisto core (read-only)

## 🚦 Examples & Best Practices
- **Add API endpoint:**
  1. Create controller in `app/Http/Controllers/API/`
  2. Add route in `routes/api.php`
  3. Return JSON with `response()->json([...])`
- **Add web page:**
  1. Create controller in `app/Http/Controllers/Frontend/`
  2. Add view in `resources/views/frontend/`
  3. Add route in `routes/web.php`
- **DO:**
  - Use Bagisto repositories in controllers
  - Add custom models in `app/Models/`
  - Keep customizations outside `packages/Webkul/`
- **DON'T:**
  - Modify Bagisto core or migrations
  - Edit files in `packages/Webkul/`

## 🧭 Quick Reference
- **Backend:** http://127.0.0.1:8000
- **Admin:** http://127.0.0.1:8000/admin (admin@example.com/admin123)
- **Frontend:** http://localhost:3000
- **API:** http://127.0.0.1:8000/api

---

**For AI agents:**
- Always check for existing conventions in `packages/Webkul/` before introducing new modules
- Use RESTful patterns for backend APIs and keep frontend API calls consistent
- Reference config files for integration details and environment variables
- Update documentation in `API_DOCUMENTATION.md` and `README.md` for architectural changes
