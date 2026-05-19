<?php
require_once __DIR__ . "/../../includes/auth.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Integration & API Docs - TQSEET</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .docs-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: calc(100vh - 72px);
            background-color: #ffffff;
        }

        .docs-sidebar {
            background-color: #f8fafc;
            border-right: 1px solid #e2e8f0;
            padding: 40px 24px;
            position: sticky;
            top: 72px;
            height: calc(100vh - 72px);
            overflow-y: auto;
        }

        .docs-sidebar-title {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 16px;
        }

        .docs-menu-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 30px;
        }

        .docs-menu-item a {
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            display: block;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .docs-menu-item a:hover,
        .docs-menu-item.active a {
            background-color: rgba(0, 90, 78, 0.05);
            color: #005a4e;
        }

        .docs-content {
            padding: 60px 80px;
            max-width: 900px;
            overflow-y: auto;
        }

        .docs-section {
            margin-bottom: 60px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 40px;
        }

        .docs-section:last-child {
            border-bottom: none;
        }

        .docs-section h2 {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }

        .docs-section p {
            color: #475569;
            line-height: 1.7;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .docs-api-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: monospace;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 6px;
            background-color: #f1f5f9;
            color: #334155;
            margin-bottom: 20px;
        }

        .docs-api-badge.post {
            background-color: #ecfdf5;
            color: #047857;
        }

        .docs-code-box {
            background-color: #0f172a;
            border-radius: 12px;
            padding: 24px;
            color: #f8fafc;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.88rem;
            line-height: 1.5;
            overflow-x: auto;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .docs-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .docs-table th, 
        .docs-table td {
            text-align: left;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .docs-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .docs-table td {
            font-size: 0.9rem;
            color: #334155;
        }

        .docs-param-name {
            font-family: monospace;
            font-weight: 700;
            color: #005a4e;
        }

        .docs-param-type {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 600;
            background-color: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
        }

        @media (max-width: 992px) {
            .docs-layout {
                grid-template-columns: 1fr;
            }
            .docs-sidebar {
                display: none;
            }
            .docs-content {
                padding: 40px 24px;
            }
        }
    </style>
</head>
<body>

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="docs-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="docs-sidebar">
            <div class="docs-sidebar-title">Getting Started</div>
            <ul class="docs-menu-list">
                <li class="docs-menu-item active"><a href="#introduction">Introduction</a></li>
                <li class="docs-menu-item"><a href="#authentication">Authentication</a></li>
            </ul>

            <div class="docs-sidebar-title">Checkout API</div>
            <ul class="docs-menu-list">
                <li class="docs-menu-item"><a href="#create-session">Create Session</a></li>
                <li class="docs-menu-item"><a href="#webhook-handling">Webhooks</a></li>
            </ul>

            <div class="docs-sidebar-title">Widgets & SDK</div>
            <ul class="docs-menu-list">
                <li class="docs-menu-item"><a href="#js-widget">Checkout Button Widget</a></li>
            </ul>
        </aside>

        <!-- Main Documentation Content -->
        <main class="docs-content">
            
            <!-- Intro Section -->
            <section id="introduction" class="docs-section">
                <h2>API Introduction</h2>
                <p>Welcome to the TQSEET Developer portal. Our REST API allows you to integrate buy-now-pay-later payment terms directly into your checkout page, custom platforms, or mobile apps.</p>
                <p>By connecting to our endpoints, you can request payment terms dynamically, verify signatures, and receive webhook triggers when consumer installments are cleared or default.</p>
            </section>

            <!-- Auth Section -->
            <section id="authentication" class="docs-section">
                <h2>API Authentication</h2>
                <p>Every call to TQSEET REST endpoints must contain your unique client token inside the request header. You can locate your private and public credentials in the <strong>Store Settings</strong> workspace.</p>
                
                <div class="docs-code-box">
Authorization: Bearer tq_live_8f3d1e9aa35e07bd6a218f2f
Content-Type: application/json
                </div>
            </section>

            <!-- Create Checkout Session -->
            <section id="create-session" class="docs-section">
                <h2>Create Checkout Session</h2>
                <p>Create an active checkout session to initiate the split payment workflow and redirect your buyers to TQSEET's installment breakdown portal.</p>
                
                <div class="docs-api-badge post">
                    <span>POST</span> /api/v1/checkout/create
                </div>

                <h4>Request Parameters</h4>
                <table class="docs-table">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Type</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="docs-param-name">amount</td>
                            <td><span class="docs-param-type">Float</span></td>
                            <td>The total cart purchase amount in MAD. Must be greater than 100.00 DH.</td>
                        </tr>
                        <tr>
                            <td class="docs-param-name">order_id</td>
                            <td><span class="docs-param-type">String</span></td>
                            <td>Your internal unique transaction ID for reference tracking.</td>
                        </tr>
                        <tr>
                            <td class="docs-param-name">success_url</td>
                            <td><span class="docs-param-type">String</span></td>
                            <td>Redirect link after successful user confirmation.</td>
                        </tr>
                        <tr>
                            <td class="docs-param-name">cancel_url</td>
                            <td><span class="docs-param-type">String</span></td>
                            <td>Redirect link if checkout is cancelled.</td>
                        </tr>
                    </tbody>
                </table>

                <h4>Example Request Payload</h4>
                <div class="docs-code-box">
{
  "amount": 6000.00,
  "order_id": "ORDER_24009A",
  "success_url": "https://yourstore.com/checkout/success",
  "cancel_url": "https://yourstore.com/checkout/cancel"
}
                </div>

                <h4>Example Success Response</h4>
                <div class="docs-code-box">
{
  "status": "success",
  "session_id": "sess_01H9T8XWJXZ",
  "redirect_url": "http://localhost:3000/views/public/checkout_wall.php?session=sess_01H9T8XWJXZ",
  "expires_at": "2026-05-19T16:00:00Z"
}
                </div>
            </section>

            <!-- Webhook Handling -->
            <section id="webhook-handling" class="docs-section">
                <h2>Webhooks Management</h2>
                <p>Configure a webhook endpoint inside your dashboard to receive notifications about events happening on TQSEET (e.g. order approval, payments paid, or cancellations).</p>
                
                <h4>Example JSON Event Payload</h4>
                <div class="docs-code-box">
{
  "event": "order.active",
  "created_at": "2026-05-19T15:20:10Z",
  "data": {
    "order_id": 24,
    "total_amount": 24000.00,
    "installments_count": 4,
    "merchant_reference": "ORDER_24009A"
  }
}
                </div>
            </section>

            <!-- JS Widget Section -->
            <section id="js-widget" class="docs-section">
                <h2>Checkout Widget SDK</h2>
                <p>Include this client widget on your product catalog cards to show dynamic installment calculations (e.g. "Or 4 payments of 1,500 DH") to maximize conversions before buyers reach the cart.</p>
                
                <div class="docs-code-box">
&lt;!-- Include script --&gt;
&lt;script src="https://cdn.tqseet.ma/sdk/widget.js" async&gt;&lt;/script&gt;

&lt;!-- Render price calculation element --&gt;
&lt;tqseet-price-widget 
  data-amount="6000" 
  data-color="green" 
  data-language="fr"&gt;
&lt;/tqseet-price-widget&gt;
                </div>
            </section>

        </main>
    </div>


</body>
</html>
