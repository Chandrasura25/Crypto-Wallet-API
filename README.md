# Crypto Wallet API

[![Tests](https://github.com/Chandrasura25/Crypto-Wallet-API/actions/workflows/tests.yml/badge.svg)](https://github.com/Chandrasura25/Crypto-Wallet-API/actions/workflows/tests.yml)
![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![Laravel 10](https://img.shields.io/badge/Laravel-10-FF2D20?logo=laravel&logoColor=white)

A Laravel REST API that keeps multi-coin crypto balances for users — Bitcoin, Ethereum, Litecoin, or any CoinGecko coin.

**📖 Interactive API docs → [chandrasura25.github.io/Crypto-Wallet-API](https://chandrasura25.github.io/Crypto-Wallet-API/)**

## What it does

| Feature | How |
|---|---|
| **Auth** | Register / log in with phone + password; Laravel **Sanctum** bearer tokens |
| **Accounts** | One account per coin per user, balances stored as `DECIMAL(18,8)` |
| **Transfers** | Send coins to another user by email. Runs inside a **DB transaction with `SELECT … FOR UPDATE` row locks**, so two simultaneous transfers can't overspend a balance |
| **Conversion** | Swap coins at the **live CoinGecko USD rate**: `amount × price(source) ÷ price(target)`, added to the target balance |
| **History** | Every transaction you sent *or* received, with both accounts attached |
| **Ownership checks** | Users can only read, spend from, or close their own accounts — enforced and tested |

All money maths uses PHP `bcmath` (string decimals), not floats.

## Endpoints

| Method | Path | Auth | Description |
|---|---|:-:|---|
| `POST` | `/api/register` | | Create user, returns token |
| `POST` | `/api/login` | | Log in, returns token |
| `GET` | `/api/crypto-accounts` | 🔒 | My accounts and balances |
| `POST` | `/api/crypto-accounts` | 🔒 | Open an account for a coin |
| `GET` `PUT` `DELETE` | `/api/crypto-accounts/{id}` | 🔒 | Read / rename / close (only if empty) |
| `POST` | `/api/transfer` | 🔒 | Send coins to a user by email |
| `GET` | `/api/transactions` | 🔒 | My sent + received transactions |
| `GET` | `/api/transactions/{id}` | 🔒 | One transaction I'm part of |
| `POST` | `/api/convert` | 🔒 | Convert between coins at the live rate |

Full request/response schemas and error codes are in the [API docs](https://chandrasura25.github.io/Crypto-Wallet-API/) (source: [`docs/openapi.json`](docs/openapi.json)).

### Example

```bash
# 1. Register
curl -X POST http://localhost:8000/api/register -H "Content-Type: application/json" \
  -d '{"phone":"+2348000000000","email":"ada@example.com","password":"secret-password"}'
# → { "message": { ...user }, "token": "1|Xq3..." }

# 2. Convert 0.5 BTC to ETH at the live rate
curl -X POST http://localhost:8000/api/convert -H "Authorization: Bearer 1|Xq3..." \
  -H "Content-Type: application/json" \
  -d '{"source_coin":"bitcoin","target_coin":"ethereum","amount":0.5}'
# → { "message": "Balance converted successfully", "rate": 20.4, "converted_amount": "10.20000000", ... }
```

## Run it locally

Requires PHP 8.1+ (with `bcmath`), Composer and MySQL (or SQLite).

```bash
git clone https://github.com/Chandrasura25/Crypto-Wallet-API.git
cd Crypto-Wallet-API
composer install
cp .env.example .env        # set DB_* to your database
php artisan key:generate
php artisan migrate
php artisan serve           # http://localhost:8000 — docs at /doc.html
```

## Tests

```bash
php artisan test
```

16 feature tests (in-memory SQLite, CoinGecko faked with `Http::fake`) cover auth, account ownership, transfers (including overspending and spending from someone else's account), transaction history scoping, and conversion maths. They run on every push via GitHub Actions.

## Project structure

```
app/Http/Controllers/
  AuthController.php           register / login
  CryptoAccountController.php  account CRUD + ownership checks
  TransactionController.php    transfers (locked DB transaction) + history
  CryptoPriceController.php    CoinGecko prices + conversion
app/Models/                    User, CryptoAccount, Transaction
tests/Feature/CryptoWalletApiTest.php
docs/                          OpenAPI spec + docs site (GitHub Pages)
```
