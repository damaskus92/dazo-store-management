# Store Management System

Monorepo untuk aplikasi **Store Management System** yang mendukung pengelolaan toko secara hierarkis, pengguna, kasir, produk, dan transaksi penjualan. Sistem ini dibangun dengan arsitektur backend RESTful API dan frontend Single Page Application (SPA). Repository ini dibuat sebagai bagian dari technical assessment.

## Tech Stack

### Backend

- Laravel 11
- RESTful API
- JWT Authentication

### Frontend

- Vue 3
- Vite
- Single Page Application (SPA)

## Fitur Utama

Sistem ini menyediakan fitur lengkap untuk mengelola toko dengan dukungan multi-level store (center, branch, retail) dan role-based access (super_admin, admin, cashier).

### Authentication

- Login dengan email dan password untuk mendapatkan JWT token
- Endpoint `/me` untuk mendapatkan data user yang sedang login
- Logout untuk invalidate session

### Store Management (Admin/Super Admin)

- Create, read, update, delete (CRUD) store
- Dukungan hierarki store: center → branch → retail (dengan parent_id)
- List store dengan pagination dan search

### User Management (Super Admin)

- CRUD user lengkap (termasuk assign role dan store)
- Role mencakup super_admin, admin, dan cashier

### Cashier Management (Admin)

- CRUD cashier khusus di dalam store milik admin
- Cashier otomatis di-assign ke store admin yang bersangkutan

### Product Management (Admin)

- CRUD produk di dalam store milik admin
- Field: name, SKU (optional), price, description, is_active

### Sale/Transaction Management (Cashier & Admin)

- Create transaksi baru (checkout) dengan multiple items
- List transaksi dengan filter: search by transaction number, date range (start_date & end_date), pagination
- View detail transaksi lengkap (items, payments, cashier info)
- Process payment terpisah via endpoint `/sales/pay` (support multiple payments jika diperlukan)

## API Documentation

API dibangun sesuai spesifikasi OpenAPI 3.0 dan dapat diakses di base URL: `http://localhost:8000/api` (development).

Dokumentasi lengkap tersedia dalam file `openapi.yaml` di direktori docs.

## Getting Started

1. Setup backend Laravel (migrate, seed, dll.)
2. Jalankan server API: `php artisan serve`
3. Setup frontend Vue dengan Vite
4. Jalankan frontend: `npm run dev`
