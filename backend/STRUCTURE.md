# Backend Structure Documentation

## Project Organization

### Directory Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/              # Custom API Controllers
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   └── CartController.php
│   │   │   ├── Frontend/         # Frontend Controllers (if needed)
│   │   │   └── Controller.php    # Base Controller
│   │   ├── Middleware/
│   │   └── Kernel.php
│   ├── Models/                   # Custom Models
│   │   ├── CustomProduct.php
│   │   └── CustomCategory.php
│   ├── Console/
│   ├── Exceptions/
│   └── Providers/
├── packages/                     # Bagisto Core Packages
│   └── Webkul/
│       ├── Admin/               # Admin Panel
│       ├── Shop/                # Shop Frontend
│       ├── Product/             # Product Management
│       ├── Category/            # Category Management
│       ├── Customer/            # Customer Management
│       ├── Checkout/            # Cart & Checkout
│       └── RestApi/             # REST API Package
├── routes/
│   ├── web.php                  # Web Routes (Bagisto default)
│   └── api.php                  # API Routes (Custom + Bagisto API)
├── resources/
│   └── views/
│       └── custom/              # Custom Views
│           ├── layout.blade.php
│           └── products.blade.php
├── config/
│   ├── cors.php                 # CORS Configuration
│   └── ...
└── database/
    └── migrations/
```

## API Routes Structure

### Custom API Routes (for React Frontend)

**Base URL:** `http://127.0.0.1:8000/api`

#### Products API
- `GET /products` - Get all products
- `GET /products/search?q={term}` - Search products
- `GET /products/{id}` - Get single product

#### Categories API
- `GET /categories` - Get all categories
- `GET /categories/{id}` - Get single category
- `GET /categories/{id}/products` - Get category products

#### Cart API
- `GET /cart` - Get cart
- `POST /cart/add` - Add item to cart
- `PUT /cart/update` - Update cart
- `DELETE /cart/{id}` - Remove item from cart

### Bagisto REST API Routes

**Base URL:** `http://127.0.0.1:8000/api/v1`

#### Customer Authentication
- `POST /customer/register` - Register new customer
- `POST /customer/login` - Login customer
- `POST /customer/logout` - Logout customer
- `GET /customer/get` - Get customer details

#### Products
- `GET /products` - List all products
- `GET /products/{id}` - Get product details
- `GET /products/{id}/configurable-config` - Get configurable options
- `GET /products/{id}/additional-information` - Get additional info

#### Categories
- `GET /categories` - List all categories
- `GET /categories/{id}` - Get category details
- `GET /descendant-categories` - Get descendant categories

#### Customer Cart (Requires Auth)
- `GET /customer/cart` - Get customer cart
- `POST /customer/cart/add/{productId}` - Add product to cart
- `PUT /customer/cart/update` - Update cart items
- `DELETE /customer/cart/remove/{cartItemId}` - Remove cart item
- `DELETE /customer/cart/empty` - Empty cart
- `POST /customer/cart/coupon` - Apply coupon
- `DELETE /customer/cart/coupon` - Remove coupon

#### Customer Wishlist (Requires Auth)
- `GET /customer/wishlist` - Get wishlist
- `POST /customer/wishlist/{id}` - Add/Remove from wishlist
- `POST /customer/wishlist/{id}/move-to-cart` - Move to cart

#### Checkout (Requires Auth)
- `POST /customer/checkout/save-address` - Save shipping address
- `POST /customer/checkout/save-shipping` - Save shipping method
- `POST /customer/checkout/save-payment` - Save payment method
- `POST /customer/checkout/save-order` - Place order

#### Customer Orders (Requires Auth)
- `GET /customer/orders` - Get all orders
- `GET /customer/orders/{id}` - Get order details
- `POST /customer/orders/{id}/cancel` - Cancel order

## Controllers

### Custom API Controllers

Located in: `app/Http/Controllers/API/`

#### ProductController
```php
- index() - Get all products
- show($id) - Get single product
- search(Request $request) - Search products
```

#### CategoryController
```php
- index() - Get all categories
- show($id) - Get single category
- products($id) - Get category products
```

#### CartController
```php
- index() - Get cart
- store(Request $request) - Add to cart
- update(Request $request) - Update cart
- destroy($id) - Remove from cart
```

### Bagisto Controllers

Located in: `packages/Webkul/*/src/Http/Controllers/`

- **Shop Controllers:** Frontend functionality
- **Admin Controllers:** Admin panel functionality
- **RestApi Controllers:** API endpoints

## Models

### Custom Models

Located in: `app/Models/`

#### CustomProduct
```php
- Relations: category(), images()
- Scopes: active(), featured()
- Attributes: name, slug, price, sku, etc.
```

#### CustomCategory
```php
- Relations: parent(), children(), products()
- Scopes: active()
- Attributes: name, slug, description, etc.
```

### Bagisto Models

Located in: `packages/Webkul/*/src/Models/`

Core models for products, categories, customers, orders, etc.

## Views

### Custom Views

Located in: `resources/views/custom/`

- `layout.blade.php` - Base layout template
- `products.blade.php` - Products listing page

### Bagisto Views

Located in: `packages/Webkul/Shop/src/Resources/views/`

Default Bagisto shop views (Vue.js based)

## CORS Configuration

**File:** `config/cors.php`

```php
'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'],
'supports_credentials' => true,
```

This allows React frontend (port 3000) to communicate with Laravel backend (port 8000).

## Authentication

### Bagisto uses Laravel Sanctum for API authentication

**To get API token:**
1. Login via API: `POST /api/v1/customer/login`
2. Response includes `token`
3. Use token in headers: `Authorization: Bearer {token}`

## Usage Example for React Frontend

```javascript
// API Base URL
const API_URL = 'http://127.0.0.1:8000/api';

// Fetch Products
fetch(`${API_URL}/products`)
  .then(res => res.json())
  .then(data => console.log(data));

// Fetch Single Product
fetch(`${API_URL}/products/1`)
  .then(res => res.json())
  .then(data => console.log(data));

// Add to Cart (with auth token)
fetch(`${API_URL}/cart/add`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer {token}'
  },
  body: JSON.stringify({ product_id: 1, quantity: 1 })
});
```

## Database

**Database Name:** `bagisto_backend`
**Tables:** All Bagisto ecommerce tables (products, categories, customers, orders, etc.)

## Development URLs

- **Backend:** http://127.0.0.1:8000
- **Admin Panel:** http://127.0.0.1:8000/admin
  - Email: admin@example.com
  - Password: admin123
- **Frontend (React):** http://localhost:3000
- **API Endpoint:** http://127.0.0.1:8000/api

## Key Features

✅ **Organized Structure:**
- Custom controllers in `app/Http/Controllers/API/`
- Custom models in `app/Models/`
- Custom views in `resources/views/custom/`
- Bagisto core isolated in `packages/`

✅ **API Ready:**
- RESTful API routes configured
- CORS enabled for React frontend
- JWT authentication via Sanctum

✅ **Scalable:**
- Easy to add new controllers
- Model relationships defined
- Repository pattern (Bagisto default)

## Next Steps

1. Create frontend API service in React
2. Implement authentication flow
3. Build product listing & detail pages
4. Implement cart functionality
5. Add checkout process
