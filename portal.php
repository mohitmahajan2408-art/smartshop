<?php
/**
 * ShopSmart India — All-in-One Master Portal & Project Launchpad
 * BCA Semester V Field Project Navigator
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$pdo = Database::getConnection();

// System stats
$stats = [
    'categories' => 0,
    'products' => 0,
    'orders' => 0,
    'users' => 0,
    'reviews' => 0
];

if ($pdo) {
    try {
        $stats['categories'] = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $stats['products']   = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $stats['orders']     = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $stats['users']      = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['reviews']    = (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopSmart India — All-in-One Project Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .portal-hero {
            background: linear-gradient(135deg, #0f233a 0%, #1e3a5f 60%, #f97316 100%);
            color: #ffffff;
            padding: 48px 24px;
            border-radius: 16px;
            margin: 24px auto 36px;
            box-shadow: 0 10px 30px rgba(15,35,58,0.25);
            text-align: center;
        }
        .portal-hero h1 {
            font-size: 2.4rem;
            font-weight: 800;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .portal-hero p {
            font-size: 1.15rem;
            opacity: 0.95;
            max-width: 680px;
            margin: 0 auto 20px;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(8px);
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.25);
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
            animation: pulseDot 2s infinite;
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
        }
        .stat-banner {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 16px;
            margin: 24px 0 36px;
        }
        .stat-item {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f233a;
        }
        .stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .hub-section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f233a;
            margin: 32px 0 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        .hub-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        .hub-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
            position: relative;
            overflow: hidden;
        }
        .hub-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(15,35,58,0.12);
            border-color: #f97316;
        }
        .hub-card-top {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 12px;
        }
        .hub-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .hub-icon.blue { background: #e0f2fe; color: #0284c7; }
        .hub-icon.orange { background: #ffedd5; color: #ea580c; }
        .hub-icon.green { background: #dcfce7; color: #16a34a; }
        .hub-icon.purple { background: #f3e8ff; color: #9333ea; }
        .hub-icon.red { background: #fee2e2; color: #dc2626; }
        .hub-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f233a;
            margin: 0 0 4px;
        }
        .hub-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            text-transform: uppercase;
        }
        .badge-core { background: #e0f2fe; color: #0369a1; }
        .badge-smart { background: #dcfce7; color: #15803d; }
        .badge-admin { background: #ffedd5; color: #c2410c; }
        .badge-test { background: #f3e8ff; color: #7e22ce; }
        .hub-desc {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.45;
            margin-bottom: 18px;
        }
        .hub-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #0f233a;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.92rem;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
            margin-top: auto;
        }
        .hub-btn:hover {
            background: #f97316;
            color: #ffffff;
        }
        .credentials-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            margin: 32px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .cred-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 18px;
        }
        .cred-role {
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .cred-role.cust { color: #0284c7; }
        .cred-role.adm { color: #ea580c; }
        .cred-row {
            font-size: 0.88rem;
            color: #334155;
            margin: 3px 0;
        }
        .cred-code {
            font-family: monospace;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            color: #0f233a;
        }
    </style>
</head>
<body style="background: #f1f5f9; padding-bottom: 60px;">

    <!-- TOP HEADER -->
    <header class="header-main" style="background: #0f233a; padding: 14px 0; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; align-items: center; justify-content: space-between;">
            <a href="index.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: #fff;">
                <svg width="34" height="34" viewBox="0 0 40 40" fill="none">
                    <rect width="40" height="40" rx="10" fill="#f97316"/>
                    <path d="M12 14L15 28H28L30 14H12Z" fill="white"/>
                    <path d="M18 14V11C18 9.34 19.34 8 21 8C22.66 8 24 9.34 24 11V14" stroke="#0f233a" stroke-width="2.5" stroke-linecap="round"/>
                    <circle cx="17" cy="31" r="2" fill="white"/>
                    <circle cx="26" cy="31" r="2" fill="white"/>
                </svg>
                <div>
                    <div style="font-size: 1.25rem; font-weight: 800; line-height: 1.1;">ShopSmart <span style="color:#f97316;">India</span></div>
                    <div style="font-size: 0.7rem; color: #94a3b8;">All-in-One Project Portal</div>
                </div>
            </a>
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="index.php" style="color: #fff; text-decoration: none; font-size: 0.88rem; font-weight: 600; padding: 6px 14px; background: rgba(255,255,255,0.12); border-radius: 6px;">🛒 Go to Storefront</a>
                <a href="admin/login.php" style="color: #fff; text-decoration: none; font-size: 0.88rem; font-weight: 600; padding: 6px 14px; background: #f97316; border-radius: 6px;">🛡️ Admin Login</a>
            </div>
        </div>
    </header>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <!-- HERO BANNER -->
        <div class="portal-hero">
            <h1>🛍️ ShopSmart India — Master Launchpad</h1>
            <p>BCA Semester V Field Project • Complete Full-Stack Indian E-Commerce Marketplace with PHP 8.2, MariaDB, Native INR, Multi-Language, and Smart Features.</p>
            <div class="status-pill">
                <span class="status-dot"></span>
                <span>System Status: Apache & MariaDB Online • All 51 Modules Active</span>
            </div>
        </div>

        <!-- LIVE METRICS -->
        <div class="stat-banner">
            <div class="stat-item">
                <div class="stat-number"><?= $stats['products'] ?></div>
                <div class="stat-label">Products Active</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['categories'] ?></div>
                <div class="stat-label">Categories</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['orders'] ?></div>
                <div class="stat-label">Seed Orders</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['users'] ?></div>
                <div class="stat-label">Users Registered</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['reviews'] ?></div>
                <div class="stat-label">Reviews & Ratings</div>
            </div>
        </div>

        <!-- QUICK CREDENTIALS BOX -->
        <div class="credentials-box">
            <div class="cred-card">
                <div class="cred-role adm">🛡️ Administrator Account</div>
                <div class="cred-row">Email: <span class="cred-code">admin@shopsmart.in</span></div>
                <div class="cred-row">Password: <span class="cred-code">Admin@ShopSmart2026</span></div>
                <div style="margin-top: 8px;">
                    <a href="admin/login.php" style="font-size: 0.8rem; color: #ea580c; font-weight: 700; text-decoration: none;">Launch Admin Panel &rarr;</a>
                </div>
            </div>
            <div class="cred-card">
                <div class="cred-role cust">👤 Customer Account</div>
                <div class="cred-row">Email: <span class="cred-code">customer@shopsmart.in</span></div>
                <div class="cred-row">Password: <span class="cred-code">Customer@123</span></div>
                <div style="margin-top: 8px;">
                    <a href="login.php" style="font-size: 0.8rem; color: #0284c7; font-weight: 700; text-decoration: none;">Launch Customer Login &rarr;</a>
                </div>
            </div>
        </div>

        <!-- 1. CUSTOMER STOREFRONT MODULES -->
        <div class="hub-section-title">
            🛒 Customer Storefront Modules
        </div>
        <div class="hub-grid">
            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon blue">🏠</div>
                        <div>
                            <span class="hub-badge badge-core">Storefront</span>
                            <h3>Homepage</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Brand splash screen, flash deals, 14 categories, hero slider, and live deals.</div>
                </div>
                <a href="index.php" class="hub-btn">Open Homepage &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon blue">📦</div>
                        <div>
                            <span class="hub-badge badge-core">Catalog</span>
                            <h3>All Products</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Multi-facet search, brand filters, price sliders, Best Value & Eco-friendly badges.</div>
                </div>
                <a href="products.php" class="hub-btn">Browse Products &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon orange">💡</div>
                        <div>
                            <span class="hub-badge badge-smart">Special Feature</span>
                            <h3>Smart Budget Mode</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Enter your ₹ budget, select categories, and the algorithm builds the optimal bundle!</div>
                </div>
                <a href="budget.php" class="hub-btn">Launch Budget Optimizer &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon purple">⚖️</div>
                        <div>
                            <span class="hub-badge badge-smart">Special Feature</span>
                            <h3>Compare Matrix</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Side-by-side comparison of technical specifications, prices, and review trust ratings.</div>
                </div>
                <a href="compare.php" class="hub-btn">Open Compare Tool &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon green">🛒</div>
                        <div>
                            <span class="hub-badge badge-core">Checkout</span>
                            <h3>Cart & Checkout</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Live quantity updates, delivery estimates, discount codes, and Indian payment methods.</div>
                </div>
                <a href="cart.php" class="hub-btn">View Cart &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon blue">📍</div>
                        <div>
                            <span class="hub-badge badge-core">Logistics</span>
                            <h3>Order Tracking</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Track real-time multi-stage status from Order Placed to Shipped and Delivered.</div>
                </div>
                <a href="track_order.php" class="hub-btn">Track Orders &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon orange">👤</div>
                        <div>
                            <span class="hub-badge badge-core">Customer</span>
                            <h3>User Account</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Address book, past orders, monthly expense tracker, and price drop notifications.</div>
                </div>
                <a href="account.php" class="hub-btn">Open Account Hub &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon purple">💬</div>
                        <div>
                            <span class="hub-badge badge-core">Feedback</span>
                            <h3>Customer Feedback</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Submit marketplace ratings, comments, and experience feedback.</div>
                </div>
                <a href="feedback.php" class="hub-btn">Give Feedback &rarr;</a>
            </div>
        </div>

        <!-- 2. ADMINISTRATOR CONTROL PANEL -->
        <div class="hub-section-title">
            🛡️ Administrator Management Suite
        </div>
        <div class="hub-grid">
            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon red">📊</div>
                        <div>
                            <span class="hub-badge badge-admin">Admin</span>
                            <h3>Admin Dashboard</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Sales summaries, revenue metrics, recent orders, inventory alerts, and quick actions.</div>
                </div>
                <a href="admin/index.php" class="hub-btn">Admin Dashboard &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon red">🏷️</div>
                        <div>
                            <span class="hub-badge badge-admin">Admin</span>
                            <h3>Product Inventory</h3>
                        </div>
                    </div>
                    <div class="hub-desc">CRUD product catalog, stock adjustments, Best Value & Eco score editing, image uploads.</div>
                </div>
                <a href="admin/products.php" class="hub-btn">Manage Products &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon red">📦</div>
                        <div>
                            <span class="hub-badge badge-admin">Admin</span>
                            <h3>Order Fulfillment</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Process orders, update tracking status (Placed, Shipped, Delivered), and manage refunds.</div>
                </div>
                <a href="admin/orders.php" class="hub-btn">Manage Orders &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon red">📈</div>
                        <div>
                            <span class="hub-badge badge-admin">Admin</span>
                            <h3>Analytics & Export</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Revenue charts, top categories, best sellers, and 1-click CSV order/sales report export.</div>
                </div>
                <a href="admin/reports.php" class="hub-btn">View Reports &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon red">🔒</div>
                        <div>
                            <span class="hub-badge badge-admin">Admin</span>
                            <h3>Security Audit Logs</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Tamper-evident logs of logins, password updates, status adjustments, and admin actions.</div>
                </div>
                <a href="admin/audit_logs.php" class="hub-btn">View Audit Logs &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon red">⭐</div>
                        <div>
                            <span class="hub-badge badge-admin">Admin</span>
                            <h3>Review Moderation</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Approve or moderate user ratings with calculated Review Trust Scores.</div>
                </div>
                <a href="admin/reviews.php" class="hub-btn">Moderate Reviews &rarr;</a>
            </div>
        </div>

        <!-- 3. VERIFICATION & ACADEMIC DELIVERABLES -->
        <div class="hub-section-title">
            📋 Project Documentation & Verification
        </div>
        <div class="hub-grid">
            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon green">🧪</div>
                        <div>
                            <span class="hub-badge badge-test">Unit Tests</span>
                            <h3>Automated Test Suite</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Run all 13 automated tests verifying database, password hashing, CSRF, and routing.</div>
                </div>
                <a href="test_runner.php" target="_blank" class="hub-btn">Run Test Suite &rarr;</a>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon green">🌐</div>
                        <div>
                            <span class="hub-badge badge-test">I18N</span>
                            <h3>Language Switcher</h3>
                        </div>
                    </div>
                    <div class="hub-desc">Switch between English, Marathi (मराठी), and Hindi (हिंदी) with persistence.</div>
                </div>
                <div style="display: flex; gap: 8px; margin-top: auto;">
                    <a href="index.php?lang=en" class="hub-btn" style="flex:1;">EN</a>
                    <a href="index.php?lang=mr" class="hub-btn" style="flex:1;">मराठी</a>
                    <a href="index.php?lang=hi" class="hub-btn" style="flex:1;">हिंदी</a>
                </div>
            </div>

            <div class="hub-card">
                <div>
                    <div class="hub-card-top">
                        <div class="hub-icon green">📖</div>
                        <div>
                            <span class="hub-badge badge-test">Docs</span>
                            <h3>Project Report (BCA)</h3>
                        </div>
                    </div>
                    <div class="hub-desc">20-Chapter academic documentation for BCA Semester V submission.</div>
                </div>
                <a href="PROJECT_REPORT_BCA.md" target="_blank" class="hub-btn">Open Project Report &rarr;</a>
            </div>
        </div>

    </div>

</body>
</html>
