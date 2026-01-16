# Material Management API Documentation

This document provides complete API documentation for the Material Management system, including Material Categories and Materials.

## Table of Contents
1. [Seeder Routes](#seeder-routes)
2. [Material Category Routes](#material-category-routes)
3. [Material Routes](#material-routes)
4. [Request/Response Examples](#requestresponse-examples)

---

## Seeder Routes

### Run All Seeders
**Endpoint:** `GET /api/seed/all`  
**Description:** Runs all seeders globally. Only runs seeders that haven't been executed before (checks for existing data).

**Response:**
```json
{
  "status": "success",
  "data": {
    "material_categories": "Seeded successfully",
    "materials": "Seeded successfully"
  },
  "message": "Seeders executed successfully"
}
```

**If already seeded:**
```json
{
  "status": "success",
  "data": {
    "material_categories": "Already seeded (skipped)",
    "materials": "Already seeded (skipped)"
  },
  "message": "Seeders executed successfully"
}
```

### Run Specific Seeder
**Endpoint:** `POST /api/seed/run`  
**Body:**
```json
{
  "seeder": "MaterialCategorySeeder"
}
```

**Response:**
```json
{
  "status": "success",
  "data": null,
  "message": "Seeder MaterialCategorySeeder executed successfully"
}
```

---

## Material Category Routes

All routes require authentication (`auth:sanctum` middleware).

### List All Material Categories
**Endpoint:** `GET /api/material-categories`  
**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "SOLAR PANELS",
      "code": "A",
      "description": "Solar panel products",
      "sort_order": 1,
      "is_active": true,
      "created_at": "2026-01-16T17:00:00.000000Z",
      "updated_at": "2026-01-16T17:00:00.000000Z",
      "materials": [
        {
          "id": 1,
          "material_category_id": 1,
          "name": "455W Monofacial Jinko Solar Panel",
          "unit": "Nos",
          "warranty": 10,
          "rate": "0.00",
          "selling_rate": "0.00",
          "profit": "0.00",
          "sort_order": 0,
          "is_active": true
        }
      ]
    }
  ],
  "message": "Material categories fetched successfully."
}
```

### Get Single Material Category
**Endpoint:** `GET /api/material-categories/{id}`  
**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "SOLAR PANELS",
    "code": "A",
    "description": "Solar panel products",
    "sort_order": 1,
    "is_active": true,
    "created_at": "2026-01-16T17:00:00.000000Z",
    "updated_at": "2026-01-16T17:00:00.000000Z",
    "materials": []
  },
  "message": "Material category fetched successfully."
}
```

### Create Material Category
**Endpoint:** `POST /api/material-categories`  
**Request Body:**
```json
{
  "name": "SOLAR PANELS",
  "code": "A",
  "description": "Solar panel products",
  "sort_order": 1,
  "is_active": true
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "SOLAR PANELS",
    "code": "A",
    "description": "Solar panel products",
    "sort_order": 1,
    "is_active": true,
    "created_at": "2026-01-16T17:00:00.000000Z",
    "updated_at": "2026-01-16T17:00:00.000000Z"
  },
  "message": "Material category created successfully."
}
```

### Update Material Category
**Endpoint:** `PUT /api/material-categories/{id}` or `POST /api/material-categories/{id}/update`  
**Request Body:**
```json
{
  "name": "SOLAR PANELS UPDATED",
  "description": "Updated description",
  "is_active": false
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "SOLAR PANELS UPDATED",
    "code": "A",
    "description": "Updated description",
    "sort_order": 1,
    "is_active": false,
    "created_at": "2026-01-16T17:00:00.000000Z",
    "updated_at": "2026-01-16T17:30:00.000000Z"
  },
  "message": "Material category updated successfully."
}
```

### Delete Material Category
**Endpoint:** `DELETE /api/material-categories/{id}`  
**Response:**
```json
{
  "status": "success",
  "data": null,
  "message": "Material category deleted successfully."
}
```

**Error Response (if category has materials):**
```json
{
  "status": "error",
  "message": "Cannot delete category with existing materials."
}
```

---

## Material Routes

All routes require authentication (`auth:sanctum` middleware).

### List All Materials
**Endpoint:** `GET /api/materials`  
**Query Parameters:**
- `category_id` (optional) - Filter by category ID
- `is_active` (optional) - Filter by active status (true/false)
- `search` (optional) - Search by material name

**Example:** `GET /api/materials?category_id=1&is_active=true&search=solar`

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "material_category_id": 1,
      "name": "455W Monofacial Jinko Solar Panel",
      "unit": "Nos",
      "warranty": 10,
      "rate": "0.00",
      "selling_rate": "0.00",
      "profit": "0.00",
      "sort_order": 0,
      "is_active": true,
      "created_at": "2026-01-16T17:00:00.000000Z",
      "updated_at": "2026-01-16T17:00:00.000000Z",
      "category": {
        "id": 1,
        "name": "SOLAR PANELS",
        "code": "A"
      }
    }
  ],
  "message": "Materials fetched successfully."
}
```

### Get Materials by Category
**Endpoint:** `GET /api/materials/category/{categoryId}`  
**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "material_category_id": 1,
      "name": "455W Monofacial Jinko Solar Panel",
      "unit": "Nos",
      "warranty": 10,
      "rate": "0.00",
      "selling_rate": "0.00",
      "profit": "0.00",
      "sort_order": 0,
      "is_active": true
    }
  ],
  "message": "Materials fetched by category successfully."
}
```

### Get Single Material
**Endpoint:** `GET /api/materials/{id}`  
**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "material_category_id": 1,
    "name": "455W Monofacial Jinko Solar Panel",
    "unit": "Nos",
    "warranty": 10,
    "rate": "50000.00",
    "selling_rate": "60000.00",
    "profit": "10000.00",
    "sort_order": 0,
    "is_active": true,
    "created_at": "2026-01-16T17:00:00.000000Z",
    "updated_at": "2026-01-16T17:00:00.000000Z",
    "category": {
      "id": 1,
      "name": "SOLAR PANELS",
      "code": "A"
    }
  },
  "message": "Material fetched successfully."
}
```

### Create Material
**Endpoint:** `POST /api/materials`  
**Request Body:**
```json
{
  "material_category_id": 1,
  "name": "455W Monofacial Jinko Solar Panel",
  "unit": "Nos",
  "warranty": 10,
  "rate": 50000.00,
  "selling_rate": 60000.00,
  "profit": 10000.00,
  "sort_order": 0,
  "is_active": true
}
```

**Note:** `profit` is auto-calculated if not provided: `profit = selling_rate - rate`

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "material_category_id": 1,
    "name": "455W Monofacial Jinko Solar Panel",
    "unit": "Nos",
    "warranty": 10,
    "rate": "50000.00",
    "selling_rate": "60000.00",
    "profit": "10000.00",
    "sort_order": 0,
    "is_active": true,
    "created_at": "2026-01-16T17:00:00.000000Z",
    "updated_at": "2026-01-16T17:00:00.000000Z",
    "category": {
      "id": 1,
      "name": "SOLAR PANELS",
      "code": "A"
    }
  },
  "message": "Material created successfully."
}
```

### Update Material
**Endpoint:** `PUT /api/materials/{id}` or `POST /api/materials/{id}/update`  
**Request Body:**
```json
{
  "name": "455W Monofacial Jinko Solar Panel Updated",
  "rate": 55000.00,
  "selling_rate": 65000.00,
  "warranty": 12
}
```

**Note:** `profit` is auto-calculated if `rate` or `selling_rate` is updated

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "material_category_id": 1,
    "name": "455W Monofacial Jinko Solar Panel Updated",
    "unit": "Nos",
    "warranty": 12,
    "rate": "55000.00",
    "selling_rate": "65000.00",
    "profit": "10000.00",
    "sort_order": 0,
    "is_active": true,
    "created_at": "2026-01-16T17:00:00.000000Z",
    "updated_at": "2026-01-16T17:30:00.000000Z",
    "category": {
      "id": 1,
      "name": "SOLAR PANELS",
      "code": "A"
    }
  },
  "message": "Material updated successfully."
}
```

### Delete Material
**Endpoint:** `DELETE /api/materials/{id}`  
**Response:**
```json
{
  "status": "success",
  "data": null,
  "message": "Material deleted successfully."
}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "status": "error",
  "message": "Validation failed.",
  "errors": {
    "name": ["The name field is required."],
    "unit": ["The unit field is required."]
  }
}
```

### Not Found Error (404)
```json
{
  "status": "error",
  "message": "Material not found."
}
```

### Server Error (500)
```json
{
  "status": "error",
  "message": "Failed to fetch materials."
}
```

---

## Field Descriptions

### Material Category Fields
- `id` - Unique identifier
- `name` - Category name (required, max 255 chars)
- `code` - Category code (optional, unique, max 10 chars, e.g., "A", "B", "C")
- `description` - Category description (optional)
- `sort_order` - Display order (optional, integer, default: 0)
- `is_active` - Active status (optional, boolean, default: true)

### Material Fields
- `id` - Unique identifier
- `material_category_id` - Foreign key to material_categories (required)
- `name` - Material name/description (required, max 255 chars)
- `unit` - Unit of measurement (required, max 50 chars, e.g., "Nos", "Mtrs")
- `warranty` - Warranty period in years (optional, integer)
- `rate` - Cost rate (optional, decimal, default: 0.00)
- `selling_rate` - Selling price (optional, decimal, default: 0.00)
- `profit` - Profit amount (optional, decimal, auto-calculated if not provided)
- `sort_order` - Display order (optional, integer, default: 0)
- `is_active` - Active status (optional, boolean, default: true)

---

## Admin Panel Integration Guide

### 1. Initial Setup
First, run the seeders to populate initial data:
```javascript
// Call this once to seed all data
fetch('/api/seed/all')
  .then(res => res.json())
  .then(data => console.log(data));
```

### 2. Material Categories Management

#### List Categories
```javascript
fetch('/api/material-categories', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json'
  }
})
.then(res => res.json())
.then(data => {
  // data.data contains array of categories
  console.log(data.data);
});
```

#### Create Category
```javascript
fetch('/api/material-categories', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    name: 'SOLAR PANELS',
    code: 'A',
    description: 'Solar panel products',
    sort_order: 1,
    is_active: true
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

### 3. Materials Management

#### List Materials with Filters
```javascript
// Get all materials
fetch('/api/materials?category_id=1&is_active=true', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json'
  }
})
.then(res => res.json())
.then(data => {
  // data.data contains array of materials
  console.log(data.data);
});
```

#### Create Material
```javascript
fetch('/api/materials', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    material_category_id: 1,
    name: '455W Monofacial Jinko Solar Panel',
    unit: 'Nos',
    warranty: 10,
    rate: 50000.00,
    selling_rate: 60000.00,
    sort_order: 0,
    is_active: true
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

#### Update Material
```javascript
fetch('/api/materials/1', {
  method: 'PUT',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    rate: 55000.00,
    selling_rate: 65000.00
    // profit will be auto-calculated
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

---

## Notes

1. **Profit Auto-Calculation:** The profit field is automatically calculated as `selling_rate - rate` if not explicitly provided during create/update operations.

2. **Units:** Common units include:
   - `Nos` - Numbers/Pieces
   - `Mtrs` - Meters
   - Custom units can be used as needed

3. **Warranty:** Warranty is stored in years. Can be null for services (like installation fees, delivery fees).

4. **Duplicate Prevention:** The seeder routes check for existing data before seeding to prevent duplicates.

5. **Category Deletion:** Categories cannot be deleted if they have associated materials. Delete all materials first, then delete the category.
