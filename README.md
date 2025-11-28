# Shiftness PHP Backend API

A PHP backend API for retrieving and managing users from a PostgreSQL database.

## Setup

1. **Install Dependencies**

```bash
composer install
```

2. **Database Connection**
   The `.env` file contains your PostgreSQL credentials:

- Host: `dpg-d4hl4np5pdvs739ae7g0-a`
- Port: `5432`
- Database: `shiftness_db`
- User: `shiftness_db_user`

3. **Database Schema**
   Make sure your PostgreSQL database has the following `users` table:

```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

4. **Run the Server**

```bash
php -S localhost:8000
```

## API Endpoints

### Get All Users

```
GET /api/users
```

**Query Parameters:**

- `sortBy`: Column to sort by (default: `id`)
  - Options: `id`, `username`, `email`, `created_at`, `updated_at`
- `sortOrder`: Sort order (default: `ASC`)
  - Options: `ASC`, `DESC`
- `limit`: Number of records to return
- `offset`: Number of records to skip

**Example:**

```
GET /api/users?sortBy=email&sortOrder=ASC&limit=10&offset=0
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "username": "john_doe",
      "email": "john@example.com",
      "created_at": "2025-11-28 10:00:00",
      "updated_at": "2025-11-28 10:00:00"
    }
  ],
  "count": 1
}
```

### Get User by ID

```
GET /api/users/{id}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    "created_at": "2025-11-28 10:00:00",
    "updated_at": "2025-11-28 10:00:00"
  }
}
```

### Create User

```
POST /api/users
```

**Request Body:**

```json
{
  "username": "john_doe",
  "email": "john@example.com",
  "password": "secure_password"
}
```

**Response:**

```json
{
  "success": true,
  "message": "User created successfully",
  "userId": 1
}
```

### Update User

```
PUT /api/users/{id}
```

**Request Body:**

```json
{
  "username": "new_username",
  "email": "newemail@example.com"
}
```

**Response:**

```json
{
  "success": true,
  "message": "User updated successfully"
}
```

### Delete User

```
DELETE /api/users/{id}
```

**Response:**

```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

## Features

- ✅ PostgreSQL database connection with PDO
- ✅ Get all users with sorting and pagination
- ✅ Get user by ID
- ✅ Create new user with password hashing (bcrypt)
- ✅ Update user information
- ✅ Delete user
- ✅ Error handling and JSON responses
- ✅ Environment variable configuration

## Security Notes

- Passwords are hashed using bcrypt (PASSWORD_BCRYPT)
- All queries use prepared statements to prevent SQL injection
- Keep your `.env` file secure and never commit it to version control
