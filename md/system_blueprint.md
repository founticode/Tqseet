# TQSEET MASTER BLUEPRINT (The Engine Guide)

This document serves as the technical blueprint for the TQSEET platform, explaining the core architecture, security, and financial logic.

## 1. Project Architecture
The platform follows a modular PHP architecture designed for security and scalability.
- **`/includes`**: Shared libraries and auth helpers.
- **`/controllers`**: Logical processing of data (Decisions, Forms).
- **`/views`**: Role-specific interfaces (Admin, Merchant, User, Public).
- **`/uploads`**: Structured storage for binary assets (Documents, Avatars, Products).

## 2. Core Authentication Functions (`includes/auth.php`)
These are the "Bouncers" of the system:
- `isLoggedIn()`: Validates if a session exists.
- `requireLogin()`: Force-redirects guests to the login page.
- `requireRole($role)`: Restricts pages based on user type (Admin, Merchant, User).
- `currentUser()`: Pulls the active user's session payload.

## 3. Financial Logic & Scoring
- **Automated Credit Engine**: Credit Limit = `User Monthly Salary * 1.5`.
- **Commission Engine**: 
    - `Platform Fee = Order Total * Merchant Commission Rate`.
    - `Merchant Payout = Order Total - Platform Fee`.
- **Installment Generation**: Every order is split into 4 equal parts with 30-day intervals.

## 4. The Security Layer
- **SQL Protection**: Use of Prepared Statements (`mysqli_prepare`) across all queries.
- **KYC Gating**: The system blocks the `place_order.php` controller if:
    - Identity (CIN) is not approved.
    - Financials (Salary) are not approved.
- **Password Security**: Use of `password_hash()` and `password_verify()`.

## 5. Database Schema Relational Map
- `users` -> Primary entity.
- `merchants` (1:1 with `users`) -> Adds store metadata.
- `products` (1:N with `merchants`) -> Catalog items.
- `user_verifications` (1:1 with `users`) -> KYC data.
- `user_financials` (1:1 with `users`) -> Credit scoring data.
- `orders` (Connects `users` & `products`) -> The transaction.
- `installments` (1:N with `orders`) -> Payment schedule.

## 6. Upload Management
- Files are renamed with unique timestamps to prevent collision.
- Folders are automatically created if they don't exist.

## 7. Core Business Rules (Industrial Standards)
- **Available Credit**: Calculated as `Max Credit Limit - SUM(Unpaid Installments)`.
- **Credit Guard**: Real-time check at checkout; blocks orders if `Product Price > Available Credit`.
- **Sequential Payments**: Chronic chronological enforcement; future installments (2, 3, 4) are locked until the immediate predecessor is 'paid'.
- **Hard Lockdown**: Orders cannot be cancelled or deleted once the first payment (downpayment) is processed.
- **Role-Aware Approval**: Distinct validation logic for Merchants (Store activation) vs Users (Credit limit allocation).
