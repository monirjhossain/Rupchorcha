# API Documentation - Product Options

Complete API endpoints for all product-related options: Categories, Brands, Tags, Attributes, and Bundles.

## Base URL
```
http://your-domain.com/api
```

## Response Format
All API responses follow this JSON structure:
```json
{
  "success": true/false,
  "data": {},
  "message": "Optional message",
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7,
    "from": 1,
    "to": 15
  }
}
```

---

## 1. Categories API

### Get All Categories
**GET** `/api/categories`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 50)
- `parent_id` (optional): Filter by parent category ID

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "description": "...",
      "image": "http://...",
      "parent_id": null,
      "status": true,
      "products_count": 45
    }
  ],
  "pagination": {...}
}
```

### Get Single Category
**GET** `/api/categories/{id}`

### Get Category Products
**GET** `/api/categories/{id}/products`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 12)

---

## 2. Brands API

### Get All Brands
**GET** `/api/brands`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Samsung",
      "admin_name": "Samsung",
      "swatch_value": null
    }
  ]
}
```

### Get Single Brand
**GET** `/api/brands/{id}`

### Get Brand Products
**GET** `/api/brands/{id}/products`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 12)

---

## 3. Tags API ✅ (Enhanced)

### Get All Tags
**GET** `/api/tags`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `status` (optional): Filter by status (0 or 1)
- `search` (optional): Search by tag name
- `all` (optional): Set to 'true' to get all tags without pagination

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "New Arrival",
      "slug": "new-arrival",
      "color": "#3498db",
      "status": true,
      "products_count": 12
    }
  ],
  "pagination": {...}
}
```

### Get Single Tag
**GET** `/api/tags/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "New Arrival",
    "slug": "new-arrival",
    "color": "#3498db",
    "status": true,
    "products_count": 12,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

### Get Tag Products
**GET** `/api/tags/{id}/products`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 12)

**Response:**
```json
{
  "success": true,
  "data": {
    "tag": {
      "id": 1,
      "name": "New Arrival",
      "slug": "new-arrival",
      "color": "#3498db"
    },
    "products": [
      {
        "id": 1,
        "sku": "PROD-001",
        "name": "Product Name",
        "slug": "product-name",
        "price": 99.99,
        "special_price": 79.99,
        "cost_price": 50.00,
        "status": true,
        "image": "http://...",
        "categories": ["Electronics", "Phones"],
        "brands": ["Samsung"]
      }
    ]
  },
  "pagination": {...}
}
```

---

## 4. Attributes API ✅ (New)

### Get All Attributes
**GET** `/api/attributes`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 50)
- `filterable` (optional): Filter by filterable attributes (true/false)
- `visible` (optional): Filter by visible on front (true/false)
- `code` (optional): Filter by attribute code (e.g., 'color', 'size')

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "color",
      "admin_name": "Color",
      "type": "select",
      "position": 1,
      "is_required": false,
      "is_unique": false,
      "is_filterable": true,
      "is_configurable": true,
      "is_visible_on_front": true,
      "options": [
        {
          "id": 1,
          "admin_name": "Red",
          "label": "Red",
          "swatch_value": "#FF0000",
          "sort_order": 1
        },
        {
          "id": 2,
          "admin_name": "Blue",
          "label": "Blue",
          "swatch_value": "#0000FF",
          "sort_order": 2
        }
      ]
    }
  ],
  "pagination": {...}
}
```

### Get Single Attribute
**GET** `/api/attributes/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "code": "color",
    "admin_name": "Color",
    "type": "select",
    "position": 1,
    "is_required": false,
    "is_unique": false,
    "validation": null,
    "is_filterable": true,
    "is_configurable": true,
    "is_visible_on_front": true,
    "is_user_defined": true,
    "swatch_type": "color",
    "options": [...]
  }
}
```

### Get Products by Attribute Option
**GET** `/api/attributes/options/{optionId}/products`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 12)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sku": "PROD-001",
      "name": "Product Name",
      "url_key": "product-name",
      "price": 99.99,
      "special_price": 79.99,
      "status": true,
      "image": "http://...",
      "in_stock": true,
      "qty": 50,
      "categories": ["Electronics"]
    }
  ],
  "pagination": {...}
}
```

---

## 5. Bundles API ✅ (New - Frequently Bought Together)

### Get All Bundles
**GET** `/api/bundles`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `product_id` (optional): Filter bundles by specific product ID
- `search` (optional): Search by product name

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product": {
        "id": 1,
        "name": "iPhone 15 Pro",
        "sku": "IPHONE-15-PRO",
        "price": 999.99,
        "image": "http://..."
      },
      "bundle_product": {
        "id": 2,
        "name": "iPhone Case",
        "sku": "CASE-001",
        "price": 29.99,
        "image": "http://..."
      },
      "discount_percentage": 10,
      "discounted_price": 26.99,
      "total_price": 1026.98,
      "position": 1,
      "created_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "pagination": {...}
}
```

### Get Single Bundle
**GET** `/api/bundles/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "product": {
      "id": 1,
      "name": "iPhone 15 Pro",
      "sku": "IPHONE-15-PRO",
      "price": 999.99,
      "description": "Latest iPhone model",
      "image": "http://...",
      "status": true
    },
    "bundle_product": {
      "id": 2,
      "name": "iPhone Case",
      "sku": "CASE-001",
      "price": 29.99,
      "description": "Protective case",
      "image": "http://...",
      "status": true
    },
    "discount_percentage": 10,
    "discounted_price": 26.99,
    "savings": 3.00,
    "total_price": 1026.98,
    "position": 1,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

### Get Product Bundles (Frequently Bought Together)
**GET** `/api/bundles/product/{productId}`

**Response:**
```json
{
  "success": true,
  "data": {
    "product_id": 1,
    "product_name": "iPhone 15 Pro",
    "bundles": [
      {
        "id": 1,
        "product": {
          "id": 2,
          "name": "iPhone Case",
          "sku": "CASE-001",
          "price": 29.99,
          "image": "http://...",
          "status": true
        },
        "discount_percentage": 10,
        "discounted_price": 26.99,
        "savings": 3.00
      },
      {
        "id": 2,
        "product": {
          "id": 3,
          "name": "Screen Protector",
          "sku": "SCREEN-001",
          "price": 9.99,
          "image": "http://...",
          "status": true
        },
        "discount_percentage": 15,
        "discounted_price": 8.49,
        "savings": 1.50
      }
    ]
  }
}
```

---

## 6. Products API

### Get All Products
**GET** `/api/products`

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 12)
- `category_id` (optional): Filter by category
- `min_price` (optional): Minimum price filter
- `max_price` (optional): Maximum price filter
- `sort` (optional): Sort order (price_asc, price_desc, name_asc, name_desc, newest)

### Search Products
**GET** `/api/products/search?q=keyword`

### Get Single Product
**GET** `/api/products/{id}`

---

## 7. Sliders API

### Get All Sliders
**GET** `/api/sliders`

---

## Error Responses

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Failed to fetch data",
  "error": "Error details..."
}
```

---

## Example Usage

### JavaScript (Fetch API)
```javascript
// Get all tags
fetch('http://your-domain.com/api/tags?per_page=20')
  .then(response => response.json())
  .then(data => {
    console.log(data.data); // Array of tags
  });

// Get tag products
fetch('http://your-domain.com/api/tags/1/products')
  .then(response => response.json())
  .then(data => {
    console.log(data.data.products); // Array of products
  });

// Get attributes with options
fetch('http://your-domain.com/api/attributes?filterable=true')
  .then(response => response.json())
  .then(data => {
    console.log(data.data); // Filterable attributes
  });

// Get product bundles (frequently bought together)
fetch('http://your-domain.com/api/bundles/product/1')
  .then(response => response.json())
  .then(data => {
    console.log(data.data.bundles); // Related products
  });
```

### React Example
```jsx
import { useEffect, useState } from 'react';

function ProductBundles({ productId }) {
  const [bundles, setBundles] = useState([]);

  useEffect(() => {
    fetch(`/api/bundles/product/${productId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setBundles(data.data.bundles);
        }
      });
  }, [productId]);

  return (
    <div>
      <h3>Frequently Bought Together</h3>
      {bundles.map(bundle => (
        <div key={bundle.id}>
          <h4>{bundle.product.name}</h4>
          <p>Price: ${bundle.discounted_price}</p>
          <p>Save: ${bundle.savings}</p>
        </div>
      ))}
    </div>
  );
}
```

---

## Summary

✅ **Categories API** - Fully functional  
✅ **Brands API** - Fully functional (uses Bagisto AttributeOption)  
✅ **Tags API** - Enhanced with custom Tag model  
✅ **Attributes API** - New implementation with Bagisto Attribute system  
✅ **Bundles API** - New implementation for Frequently Bought Together  
✅ **Products API** - Fully functional  
✅ **Cart API** - Fully functional  
✅ **Orders API** - Fully functional  
✅ **Shipping API** - Fully functional  

All API endpoints follow RESTful conventions and return consistent JSON responses with proper error handling.
