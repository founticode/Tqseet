/tqseet
│
├── /assets
│   ├── /css
│   │   ├── merchant_portal.css
│   │   └── style.css
│   └── favicon.svg
│
├── /config
│   ├── db.php
│   ├── dev_logs.php
│   └── user_errors.php
│
├── /controllers
│   ├── AdminController.php
│   ├── AdminSettlementController.php
│   ├── AuthController.php
│   ├── OrderController.php
│   ├── PaymentLinkController.php
│   ├── ProductController.php
│   ├── SettlementController.php
│   └── VerificationController.php
│
├── /database
│   └── schema.sql
│
├── /docs
│   └── [Contains technical documentation txt files (00 to 75)]
│
├── /includes
│   ├── admin_sidebar.php
│   ├── auth.php
│   ├── footer.php
│   ├── merchant_sidebar.php
│   ├── navbar.php
│   └── otp_helpers.php
│
├── /md
│   ├── cahier_des_charges.md
│   ├── progress.md
│   ├── structure.md
│   ├── system_blueprint.md
│   └── workflow
│
│
├── /uploads
│   ├── /avatars
│   ├── /financials
│   ├── /kyc
│   ├── /products
│   └── /verifications
│
├── /views
│   ├── /admin
│   │   ├── all_installments.php
│   │   ├── analytics.php
│   │   ├── commission_reports.php
│   │   ├── dashboard.php
│   │   ├── merchants.php
│   │   ├── settings.php
│   │   ├── settlements.php
│   │   ├── verifications.php
│   │   ├── view_plan.php
│   │   └── view_user.php
│   │
│   ├── /auth
│   │   ├── forgot_password.php
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── reset_password.php
│   │   └── verify_otp.php
│   │
│   ├── /merchant
│   │   ├── add_product.php
│   │   ├── dashboard.php
│   │   ├── edit_product.php
│   │   ├── orders.php
│   │   ├── payment_links.php
│   │   ├── products.php
│   │   ├── sales.php
│   │   ├── settings.php
│   │   └── settlements.php
│   │
│   ├── /public
│   │   ├── bnpl_agreement.php
│   │   ├── business.php
│   │   ├── business_docs.php
│   │   ├── catalog.php
│   │   ├── contact.php
│   │   ├── help.php
│   │   ├── pay_link.php
│   │   ├── privacy.php
│   │   ├── product_detail.php
│   │   ├── shop.php
│   │   └── terms.php
│   │
│   └── /user
│       ├── calculate_installments.php
│       ├── checkout_payment.php
│       ├── dashboard.php
│       ├── orders.php
│       ├── pay_installment.php
│       ├── place_order.php
│       ├── process_simulated_payment.php
│       ├── save_installments.php
│       ├── settings.php
│       ├── update_password.php
│       ├── upgrade_merchant.php
│       ├── verify.php
│       └── view_installments.php
│
├── index.php
├── errors.log
└── otp_sent.log