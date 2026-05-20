# BNPL Payment Methods & TQSEET Backend Compatibility Analysis

This document explains the three industry-standard Buy Now Pay Later (BNPL) payment methods displayed on our merchant marketing page, analyzes how they map to TQSEET's current codebase, and presents strategies for integration.

---

## 1. How the Three BNPL Methods Work (General Industry Standards)

| Payment Method | Consumer Flow | Merchant Flow | Best Suited For |
| :--- | :--- | :--- | :--- |
| **Split in 4 (Pay in 4)** | Cart total is divided into **4 equal parts**. The first 25% is paid immediately at checkout. The remaining three 25% payments are automatically billed to the user's card every 14 days (or monthly). Zero interest/fees. | Merchant is paid the **full cart amount** upfront (minus a small 2-5% commission fee) within 24-48 hours. The BNPL platform absorbs default risk. | Fashion, cosmetics, small electronics, everyday retail (500 MAD - 8,000 MAD). |
| **Pay in 30 Days** | Shopper pays **nothing upfront**. The order ships immediately. The shopper has 30 days to try the goods. Within 30 days, they must pay the invoice balance or return the goods. Zero interest/fees. | Merchant is paid **upfront** upon order shipment, exactly like credit card checkouts. The BNPL platform handles collection risk. | Apparel/shoes (high return rate products), boutique items (200 MAD - 5,000 MAD). |
| **Monthly Financing** | For high-value carts. Total cost is split over **3, 6, or 12 months** with structured fixed monthly installments. May include a low interest rate (APR) depending on the buyer's credit score. | Merchant gets paid **upfront** in full. BNPL platform receives monthly installments from the user. | Furniture, high-end electronics, appliances, travel bookings (4,000 MAD - 30,000 MAD). |

---

## 2. TQSEET's Current Backend Implementation

Our current codebase **exclusively supports the "Split in 4" (Pay in 4) mechanism**. Here is how it is structured:

### Database Schema (`installments` table)
Our schema stores installments with:
* `id` (Primary Key)
* `order_id` (Foreign Key referencing `orders`)
* `amount` (Decimal)
* `due_date` (Date)
* `status` (Enum: `'paid'`, `'unpaid'`)

### Hardcoded Calculation Logic
In `views/user/save_installments.php` and `views/user/process_simulated_payment.php`, the split is computed as follows:
```php
$installmentAmount = $total / 4;

$dates = [
    date('Y-m-d'), // First payment: Today (paid immediately)
    date('Y-m-d', strtotime('+1 month')), // Second payment
    date('Y-m-d', strtotime('+2 months')), // Third payment
    date('Y-m-d', strtotime('+3 months'))  // Fourth payment
];
```
This divides the order total into exactly four parts, marks the first part as `'paid'` immediately, and leaves the remaining three as `'unpaid'` due at monthly intervals.

---

## 3. Implementation Plan & Current Status

### Option A Selected (Launched Phase)
As of May 2026, the decision has been finalized to proceed with **Option A**: focusing purely on **Split in 4** as our primary operational payment option.

#### Frontend Updates Made:
1. **Interactive Tabs Integration**:
   - The left menu button titles for **Pay in 30 Days** and **Monthly Financing** now explicitly display a light grey `Coming Soon` badge inline.
2. **Dynamic Offering Status Card**:
   - The Terms summary card displays a new `Offering Status` field.
   - It shows a bold green `Active Offering` state for **Split in 4** and a neutral grey `Coming Soon` state for the other two options when selected.
