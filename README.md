# Cashfloo

Aplikasi Manajemen Keuangan Pribadi yang mobile-first dan user-friendly.

## 🚀 Fitur Utama

- **Autentikasi**: Register, login, logout dengan email & password
- **Transaksi**: Tambah pemasukan dan pengeluaran
- **Kategori**: Kelola kategori transaksi dengan ikon dan warna
- **Anggaran**: Buat dan pantau anggaran bulanan
- **Tujuan**: Set dan track tujuan keuangan
- **Dashboard**: Overview keuangan yang informatif
- **Mobile-First**: Desain responsif untuk semua device

## 🛠️ Teknologi

- **Backend**: Laravel 12
- **Frontend**: Livewire 3 + TailwindCSS 4
- **Database**: SQLite (development), MySQL/PostgreSQL (production)
- **Authentication**: Laravel Breeze (built-in)
- **Icons**: Heroicons

## 📱 Fitur PWA

- Mobile-first design
- Responsive layout
- Fast loading
- Modern UI/UX

## 🚀 Instalasi

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- SQLite/MySQL/PostgreSQL

### Setup

1. **Clone repository**
```bash
git clone https://github.com/haninggrk/Cashfloo.git
cd Cashfloo
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database setup**
```bash
php artisan migrate
php artisan db:seed --class=CategorySeeder
```

5. **Build assets**
```bash
npm run build
```

6. **Run development server**
```bash
php artisan serve
npm run dev
```

## 📊 Database Schema

### Tables

- **users**: User accounts dan profil
- **categories**: Kategori transaksi
- **transactions**: Data transaksi keuangan
- **budgets**: Anggaran bulanan
- **goals**: Tujuan keuangan

### Default Categories

**Pengeluaran:**
- Makan (🍰)
- Transportasi (🚚)
- Tagihan (📄)
- Belanja (🛍️)
- Lain-lain (⋯)

**Pemasukan:**
- Gaji (💰)
- Bonus (🎁)
- Investasi (📈)
- Lain-lain (➕)

## 🎨 UI Components

### Color Scheme
- **Primary**: Money Orange (#FF6B35)
- **Secondary**: Money Red (#D62828)
- **Dark**: Money Dark (#1A1A1A)
- **Light**: Money Light (#F8F9FA)

### Components
- `.btn-primary`: Button utama
- `.btn-secondary`: Button sekunder
- `.card`: Card container
- `.input-field`: Input form

## 🔐 Authentication

### Routes
- `GET /login` - Login page
- `POST /login` - Login process
- `GET /register` - Register page
- `POST /register` - Register process
- `POST /logout` - Logout
- `GET /stats` - Statistics (protected)

### Features
- Email + Password authentication
- Google Sign-In (placeholder)
- Phone verification (placeholder)
- Multi-user support

## 📱 Mobile-First Design

- Responsive grid system
- Touch-friendly buttons
- Optimized for mobile devices
- Fast loading times
- Minimal loading states

## 🚀 Development

### Commands

```bash
# Create new migration
php artisan make:migration create_table_name

# Run migrations
php artisan migrate

# Create seeder
php artisan make:seeder SeederName

# Run seeder
php artisan db:seed --class=SeederName

# Create model
php artisan make:model ModelName

# Create controller
php artisan make:controller ControllerName
```

### File Structure

```
cashfloo/
├── app/
│   ├── Http/Controllers/
│   │   └── AuthController.php
│   └── Models/
│       ├── User.php
│       ├── Category.php
│       ├── Transaction.php
│       ├── Budget.php
│       └── Goal.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── auth/
│   │   ├── layouts/
│   │   └── dashboard.blade.php
│   └── css/
│       └── app.css
├── routes/
│   └── web.php
└── tailwind.config.js
```

## 📝 API Endpoints

### Authentication
- `POST /api/register`
- `POST /api/login`
- `POST /api/logout`

### Transactions
- `GET /api/transactions`
- `POST /api/transactions`
- `PUT /api/transactions/{id}`
- `DELETE /api/transactions/{id}`

### Categories
- `GET /api/categories`
- `POST /api/categories`

### Budgets
- `GET /api/budgets`
- `POST /api/budgets`

### Goals
- `GET /api/goals`
- `POST /api/goals`

## 🎯 Roadmap

- [x] Basic authentication
- [x] Database schema
- [x] Basic UI components
- [ ] Transaction management
- [ ] Category management
- [ ] Budget tracking
- [ ] Goal management
- [ ] Dashboard analytics
- [ ] Mobile app (React Native)

## 🤝 Contributing

1. Fork repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📄 License

This project is licensed under the MIT License.

## 👥 Team

- **Developer**: [Your Name]
- **Project**: Cashfloo
- **Repository**: https://github.com/haninggrk/Cashfloo

## 📞 Support

Untuk support dan pertanyaan, silakan buat issue di GitHub repository.

---

**Cashfloo** - Manajemen Keuangan Pribadi yang Baik 💰✨
