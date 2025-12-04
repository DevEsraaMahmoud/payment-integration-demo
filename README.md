# E-Commerce Demo - Laravel + Inertia + Vue 3 + Stripe

A complete, production-ready e-commerce application built with Laravel 12, Inertia.js, Vue 3, TailwindCSS, and Stripe payments.

## ✨ Features

### 🛍️ Storefront
- **Home Page** - Hero section, featured products, category showcase
- **Product Catalog** - Listing with filters (category, price, search), pagination
- **Product Details** - Full product information with image gallery
- **Responsive Design** - Mobile-first, works on all devices

### 🛒 Shopping Cart
- **Session-based Cart** - Works for guest and authenticated users
- **Cart Drawer** - Slide-out cart accessible from any page
- **Cart Management** - Add, update quantities, remove items

### 💳 Checkout & Payments
- **Multiple Payment Methods**:
  - 💳 Stripe Card Payments (Stripe Elements)
  - 💰 Wallet Payments (instant)
  - 🔄 Partial Wallet + Stripe
- **Order Management** - Order creation, tracking, history
- **Payment Processing** - Secure server-side PaymentIntent creation

### 👤 User Accounts
- **Authentication** - Login, register, logout
- **Order History** - View past orders with details
- **Wallet System**:
  - View balance and transactions
  - Fund wallet via Stripe
  - Use wallet for checkout
  - Receive refunds to wallet

### 🔐 Admin Panel
- **Dashboard** - Sales statistics and overview
- **Product Management** - CRUD operations for products
- **Order Management** - View, filter, update order status
- **Transaction Management** - View all payments, process refunds
- **Refund System**:
  - Refund to original payment method (Stripe)
  - Refund to user's wallet
- **CSV Export** - Export orders data

### 🔔 Webhooks & Integrations
- **Stripe Webhooks** - Signature verification, idempotency handling
- **Event Logging** - Track all webhook events
- **Charge Tracking** - Store charge_id for refunds

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm
- Stripe account (test mode)

### Installation

```bash
# 1. Install dependencies
composer install
npm install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Configure Stripe keys in .env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# 4. Run migrations and seeders
php artisan migrate --seed

# 5. Build frontend
npm run dev

# 6. Start server
php artisan serve --port=8000
```

### Stripe Webhook Setup (Local)

```bash
# Install Stripe CLI: https://stripe.com/docs/stripe-cli
stripe listen --forward-to http://localhost:8000/webhooks/stripe

# Copy webhook secret to .env
STRIPE_WEBHOOK_SECRET=whsec_...

# Test webhook
stripe trigger payment_intent.succeeded
```

## 📋 Default Credentials

**Admin User:**
- Email: `admin@example.com`
- Password: `password`

**Test User:**
- Email: `test@example.com`
- Password: `password`

## 🏗️ Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Inertia.js + Vue 3 + TailwindCSS 4
- **Payment**: Stripe Payment Intents API
- **Database**: SQLite (default) or MySQL/PostgreSQL
- **Authentication**: Laravel session-based auth

## 📁 Project Structure

```
app/
├── Http/Controllers/
│   ├── Auth/              # Authentication
│   ├── Admin/             # Admin panel
│   ├── Payment/           # Stripe integration
│   └── ...                # Public controllers
├── Models/                # Eloquent models
├── Services/              # StripeService
└── Middleware/           # Auth, admin protection

resources/js/
├── Layouts/              # AppLayout with navbar/footer
├── Components/           # CartDrawer, etc.
└── Pages/                # Vue pages
    ├── Auth/             # Login, Register
    ├── Products/          # Index, Show
    ├── Admin/            # Dashboard, Products, Orders
    └── ...
```

## 🎯 Key Features Implemented

✅ **Complete Storefront** - Home, products, cart, checkout  
✅ **Stripe Integration** - Payment Intents, Elements, webhooks  
✅ **Wallet System** - Funding, checkout, refunds  
✅ **Admin Panel** - Products, orders, transactions management  
✅ **Refund System** - To card or wallet  
✅ **Order Management** - Tracking, status updates, CSV export  
✅ **User Authentication** - Login, register, profile  
✅ **Responsive UI** - Mobile-friendly, TailwindCSS  
✅ **Webhook Handling** - Signature verification, idempotency  

## 🔧 Configuration

### Environment Variables

```env
# Stripe
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_CURRENCY=USD

# Database (SQLite default)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Mail (for order confirmations)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
```

## 📝 Testing

```bash
# Run tests
php artisan test

# Test Stripe webhooks locally
stripe listen --forward-to http://localhost:8000/webhooks/stripe
stripe trigger payment_intent.succeeded
```

## 🎨 UI Features

- **Cart Drawer** - Slide-out cart from right
- **Responsive Navbar** - Mobile menu, user dropdown
- **Product Cards** - Hover effects, quick view
- **Filter System** - Category, price range, search
- **Status Badges** - Color-coded order/transaction status
- **Toast Notifications** - Success/error messages

## 🔒 Security

- CSRF protection (webhook routes excluded)
- Admin middleware protection
- Webhook signature verification
- Input validation on all forms
- SQL injection protection (Eloquent)
- XSS protection (Vue auto-escaping)

## 📊 Database Schema

- `users` - User accounts (with `is_admin` flag)
- `products` - Product catalog (with categories)
- `categories` - Product categories
- `orders` - Customer orders
- `order_items` - Order line items
- `transactions` - Payment transactions
- `wallets` - User wallet balances
- `wallet_transactions` - Wallet history
- `addresses` - User address book
- `coupons` - Discount codes
- `stripe_events` - Webhook event log

## 🚦 Routes

**Public:**
- `/` - Home page
- `/products` - Product listing
- `/products/{id}` - Product details
- `/cart` - Shopping cart
- `/checkout` - Checkout page
- `/login` - Login page
- `/register` - Registration page

**Authenticated:**
- `/orders` - Order history
- `/orders/{id}` - Order details
- `/wallet` - Wallet management

**Admin:**
- `/admin/dashboard` - Admin dashboard
- `/admin/products` - Product management
- `/admin/orders` - Order management
- `/admin/transactions` - Transaction management

## 💡 Usage Examples

### As Customer
1. Browse products → Add to cart → Checkout
2. Choose payment: Stripe card or Wallet
3. View orders in `/orders`
4. Fund wallet in `/wallet`

### As Admin
1. Login with admin credentials
2. Manage products in `/admin/products`
3. View orders in `/admin/orders`
4. Process refunds in `/admin/transactions`

## 📚 Documentation

- `COMPLETE_IMPLEMENTATION_GUIDE.md` - Detailed implementation notes
- `FILES_CREATED_SUMMARY.md` - List of all files created/updated

## 🐛 Troubleshooting

**Images not loading?**
- Check if image URLs are full URLs (https://) or relative paths
- Ensure storage link: `php artisan storage:link`

**Webhook not working?**
- Verify `STRIPE_WEBHOOK_SECRET` in `.env`
- Check Laravel logs: `storage/logs/laravel.log`
- Test with Stripe CLI

**Wallet funding fails?**
- Verify Stripe keys are correct
- Check webhook is receiving events
- Review `wallet_transactions` table

## 📄 License

MIT License - Free to use for portfolio and learning purposes.

---

**Built with ❤️ using Laravel, Inertia.js, Vue 3, and Stripe**
