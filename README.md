# E-Commerce Platform — Laravel + Inertia + Vue 3

Production-style e-commerce platform built with **Laravel, Inertia.js, and Vue 3**, focusing on backend correctness, clean architecture, and reliable payment integrations rather than UI polish.

This project simulates real-world e-commerce challenges such as payment processing, webhook handling, transaction safety, and admin operations.

---

## 🧠 Project Goal

The main purpose of this project is to practice and demonstrate:

- Secure payment flows
- Event-driven backend design
- Idempotent webhook handling
- Scalable order & transaction management
- Clean separation of concerns
- Real-world engineering trade-offs

---

## 🏗️ Architecture Overview

- **Backend-first architecture** with clear responsibility boundaries
- Business logic isolated using **Service / Action layers**
- Payment providers abstracted behind a unified interface
- Webhook processing designed to be:
  - Idempotent
  - Retry-safe
  - Signature-verified
- Admin operations separated from user-facing flows

---

## ✨ Core Features

### Store & Orders
- Product catalog with filtering
- Session-based cart (guest & authenticated users)
- Order lifecycle management

### Payments
- Stripe Payment Intents integration
- Paymob iframe integration
- Internal wallet-based payments
- Transaction verification & status tracking

### Webhooks
- Signature verification (Stripe & Paymob)
- Duplicate event protection
- Safe retries without double-charging
- Centralized webhook logging

### Admin Panel
- Order & transaction monitoring
- Refund handling
- Payment status inspection
- Protected admin middleware

---

## 🔒 Security & Reliability

- Webhook signature validation
- Idempotency keys to prevent duplicate charges
- CSRF protection (webhook routes excluded)
- Input validation & guarded mass assignment
- Centralized error logging

---

## 🧩 Tech Stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Inertia.js, Vue 3, TailwindCSS
- **Payments:** Stripe, Paymob
- **Database:** MySQL / SQLite
- **Async & Events:** Laravel Events & Listeners

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+, Composer
- Node.js & npm
- Stripe test account
- Paymob account (optional)

### Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate --seed

npm run dev
php artisan serve
```

## 🔁 Webhook Setup

### Stripe (Local)
Use the Stripe CLI to forward webhook events locally:

```bash
stripe listen --forward-to http://localhost:8000/webhooks/stripe
```

## 🔁 Webhook Setup

### Stripe (Local)

```bash
stripe listen --forward-to http://localhost:8000/webhooks/stripe
```
### Paymob
For local development, expose your application using ngrok:

```bash
ngrok http 8000
```
Then configure the generated public URL as the webhook endpoint in the Paymob dashboard.

### 📌 Design Decisions & Trade-offs
- Chose Inertia.js to maintain a single backend-driven architecture without duplicating API layers.
- Webhooks are handled synchronously with minimal logic to ensure fast acknowledgment.
- Heavy processing is intentionally kept minimal and can be deferred when scaling.
- SQLite is supported for simplicity and local setup, while MySQL is recommended for production use.
- UI is intentionally minimal to keep the focus on backend correctness, architecture, and payment flow clarity.

### 🔮 Possible Improvements
- Introduce queues for webhook processing at scale.
- Add automated tests for critical payment flows.
- Extract the payment integration layer into a standalone reusable package.
- Add observability and monitoring (metrics, tracing, alerts).

### 📄 License
MIT License — free to use for learning and portfolio purposes.

**Built with ❤️ using Laravel, Inertia.js, Vue 3, Stripe, and Paymob**
