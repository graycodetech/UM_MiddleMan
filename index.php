<?php
require_once 'session_check.php';

// --- Force password change if needed ---
if (isset($_SESSION['is_temp_password']) && $_SESSION['is_temp_password'] === true) {
    header('Location: change_password.php');
    exit;
}

require_once 'class/TransactionManager.php';

// --- DATA FETCHING & PAGINATION LOGIC ---
$transactionManager = new TransactionManager();

// Get latest balance
$latest_balance = $transactionManager->getLatestOrgAccountBalance();

// Search parameters
$search_term = $_GET['search_term'] ?? '';
$search_type = $_GET['search_type'] ?? 'trans_id';

// Pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if (!in_array($items_per_page, [50, 100, 200])) {
    $items_per_page = 50;
}

$search_params = ['term' => $search_term, 'type' => $search_type];
$result = $transactionManager->getTransactions($search_params, $page, $items_per_page);

$transactions = $result['transactions'];
$total_transactions = $result['total'];
$total_pages = ceil($total_transactions / $items_per_page);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h1>Transaction Dashboard</h1>
        <div class="header-meta">
            <div class="balance-display">
                Latest Organization Balance: <strong>Ksh <?php echo htmlspecialchars(number_format((float)$latest_balance, 2)); ?></strong>
            </div>
            <div class="user-info">
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="manage_users.php" class="btn btn-secondary">Manage Users</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="card search-card">
            <form action="index.php" method="get" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="search_term">Search Term</label>
                        <input type="text" name="search_term" id="search_term" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Enter search term...">
                    </div>
                    <div class="form-group">
                        <label for="search_type">Search By</label>
                        <select name="search_type" id="search_type">
                            <option value="trans_id" <?php echo ($search_type === 'trans_id') ? 'selected' : ''; ?>>Transaction ID</option>
                            <option value="account_number" <?php echo ($search_type === 'account_number') ? 'selected' : ''; ?>>Account Number</option>
                            <option value="account_name" <?php echo ($search_type === 'account_name') ? 'selected' : ''; ?>>Account Name</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="limit">Items per page</label>
                        <select name="limit" id="limit" onchange="this.form.submit()">
                            <option value="50" <?php echo ($items_per_page === 50) ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo ($items_per_page === 100) ? 'selected' : ''; ?>>100</option>
                            <option value="200" <?php echo ($items_per_page === 200) ? 'selected' : ''; ?>>200</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">Search</button>
                         <a href="index.php" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card transactions-card">
            <h3>Transactions</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Trans ID</th>
                            <th>Timestamp</th>
                            <th>Account No.</th>
                            <th>Account Name</th>
                            <th>Amount (KES)</th>
                            <th>Payer Name</th>
                            <th>Status</th>
                            <th>API Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="8">No transactions found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($txn['trans_id']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['trans_time']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['bill_ref_number']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['um_account_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(number_format((float)$txn['trans_amount'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($txn['first_name']); ?></td>
                                    <td><span class="status <?php echo strtolower(str_replace('_', '-', $txn['processing_status'] ?? 'pending')); ?>"><?php echo htmlspecialchars($txn['processing_status'] ?? 'PENDING'); ?></span></td>
                                    <td><?php echo htmlspecialchars($txn['um_api_response_message'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <div class="page-info">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?> (Total: <?php echo $total_transactions; ?>)
                </div>
                <div class="page-links">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $items_per_page; ?>&search_term=<?php echo urlencode($search_term); ?>&search_type=<?php echo $search_type; ?>" class="btn">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $items_per_page; ?>&search_term=<?php echo urlencode($search_term); ?>&search_type=<?php echo $search_type; ?>" class="btn">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
