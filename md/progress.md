# TQSEET — Project Progress & Roadmap

> This file tracks every phase and step of the project.
> ✅ = Done | 🔄 = In Progress | ⬜ = Not Started

---

## Phase 1: Setup & Configuration ✅
- [x] Step 1: Create database (tqseet_db)
- [x] Step 2: Create schema (all tables)
- [x] Step 3: Database connection class (db.php)
- [x] Step 4: Error logging system (dev_logs.php)
- [x] Step 5: User-friendly error messages (user_errors.php)
- [x] Step 6: Test connection (test.php)

---

## Phase 2: Auth — Register ✅
- [x] Step 1: Create register form (HTML)
- [x] Step 2: Handle form submission (POST)
- [x] Step 3: Validate inputs (empty, email, phone, password)
- [x] Step 4: Hash password (password_hash)
- [x] Step 5: Check if email/phone already exists
- [x] Step 6: Insert user into database

---

## Phase 3: Auth — Login ✅
- [x] Step 1: Create login form (HTML)
- [x] Step 2: Handle login submission (POST)
- [x] Step 3: Validate inputs
- [x] Step 4: Find user by email in database
- [x] Step 5: Verify password (password_verify)
- [x] Step 6: Start session and store user data
- [x] Step 7: Redirect based on role (user/merchant/admin)

---

## Phase 4: Auth — Sessions & Protection 🔄
- [x] Step 1: Start session on every page
- [x] Step 2: Create auth helper function (reusable)
- [x] Step 3: Protect pages (redirect if not logged in)
- [x] Step 4: Create logout functionality
- [x] Step 5: Create navigation bar (show login/logout)

---

## Phase 5: User Dashboard ✅
- [x] Step 1: Create basic user dashboard page
- [x] Step 2: Show user info (name, email, phone)
- [x] Step 3: Show account status (verified/not verified)

---

## Phase 6: User Verification (KYC) ✅
- [x] Step 1: Create verification form (CIN + image upload)
- [x] Step 2: Handle file upload
- [x] Step 3: Save verification data to database
- [x] Step 4: Admin can view pending verifications
- [x] Step 5: Admin can approve/reject

---

## Phase 7: OTP System ✅
- [x] Step 1: Generate OTP code
- [x] Step 2: Save OTP to database
- [x] Step 3: Send OTP (email or SMS)
- [x] Step 4: Verify OTP input from user
- [x] Step 5: Mark user as verified

---

## Phase 8: Merchant System 🔄
- [x] Step 1: Merchant registration / upgrade
- [x] Step 2: Merchant dashboard
- [x] Step 3: Add products (form + insert)
- [x] Step 4: Edit / delete products
- [x] Step 5: View merchant orders

---

## Phase 9: Products & Catalog ✅
- [x] Step 1: Display all products (public page)
- [x] Step 2: Product detail page
- [x] Step 3: Search / filter products

---

## Phase 10: Orders & Allocation ⬜
- [x] Step 1: User places an order
- [x] Step 2: Calculate installments (split into payments)
- [ ] Step 3: Save order + installments to database
- [ ] Step 4: User views their orders
- [ ] Step 5: User views their installments
- [ ] Step 6: Mark installments as paid

---

## Phase 11: Admin Dashboard ⬜
- [ ] Step 1: Admin dashboard (overview/stats)
- [ ] Step 2: Manage users (view, ban)
- [ ] Step 3: Manage merchants
- [ ] Step 4: View all orders
- [ ] Step 5: View all installments
- [ ] Step 6: Commission reports

---

## Phase 12: User Financial Profile ⬜
- [ ] Step 1: Financial info form (profession, salary)
- [ ] Step 2: Save to user_financials table
- [ ] Step 3: Admin reviews financial data
- [ ] Step 4: Credit limit calculation

---

## Phase 13: UI/UX Polish ⬜
- [ ] Step 1: Design system (colors, fonts, spacing)
- [ ] Step 2: Style auth pages (login, register)
- [ ] Step 3: Style dashboards
- [ ] Step 4: Responsive design (mobile-friendly)
- [ ] Step 5: Error/success message styling
- [ ] Step 6: Loading states and animations

---

## Current Position: Phase 10, Step 3 (Save Installments)
