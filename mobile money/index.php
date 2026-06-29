<?php
// ============================================================
// PAYMENT TRANSACTION SYSTEM
// Matches the pseudocode algorithms provided
// ============================================================

session_start();

// Initialize the MainArray in session if not exists
if (!isset($_SESSION['MainArray'])) {
    $_SESSION['MainArray'] = [];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    // ALGORITHM InsertSmallTransaction(paymentDetails, MainArray)
    if ($action === 'insert') {
        $paymentDetails = [
            'ID' => uniqid('PAY_'),
            'amount' => floatval($_POST['amount'] ?? 0),
            'payerName' => htmlspecialchars($_POST['payerName'] ?? ''),
            'description' => htmlspecialchars($_POST['description'] ?? ''),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // MainArray.Append(paymentDetails)
        $_SESSION['MainArray'][] = $paymentDetails;
        
        // RETURN "Success: Payment Added"
        echo json_encode([
            'status' => 'Success: Payment Added',
            'payment' => $paymentDetails
        ]);
        exit;
    }
    
    // ALGORITHM LinearSearchTransaction(paymentID, MainArray)
    if ($action === 'search') {
        $paymentID = $_POST['paymentID'] ?? '';
        $found = null;
        
        // FOR EACH item IN MainArray:
        foreach ($_SESSION['MainArray'] as $item) {
            // IF item.ID EQUALS paymentID THEN
            if ($item['ID'] === $paymentID) {
                // RETURN item
                $found = $item;
                break;
            }
        }
        
        // RETURN "Error: Not Found" (or the item)
        if ($found) {
            echo json_encode(['status' => 'found', 'payment' => $found]);
        } else {
            echo json_encode(['status' => 'Error: Not Found']);
        }
        exit;
    }
    
    if ($action === 'getAll') {
        echo json_encode(['payments' => $_SESSION['MainArray']]);
        exit;
    }
    
    if ($action === 'clear') {
        $_SESSION['MainArray'] = [];
        echo json_encode(['status' => 'All transactions cleared']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Transaction System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            color: #eaeaea;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #e94560, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            text-align: center;
            color: #a0a0a0;
            margin-bottom: 40px;
            font-size: 0.95rem;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 28px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .card-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .icon-insert {
            background: linear-gradient(135deg, #00b894, #00cec9);
        }

        .icon-search {
            background: linear-gradient(135deg, #e17055, #fdcb6e);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #fff;
        }

        .card-subtitle {
            font-size: 0.8rem;
            color: #888;
            margin-top: 2px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.85rem;
            color: #b0b0b0;
            font-weight: 500;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            outline: none;
        }

        input[type="text"]:focus,
        input[type="number"]:focus {
            border-color: #e94560;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.15);
        }

        input::placeholder {
            color: #666;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e94560, #c0392b);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(233, 69, 96, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 92, 231, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #636e72, #b2bec3);
            color: white;
            font-size: 0.85rem;
            padding: 10px;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #e17055, #d63031);
        }

        .result-box {
            margin-top: 18px;
            padding: 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            display: none;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-success {
            background: rgba(0, 184, 148, 0.15);
            border: 1px solid rgba(0, 184, 148, 0.3);
            color: #00b894;
        }

        .result-error {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #e74c3c;
        }

        .result-found {
            background: rgba(108, 92, 231, 0.15);
            border: 1px solid rgba(108, 92, 231, 0.3);
            color: #a29bfe;
        }

        /* Transaction List */
        .transactions-section {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 28px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .badge {
            background: rgba(233, 69, 96, 0.2);
            color: #e94560;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .transaction-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .transaction-list::-webkit-scrollbar {
            width: 6px;
        }

        .transaction-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }

        .transaction-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .transaction-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .transaction-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(233, 69, 96, 0.3);
            transform: translateX(5px);
        }

        .transaction-info {
            flex: 1;
        }

        .transaction-id {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            color: #e94560;
            margin-bottom: 4px;
        }

        .transaction-payer {
            font-weight: 600;
            font-size: 1rem;
            color: #fff;
        }

        .transaction-desc {
            font-size: 0.8rem;
            color: #888;
            margin-top: 2px;
        }

        .transaction-meta {
            text-align: right;
        }

        .transaction-amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: #00b894;
        }

        .transaction-time {
            font-size: 0.75rem;
            color: #666;
            margin-top: 4px;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .payment-details-found {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #888;
            font-size: 0.85rem;
        }

        .detail-value {
            color: #fff;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .copy-hint {
            font-size: 0.75rem;
            color: #666;
            margin-top: 8px;
            font-style: italic;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(0, 184, 148, 0.9);
            color: white;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 500;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.4s ease;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .pseudocode-ref {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            color: #a0a0a0;
            border-left: 3px solid #e94560;
        }

        .pseudocode-ref code {
            color: #fdcb6e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💳 Payment Transaction System</h1>
        <p class="subtitle">Implements InsertSmallTransaction & LinearSearchTransaction algorithms</p>

        <div class="grid">
            <!-- Insert Transaction Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon icon-insert">➕</div>
                    <div>
                        <div class="card-title">Insert Transaction</div>
                        <div class="card-subtitle">Algorithm: InsertSmallTransaction()</div>
                    </div>
                </div>

                <div class="pseudocode-ref">
                    <code>MainArray.Append(paymentDetails)</code><br>
                    <code>RETURN "Success: Payment Added"</code>
                </div>

                <form id="insertForm">
                    <div class="form-group">
                        <label for="payerName">Payer Name</label>
                        <input type="text" id="payerName" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount ($)</label>
                        <input type="number" id="amount" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" placeholder="e.g. Monthly subscription" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner" id="insertSpinner"></span>
                        <span id="insertBtnText">Add Payment</span>
                    </button>
                </form>

                <div id="insertResult" class="result-box"></div>
            </div>

            <!-- Search Transaction Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon icon-search">🔍</div>
                    <div>
                        <div class="card-title">Search Transaction</div>
                        <div class="card-subtitle">Algorithm: LinearSearchTransaction()</div>
                    </div>
                </div>

                <div class="pseudocode-ref">
                    <code>FOR EACH item IN MainArray</code><br>
                    <code>&nbsp;&nbsp;IF item.ID EQUALS paymentID</code><br>
                    <code>&nbsp;&nbsp;&nbsp;&nbsp;RETURN item</code>
                </div>

                <form id="searchForm">
                    <div class="form-group">
                        <label for="searchPaymentID">Payment ID</label>
                        <input type="text" id="searchPaymentID" placeholder="e.g. PAY_64a2b8c..." required>
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <span class="spinner" id="searchSpinner"></span>
                        <span id="searchBtnText">Search Payment</span>
                    </button>
                </form>

                <div id="searchResult" class="result-box"></div>
                <p class="copy-hint">💡 Tip: Click any transaction below to auto-fill its ID</p>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="transactions-section">
            <div class="section-header">
                <div>
                    <span class="section-title">📋 MainArray Contents</span>
                    <span class="badge" id="transactionCount">0 items</span>
                </div>
                <button class="btn btn-danger" onclick="clearAllTransactions()" style="width: auto; padding: 8px 16px;">
                    🗑️ Clear All
                </button>
            </div>
            <div id="transactionList" class="transaction-list">
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>No transactions in MainArray yet.</p>
                    <p style="font-size: 0.85rem; margin-top: 5px;">Add a payment using the form above.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script>
        // ============================================================
        // JAVASCRIPT - Frontend Logic
        // ============================================================

        // Show toast notification
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // Show/hide spinner
        function setLoading(btnId, spinnerId, textId, isLoading, defaultText) {
            const spinner = document.getElementById(spinnerId);
            const text = document.getElementById(textId);
            spinner.style.display = isLoading ? 'inline-block' : 'none';
            text.textContent = isLoading ? 'Processing...' : defaultText;
        }

        // Show result message
        function showResult(elementId, message, type) {
            const el = document.getElementById(elementId);
            el.innerHTML = message;
            el.className = 'result-box result-' + type;
            el.style.display = 'block';
        }

        // Hide result
        function hideResult(elementId) {
            document.getElementById(elementId).style.display = 'none';
        }

        // AJAX helper
        async function postData(action, data) {
            const formData = new FormData();
            formData.append('action', action);
            for (const key in data) {
                formData.append(key, data[key]);
            }

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            return await response.json();
        }

        // Load and display all transactions
        async function loadTransactions() {
            const data = await postData('getAll', {});
            const list = document.getElementById('transactionList');
            const count = document.getElementById('transactionCount');

            count.textContent = `${data.payments.length} item${data.payments.length !== 1 ? 's' : ''}`;

            if (data.payments.length === 0) {
                list.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <p>No transactions in MainArray yet.</p>
                        <p style="font-size: 0.85rem; margin-top: 5px;">Add a payment using the form above.</p>
                    </div>
                `;
                return;
            }

            list.innerHTML = data.payments.map(payment => `
                <div class="transaction-item" onclick="fillSearchId('${payment.ID}')" title="Click to search this transaction">
                    <div class="transaction-info">
                        <div class="transaction-id">🆔 ${payment.ID}</div>
                        <div class="transaction-payer">${escapeHtml(payment.payerName)}</div>
                        <div class="transaction-desc">${escapeHtml(payment.description)}</div>
                    </div>
                    <div class="transaction-meta">
                        <div class="transaction-amount">$${parseFloat(payment.amount).toFixed(2)}</div>
                        <div class="transaction-time">${payment.timestamp}</div>
                    </div>
                </div>
            `).join('');
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Fill search input with transaction ID
        function fillSearchId(id) {
            document.getElementById('searchPaymentID').value = id;
            document.getElementById('searchPaymentID').focus();
            showToast('Transaction ID copied to search field');
        }

        // Clear all transactions
        async function clearAllTransactions() {
            if (!confirm('Are you sure you want to clear all transactions?')) return;
            
            const data = await postData('clear', {});
            showToast(data.status);
            loadTransactions();
            hideResult('insertResult');
            hideResult('searchResult');
        }

        // Handle Insert Form
        document.getElementById('insertForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            hideResult('insertResult');
            setLoading('insertForm', 'insertSpinner', 'insertBtnText', true, 'Add Payment');

            const result = await postData('insert', {
                payerName: document.getElementById('payerName').value,
                amount: document.getElementById('amount').value,
                description: document.getElementById('description').value
            });

            setLoading('insertForm', 'insertSpinner', 'insertBtnText', false, 'Add Payment');

            if (result.status === 'Success: Payment Added') {
                showResult('insertResult', `
                    <strong>✅ ${result.status}</strong><br>
                    <span style="font-size: 0.85rem;">ID: <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">${result.payment.ID}</code></span>
                `, 'success');
                
                // Reset form
                this.reset();
                showToast('Payment added successfully!');
                loadTransactions();
            } else {
                showResult('insertResult', `<strong>❌ Error:</strong> ${result.status}`, 'error');
            }
        });

        // Handle Search Form
        document.getElementById('searchForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            hideResult('searchResult');
            setLoading('searchForm', 'searchSpinner', 'searchBtnText', true, 'Search Payment');

            const paymentID = document.getElementById('searchPaymentID').value;
            const result = await postData('search', { paymentID });

            setLoading('searchForm', 'searchSpinner', 'searchBtnText', false, 'Search Payment');

            if (result.status === 'found') {
                const p = result.payment;
                showResult('searchResult', `
                    <strong>✅ Transaction Found!</strong>
                    <div class="payment-details-found">
                        <div class="detail-row">
                            <span class="detail-label">Payment ID</span>
                            <span class="detail-value"><code>${p.ID}</code></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payer</span>
                            <span class="detail-value">${escapeHtml(p.payerName)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Amount</span>
                            <span class="detail-value" style="color: #00b894;">$${parseFloat(p.amount).toFixed(2)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Description</span>
                            <span class="detail-value">${escapeHtml(p.description)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Timestamp</span>
                            <span class="detail-value">${p.timestamp}</span>
                        </div>
                    </div>
                `, 'found');
            } else {
                showResult('searchResult', `<strong>❌ ${result.status}</strong><br><span style="font-size: 0.85rem;">Payment ID "<code>${escapeHtml(paymentID)}</code>" does not exist in MainArray.</span>`, 'error');
            }
        });

        // Load transactions on page load
        document.addEventListener('DOMContentLoaded', loadTransactions);
    </script>
</body>
</html>