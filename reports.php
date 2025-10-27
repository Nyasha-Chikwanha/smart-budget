<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['sb_logged_in']) || $_SESSION['sb_logged_in'] !== true) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['sb_user_id'];
$user_name = $_SESSION['sb_user_name'] ?? 'User';
$user_email = $_SESSION['sb_user_email'] ?? '';

// Database configuration - UPDATE THESE WITH YOUR ACTUAL CREDENTIALS
$host = 'localhost';
$dbname = 'smart_budget';
$username = 'your_username';      // ← Change to your actual MySQL username
$password = 'your_password';      // ← Change to your actual MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If database connection fails, use sample data for demo
    $reports_data = [
        'total_income' => 12500.00,
        'total_expenses' => 8450.75,
        'net_balance' => 4049.25,
        'monthly_expenses_by_category' => [
            ['category' => 'Groceries', 'total' => 450.00, 'count' => 12],
            ['category' => 'Utilities', 'total' => 320.50, 'count' => 6],
            ['category' => 'Entertainment', 'total' => 280.00, 'count' => 8],
            ['category' => 'Transportation', 'total' => 180.25, 'count' => 10],
            ['category' => 'Dining Out', 'total' => 220.00, 'count' => 5]
        ],
        'recent_expenses' => [
            ['category' => 'Groceries', 'amount' => 85.50, 'description' => 'Weekly shopping', 'date_spent' => '2025-01-15'],
            ['category' => 'Utilities', 'amount' => 120.00, 'description' => 'Electricity bill', 'date_spent' => '2025-01-14'],
            ['category' => 'Entertainment', 'amount' => 45.00, 'description' => 'Movie tickets', 'date_spent' => '2025-01-13'],
            ['category' => 'Transportation', 'amount' => 35.25, 'description' => 'Gas fill-up', 'date_spent' => '2025-01-12'],
            ['category' => 'Dining Out', 'amount' => 68.00, 'description' => 'Dinner with friends', 'date_spent' => '2025-01-11']
        ],
        'income_sources' => [
            ['source' => 'Salary', 'total' => 12000.00, 'count' => 4],
            ['source' => 'Freelance', 'total' => 500.00, 'count' => 2]
        ],
        'monthly_trends' => [
            ['month' => '2025-01', 'monthly_expenses' => 1250.75],
            ['month' => '2024-12', 'monthly_expenses' => 1180.50],
            ['month' => '2024-11', 'monthly_expenses' => 1320.25],
            ['month' => '2024-10', 'monthly_expenses' => 1100.00],
            ['month' => '2024-09', 'monthly_expenses' => 1250.00],
            ['month' => '2024-08', 'monthly_expenses' => 1350.25]
        ],
        'demo_mode' => true
    ];
}

// If database connection was successful, fetch real data
if (isset($pdo)) {
    try {
        // Total income
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_income FROM income WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $reports_data['total_income'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_income'];

        // Total expenses
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_expenses FROM expenses WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $reports_data['total_expenses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_expenses'];

        // Net balance
        $reports_data['net_balance'] = $reports_data['total_income'] - $reports_data['total_expenses'];

        // Monthly expenses by category
        $stmt = $pdo->prepare("
            SELECT category, SUM(amount) as total, COUNT(*) as count 
            FROM expenses 
            WHERE user_id = ? AND MONTH(date_spent) = MONTH(CURRENT_DATE()) 
            GROUP BY category 
            ORDER BY total DESC
        ");
        $stmt->execute([$user_id]);
        $reports_data['monthly_expenses_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent expenses (last 30 days)
        $stmt = $pdo->prepare("
            SELECT category, amount, description, date_spent 
            FROM expenses 
            WHERE user_id = ? AND date_spent >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            ORDER BY date_spent DESC 
            LIMIT 20
        ");
        $stmt->execute([$user_id]);
        $reports_data['recent_expenses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Income sources
        $stmt = $pdo->prepare("
            SELECT source, SUM(amount) as total, COUNT(*) as count 
            FROM income 
            WHERE user_id = ? 
            GROUP BY source 
            ORDER BY total DESC
        ");
        $stmt->execute([$user_id]);
        $reports_data['income_sources'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Monthly trends (last 6 months)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(date_spent, '%Y-%m') as month,
                SUM(amount) as monthly_expenses
            FROM expenses 
            WHERE user_id = ? AND date_spent >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(date_spent, '%Y-%m')
            ORDER BY month DESC
        ");
        $stmt->execute([$user_id]);
        $reports_data['monthly_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $reports_data['demo_mode'] = false;

    } catch (PDOException $e) {
        error_log("Error fetching reports data: " . $e->getMessage());
        $reports_data['error'] = "Unable to load reports data";
        $reports_data['demo_mode'] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget — Financial Reports</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #6c5ce7 0%, #81ecec 100%) fixed;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: #0f476a;
            color: white;
            padding: 1.5rem 0;
            text-align: center;
            margin-bottom: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .demo-notice {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #ffc107;
            text-align: center;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e8f5e9;
            border: 3px solid #2e7d32;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #2e7d32;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .nav-links a {
            color: #6c5ce7;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: #f0f0f0;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .report-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
        }
        
        .report-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            border-bottom: 2px solid #6c5ce7;
            padding-bottom: 0.5rem;
        }
        
        .financial-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .summary-card.income {
            border-top: 4px solid #2e7d32;
        }
        
        .summary-card.expenses {
            border-top: 4px solid #d32f2f;
        }
        
        .summary-card.balance {
            border-top: 4px solid #6c5ce7;
        }
        
        .summary-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        
        .income .summary-value {
            color: #2e7d32;
        }
        
        .expenses .summary-value {
            color: #d32f2f;
        }
        
        .balance .summary-value {
            color: #6c5ce7;
        }
        
        .category-list {
            list-style: none;
        }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-name {
            font-weight: 500;
        }
        
        .category-amount {
            font-weight: bold;
            color: #d32f2f;
        }
        
        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 8px;
            margin: 0.5rem 0;
            overflow: hidden;
        }
        
        .progress-fill {
            background: #6c5ce7;
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .chart-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .chart-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 3rem;
            text-align: center;
            color: #6c757d;
        }
        
        .recent-expenses {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .expense-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin: 0.5rem 0;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #6c5ce7;
        }
        
        .expense-details {
            flex: 1;
        }
        
        .expense-category {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .expense-description {
            color: #666;
            font-size: 0.9rem;
        }
        
        .expense-amount {
            font-weight: bold;
            color: #d32f2f;
            font-size: 1.1rem;
        }
        
        .expense-date {
            color: #999;
            font-size: 0.8rem;
        }
        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-style: italic;
        }
        
        .bottom-tabs {
            display: flex;
            justify-content: center;
            gap: 2rem;
            background: #1a2a4f;
            padding: 1rem 0;
            border-radius: 12px;
            margin-top: 2rem;
        }
        
        .bottom-tabs .tab {
            color: #81ecec;
            font-weight: 500;
            text-decoration: none;
            font-size: 1.1rem;
            transition: color 0.2s;
        }
        
        .bottom-tabs .tab:hover {
            color: #a29bfe;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .financial-summary {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .bottom-tabs {
                flex-wrap: wrap;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>📊 Financial Reports</h1>
            <p>Comprehensive analysis of your financial health</p>
        </header>

        <?php if (isset($reports_data['demo_mode']) && $reports_data['demo_mode']): ?>
            <div class="demo-notice">
                ⚠️ Showing demo data. Connect to database to see your actual financial reports.
            </div>
        <?php endif; ?>

        <div class="dashboard-header">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div>
                    <h2>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p>Here's your financial overview</p>
                </div>
            </div>
            <div class="nav-links">
                <a href="welcome.html">← Back to Dashboard</a>
                <a href="budget.html">Add Expense</a>
                <a href="login.html" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="financial-summary">
            <div class="summary-card income">
                <h3>Total Income</h3>
                <div class="summary-value">$<?php echo number_format($reports_data['total_income'], 2); ?></div>
                <p>All time income</p>
            </div>
            <div class="summary-card expenses">
                <h3>Total Expenses</h3>
                <div class="summary-value">$<?php echo number_format($reports_data['total_expenses'], 2); ?></div>
                <p>All time expenses</p>
            </div>
            <div class="summary-card balance">
                <h3>Net Balance</h3>
                <div class="summary-value" style="color: <?php echo $reports_data['net_balance'] >= 0 ? '#2e7d32' : '#d32f2f'; ?>">
                    $<?php echo number_format($reports_data['net_balance'], 2); ?>
                </div>
                <p>Current financial position</p>
            </div>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
            <!-- Monthly Expenses by Category -->
            <div class="report-card">
                <h3>📈 Monthly Expenses by Category</h3>
                <?php if (!empty($reports_data['monthly_expenses_by_category'])): ?>
                    <ul class="category-list">
                        <?php 
                        $max_expense = max(array_column($reports_data['monthly_expenses_by_category'], 'total'));
                        foreach ($reports_data['monthly_expenses_by_category'] as $category): 
                            $percentage = ($category['total'] / $max_expense) * 100;
                        ?>
                            <li class="category-item">
                                <div>
                                    <div class="category-name"><?php echo htmlspecialchars($category['category']); ?></div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                                <div class="category-amount">
                                    $<?php echo number_format($category['total'], 2); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-data">No expense data available for this month</div>
                <?php endif; ?>
            </div>

            <!-- Income Sources -->
            <div class="report-card">
                <h3>💰 Income Sources</h3>
                <?php if (!empty($reports_data['income_sources'])): ?>
                    <ul class="category-list">
                        <?php foreach ($reports_data['income_sources'] as $source): ?>
                            <li class="category-item">
                                <span class="category-name"><?php echo htmlspecialchars($source['source']); ?></span>
                                <span style="color: #2e7d32; font-weight: bold;">
                                    $<?php echo number_format($source['total'], 2); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-data">No income data available</div>
                <?php endif; ?>
            </div>

            <!-- Monthly Trends -->
            <div class="report-card">
                <h3>📅 6-Month Expense Trends</h3>
                <?php if (!empty($reports_data['monthly_trends'])): ?>
                    <ul class="category-list">
                        <?php foreach ($reports_data['monthly_trends'] as $trend): ?>
                            <li class="category-item">
                                <span class="category-name"><?php echo date('F Y', strtotime($trend['month'] . '-01')); ?></span>
                                <span class="category-amount">$<?php echo number_format($trend['monthly_expenses'], 2); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-data">No trend data available</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chart Placeholder -->
        <div class="chart-container">
            <h3>📊 Expense Distribution</h3>
            <div class="chart-placeholder">
                <p>📈 Chart visualization would appear here</p>
                <p><small>Pie chart showing expense distribution across categories</small></p>
            </div>
        </div>

        <!-- Recent Expenses -->
        <div class="recent-expenses">
            <h3>🕒 Recent Expenses (Last 30 Days)</h3>
            <?php if (!empty($reports_data['recent_expenses'])): ?>
                <?php foreach ($reports_data['recent_expenses'] as $expense): ?>
                    <div class="expense-item">
                        <div class="expense-details">
                            <div class="expense-category"><?php echo htmlspecialchars($expense['category']); ?></div>
                            <div class="expense-description"><?php echo htmlspecialchars($expense['description']); ?></div>
                            <div class="expense-date"><?php echo date('M j, Y', strtotime($expense['date_spent'])); ?></div>
                        </div>
                        <div class="expense-amount">$<?php echo number_format($expense['amount'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">No recent expenses found</div>
            <?php endif; ?>
        </div>

        <!-- Bottom Navigation -->
        <footer class="bottom-tabs">
            <a class="tab" href="welcome.html">Dashboard</a>
            <a class="tab" href="budget.html">Add Expense</a>
            <a class="tab" href="Savings.html">Savings Goals</a>
            <a class="tab" href="Premium.html">Go Premium</a>
            <a class="tab" href="login.html" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
        </footer>
    </div>

    <script>
        // Simple JavaScript for enhanced interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading animation to summary cards
            const summaryCards = document.querySelectorAll('.summary-card');
            summaryCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Add hover effects to report cards
            const reportCards = document.querySelectorAll('.report-card');
            reportCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                });
            });

            // Print functionality
            const printButton = document.createElement('button');
            printButton.textContent = '🖨️ Print Report';
            printButton.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #6c5ce7;
                color: white;
                border: none;
                padding: 12px 20px;
                border-radius: 25px;
                cursor: pointer;
                font-weight: bold;
                box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
                z-index: 1000;
            `;
            printButton.addEventListener('click', function() {
                window.print();
            });
            document.body.appendChild(printButton);
        });
    </script>
</body>
</html>