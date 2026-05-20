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

## Phase 4: Auth — Sessions & Protection ✅
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

## Phase 8: Merchant System ✅
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

## Phase 10: Orders & Allocation ✅
- [x] Step 1: User places an order
- [x] Step 2: Calculate installments (split into payments)
- [x] Step 3: Save order + installments to database
- [x] Step 4: User views their orders
- [x] Step 5: User views their installments (Timeline)
- [x] Step 6: Mark installments as paid (One-Click Card Simulation)

---

## Phase 11: Merchants & Admins Dashboards ✅
- [x] Step 1: Merchant views their sales
- [x] Step 2: Merchant views their products/inventory
- [x] Step 3: Manage merchants (Admin side)
- [x] Step 4: System analytics for Admin
- [x] Step 5: View all installments
- [x] Step 6: Commission reports

---

## Phase 12: User Financial Profile ✅
- [x] Step 1: Financial info form (profession, salary + document proof)
- [x] Step 2: Identity Verification form (CIN card upload)
- [x] Step 3: Admin reviews documents and financial data
- [x] Step 4: Automated Credit scoring & Checkout Protection

---

## Phase 13: Profile & Account Management ✅
- [x] Step 1: User Profile Settings (Personal Info & Security)
- [x] Step 2: Merchant Store Settings (Branding & Info)
- [x] Step 3: Admin Account Settings
- [x] Step 4: Password Update Logic & Security
- [x] Step 5: Real Profile page (Face of the User) with Avatar support

---

## Phase 15: Industrial Hardening & Debugging ✅
- [x] Step 1: Fix Product/Merchant orphaned links
- [x] Step 2: Dynamic Credit Limit calculation (Available vs Max)
- [x] Step 3: Real-time Credit Limit Guards (Blocking checkout)
- [x] Step 4: Sequential Installment Enforcement (Pay in order)
- [x] Step 5: "Ghost Order" elimination and Draft Cancellation
- [x] Step 6: Rejection Data Integrity (Resetting verified status)
- [x] Step 7: Final Checkout Security Guard

---

## Phase 14: UI/UX Polish ⬜
- [x] Step 1: Design system (colors, fonts, spacing)
- [x] Step 2: Style auth pages (login, register)
- [x] Step 3: Style dashboards
- [x] Step 4: Responsive design (mobile-friendly)
- [ ] Step 5: Error/success message styling
- [ ] Step 6: Loading states and animations
- [x] Step 7: Style landing, shop catalog pages (Klarna-style shop), and testimonials


## Phase 16: Fintech Identity & Credit Verification Hub ✅
- [x] Step 1: Integrated visual compliance panel directly into consumer Settings
- [x] Step 2: Consolidated identity (CIN) upload and verification forms
- [x] Step 3: Consolidated income profile (Job Title, Monthly Net Salary) and statements upload
- [x] Step 4: Sealed fields dynamically with green visual credentials once approved by admins
- [x] Step 5: Updated dashboard checkpoints to automatically scroll to settings verification
- [x] Step 6: Retired old standalone separate files and established safe redirects
- [x] Step 7: Integrated beautiful iOS-style OTP notification slide-down and autofill to Registration verification flow
- [x] Step 8: Upgraded all database key relationships to use `ON DELETE CASCADE` dynamically to allow effortless user deletion
- [x] Step 9: Replaced static dashboard hardcoding with real-time dynamic database queries of user verification state
- [x] Step 10: Segregated and styled distinct, stacked OTP Verification and KYC/Credit Review status badges on the Admin reviews table
- [x] Step 11: Upgraded Admin Control Tower stats to calculate true "Action Required" queues representing pending customer KYC and pending merchant onboardings, featuring a visual split breakdown

---

## Phase 17: B2B Merchant Experience & API Documentation Hub ✅
- [x] Step 1: Created high-fidelity B2B marketing landing page (views/public/business.php)
- [x] Step 2: Integrated launch-phase trust metrics (100% Upfront Payouts, 0.00% Interest, 24h Settlement)
- [x] Step 3: Designed interactive dynamic payment tabs switcher for B2B products
- [x] Step 4: Developed Stripe-like developer API integration portal (views/public/business_docs.php)
- [x] Step 5: Updated navbar logic to route to merchant landing and api docs dynamically

---

## Phase 18: Advanced Merchant Tools (Upcoming) ⬜
- [ ] Step 1: Build Payment Links logic (Generate unique URL for dynamic amount to send via WhatsApp/Email)
- [ ] Step 2: Build Settlements dashboard and tracking logic (Track platform payouts to the merchant's bank)

---

## Current Position: Working on Merchant Portal UI (Catalog & Orders Redesign)
