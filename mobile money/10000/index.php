<?php
// =============================================================================
// LARGE SCALE TRANSACTION SYSTEM - SINGLE FILE
// =============================================================================
// Data Structures: HashSet, HashTable, PriorityHeap, AVLTree
// Algorithms: InsertLargeScale, SearchLargeScale
// Features: End-of-Day Reports by Timestamp
// =============================================================================

// --- SESSION PERSISTENCE FOR DEMO ---
session_start();
if (!isset($_SESSION['transaction_system'])) {
    $_SESSION['transaction_system'] = [
        'hashSet' => [],
        'hashTable' => [],
        'payments' => [],
        'transactions' => [],
        'log' => []
    ];
}

// --- DATA STRUCTURES ---

class Payment {
    public string $ID;
    public float $Value;
    public int $Timestamp;
    public array $Data;
    
    public function __construct(string $id, float $value, int $timestamp, array $data = []) {
        $this->ID = $id;
        $this->Value = $value;
        $this->Timestamp = $timestamp;
        $this->Data = $data;
    }
    
    public function toArray(): array {
        return [
            'ID' => $this->ID,
            'Value' => $this->Value,
            'Timestamp' => $this->Timestamp,
            'Date' => date('Y-m-d H:i:s', $this->Timestamp),
            'Data' => $this->Data
        ];
    }
}

// AVL Tree Node
class AVLNode {
    public int $key;
    public Payment $payment;
    public int $height = 1;
    public ?AVLNode $left = null;
    public ?AVLNode $right = null;
    
    public function __construct(int $key, Payment $payment) {
        $this->key = $key;
        $this->payment = $payment;
    }
}

// AVL Tree for Timestamp-based Range Queries
class AVLTree {
    private ?AVLNode $root = null;
    private int $size = 0;
    
    private function height(?AVLNode $node): int {
        return $node ? $node->height : 0;
    }
    
    private function balanceFactor(?AVLNode $node): int {
        return $this->height($node->left) - $this->height($node->right);
    }
    
    private function updateHeight(AVLNode $node): void {
        $node->height = 1 + max($this->height($node->left), $this->height($node->right));
    }
    
    private function rotateRight(AVLNode $y): AVLNode {
        $x = $y->left;
        $T2 = $x->right;
        $x->right = $y;
        $y->left = $T2;
        $this->updateHeight($y);
        $this->updateHeight($x);
        return $x;
    }
    
    private function rotateLeft(AVLNode $x): AVLNode {
        $y = $x->right;
        $T2 = $y->left;
        $y->left = $x;
        $x->right = $T2;
        $this->updateHeight($x);
        $this->updateHeight($y);
        return $y;
    }
    
    public function insert(int $timestamp, Payment $payment): void {
        $this->root = $this->insertNode($this->root, $timestamp, $payment);
        $this->size++;
    }
    
    private function insertNode(?AVLNode $node, int $key, Payment $payment): AVLNode {
        if (!$node) return new AVLNode($key, $payment);
        
        if ($key < $node->key) {
            $node->left = $this->insertNode($node->left, $key, $payment);
        } else {
            $node->right = $this->insertNode($node->right, $key, $payment);
        }
        
        $this->updateHeight($node);
        $balance = $this->balanceFactor($node);
        
        // Left Left
        if ($balance > 1 && $key < $node->left->key) {
            return $this->rotateRight($node);
        }
        // Right Right
        if ($balance < -1 && $key > $node->right->key) {
            return $this->rotateLeft($node);
        }
        // Left Right
        if ($balance > 1 && $key > $node->left->key) {
            $node->left = $this->rotateLeft($node->left);
            return $this->rotateRight($node);
        }
        // Right Left
        if ($balance < -1 && $key < $node->right->key) {
            $node->right = $this->rotateRight($node->right);
            return $this->rotateLeft($node);
        }
        
        return $node;
    }
    
    public function rangeQuery(int $startTime, int $endTime): array {
        $result = [];
        $this->inOrderRange($this->root, $startTime, $endTime, $result);
        return $result;
    }
    
    private function inOrderRange(?AVLNode $node, int $start, int $end, array &$result): void {
        if (!$node) return;
        if ($node->key > $start) $this->inOrderRange($node->left, $start, $end, $result);
        if ($node->key >= $start && $node->key <= $end) $result[] = $node->payment->toArray();
        if ($node->key < $end) $this->inOrderRange($node->right, $start, $end, $result);
    }
    
    public function getSize(): int { return $this->size; }
}

// Priority Heap (Max/Min)
class PriorityHeap {
    private array $heap = [];
    private bool $isMaxHeap;
    
    public function __construct(bool $isMaxHeap = true) {
        $this->isMaxHeap = $isMaxHeap;
    }
    
    private function compare(Payment $a, Payment $b): bool {
        return $this->isMaxHeap ? $a->Value > $b->Value : $a->Value < $b->Value;
    }
    
    private function siftUp(int $index): void {
        while ($index > 0) {
            $parent = (int)(($index - 1) / 2);
            if (!$this->compare($this->heap[$index], $this->heap[$parent])) break;
            [$this->heap[$index], $this->heap[$parent]] = [$this->heap[$parent], $this->heap[$index]];
            $index = $parent;
        }
    }
    
    private function siftDown(int $index): void {
        $size = count($this->heap);
        while (true) {
            $best = $index;
            $left = 2 * $index + 1;
            $right = 2 * $index + 2;
            if ($left < $size && $this->compare($this->heap[$left], $this->heap[$best])) $best = $left;
            if ($right < $size && $this->compare($this->heap[$right], $this->heap[$best])) $best = $right;
            if ($best === $index) break;
            [$this->heap[$index], $this->heap[$best]] = [$this->heap[$best], $this->heap[$index]];
            $index = $best;
        }
    }
    
    public function insert(float $value, Payment $payment): void {
        $this->heap[] = $payment;
        $this->siftUp(count($this->heap) - 1);
    }
    
    public function getAll(): array {
        $sorted = $this->heap;
        usort($sorted, function($a, $b) {
            return $this->isMaxHeap ? $b->Value <=> $a->Value : $a->Value <=> $b->Value;
        });
        return array_map(fn($p) => $p->toArray(), $sorted);
    }
    
    public function getSize(): int { return count($this->heap); }
}

// --- MAIN TRANSACTION SYSTEM ---

class LargeScaleTransactionSystem {
    private array $hashSet = [];
    private array $hashTable = [];
    private AVLTree $avlTree;
    private PriorityHeap $maxHeap;
    private PriorityHeap $minHeap;
    private array $transactionLog = [];
    
    public function __construct(array $sessionData = null) {
        if ($sessionData) {
            $this->hashSet = $sessionData['hashSet'];
            $this->hashTable = [];
            foreach ($sessionData['hashTable'] as $id => $data) {
                $this->hashTable[$id] = new Payment($data['ID'], $data['Value'], $data['Timestamp'], $data['Data']);
            }
        }
        $this->avlTree = new AVLTree();
        $this->maxHeap = new PriorityHeap(true);
        $this->minHeap = new PriorityHeap(false);
        
        // Rebuild heaps and AVL from session
        foreach ($this->hashTable as $payment) {
            $this->maxHeap->insert($payment->Value, $payment);
            $this->minHeap->insert($payment->Value, $payment);
            $this->avlTree->insert($payment->Timestamp, $payment);
        }
    }
    
    // ALGORITHM InsertLargeScale(payment, HashSet, HashTable, PriorityHeap, AVLTree)
    public function insertLargeScale(Payment $payment): array {
        // IF HashSet.Contains(payment.ID) THEN
        if (isset($this->hashSet[$payment->ID])) {
            // RETURN "Error: Double Spending Prevented"
            return [
                'status' => 'error',
                'message' => 'Double Spending Prevented',
                'payment_id' => $payment->ID
            ];
        }
        
        // HashSet.Insert(payment.ID)
        $this->hashSet[$payment->ID] = true;
        
        // HashTable.Insert(payment.ID, payment)
        $this->hashTable[$payment->ID] = $payment;
        
        // PriorityHeap.Insert(payment.Value, payment)
        $this->maxHeap->insert($payment->Value, $payment);
        $this->minHeap->insert($payment->Value, $payment);
        
        // AVLTree.Insert(payment.Timestamp, payment)
        $this->avlTree->insert($payment->Timestamp, $payment);
        
        $this->transactionLog[] = [
            'action' => 'INSERT',
            'time' => time(),
            'payment' => $payment->toArray()
        ];
        
        // RETURN "Success: Synchronized to all indices"
        return [
            'status' => 'success',
            'message' => 'Synchronized to all indices',
            'payment' => $payment->toArray(),
            'indices' => ['HashSet', 'HashTable', 'PriorityHeap', 'AVLTree']
        ];
    }
    
    // ALGORITHM SearchLargeScale(paymentID, HashTable)
    public function searchLargeScale(string $paymentID): array {
        // target = HashTable.Lookup(paymentID)
        $target = $this->hashTable[$paymentID] ?? null;
        
        // IF target IS NOT NULL THEN
        if ($target !== null) {
            // RETURN target
            return [
                'status' => 'found',
                'payment' => $target->toArray(),
                'search_time' => 'O(1)'
            ];
        } else {
            // RETURN "Error: Not Found"
            return [
                'status' => 'error',
                'message' => 'Not Found',
                'payment_id' => $paymentID
            ];
        }
    }
    
    // End-of-Day Report using AVL Tree Range Query
    public function generateEODReport(int $startOfDay, int $endOfDay): array {
        $transactions = $this->avlTree->rangeQuery($startOfDay, $endOfDay);
        $totalValue = array_sum(array_column($transactions, 'Value'));
        $count = count($transactions);
        
        return [
            'report_type' => 'End-of-Day',
            'date_range' => [
                'start' => date('Y-m-d H:i:s', $startOfDay),
                'end' => date('Y-m-d H:i:s', $endOfDay)
            ],
            'summary' => [
                'total_transactions' => $count,
                'total_value' => number_format($totalValue, 2),
                'average_value' => $count > 0 ? number_format($totalValue / $count, 2) : '0.00'
            ],
            'top_transactions' => array_slice($this->maxHeap->getAll(), 0, 5),
            'transactions' => $transactions,
            'query_complexity' => 'O(log n + k) where k = results'
        ];
    }
    
    public function getSystemStats(): array {
        return [
            'hashSet_size' => count($this->hashSet),
            'hashTable_size' => count($this->hashTable),
            'avlTree_size' => $this->avlTree->getSize(),
            'maxHeap_size' => $this->maxHeap->getSize(),
            'total_logged' => count($this->transactionLog)
        ];
    }
    
    public function getAllTransactions(): array {
        return array_values(array_map(fn($p) => $p->toArray(), $this->hashTable));
    }
    
    public function getSessionData(): array {
        return [
            'hashSet' => $this->hashSet,
            'hashTable' => array_map(fn($p) => $p->toArray(), $this->hashTable)
        ];
    }
}

// --- API HANDLER ---

$system = new LargeScaleTransactionSystem($_SESSION['transaction_system'] ?? null);
$response = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'insert':
            $id = $_POST['id'] ?? 'TXN_' . uniqid();
            $value = floatval($_POST['value'] ?? 0);
            $timestamp = intval($_POST['timestamp'] ?? time());
            $metadata = [];
            if (!empty($_POST['metadata'])) {
                $metadata = json_decode($_POST['metadata'], true) ?: [];
            }
            $payment = new Payment($id, $value, $timestamp, $metadata);
            $response = $system->insertLargeScale($payment);
            break;
            
        case 'search':
            $response = $system->searchLargeScale($_POST['id'] ?? '');
            break;
            
        case 'eod_report':
            $date = $_POST['date'] ?? date('Y-m-d');
            $start = strtotime($date . ' 00:00:00');
            $end = strtotime($date . ' 23:59:59');
            $response = $system->generateEODReport($start, $end);
            break;
            
        case 'stats':
            $response = $system->getSystemStats();
            break;
            
        case 'clear':
            $_SESSION['transaction_system'] = [
                'hashSet' => [],
                'hashTable' => [],
                'payments' => [],
                'transactions' => [],
                'log' => []
            ];
            $response = ['status' => 'cleared'];
            break;
    }
    
    // Save session state
    $_SESSION['transaction_system'] = $system->getSessionData();
    
    if ($response) {
        echo json_encode($response);
        exit;
    }
}

// Get initial data for page render
$stats = $system->getSystemStats();
$allTransactions = $system->getAllTransactions();
$_SESSION['transaction_system'] = $system->getSessionData();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Large Scale Transaction System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
            min-height: 100vh;
            color: #e2e8f0;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px 20px;
            background: rgba(15, 23, 42, 0.7);
            border-radius: 16px;
            border: 1px solid rgba(94, 234, 212, 0.2);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        h1 {
            font-size: 2.8em;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #5eead4, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: none;
        }

        .subtitle {
            opacity: 0.8;
            font-size: 1.2em;
            color: #94a3b8;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 28px;
            border: 1px solid rgba(148, 163, 184, 0.15);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        }

        .card h2 {
            margin-bottom: 20px;
            font-size: 1.5em;
            color: #5eead4;
            border-bottom: 2px solid rgba(94, 234, 212, 0.3);
            padding-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.6);
            color: #e2e8f0;
            font-size: 1em;
            transition: all 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        input::placeholder {
            color: #64748b;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
            color: white;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        button.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        button.danger:hover {
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        button.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .result {
            margin-top: 18px;
            padding: 18px;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.6);
            border-left: 4px solid;
            display: none;
            animation: slideIn 0.4s ease;
        }

        .result.show {
            display: block;
        }

        .result.success {
            border-left-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .result.error {
            border-left-color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }

        .result.info {
            border-left-color: #38bdf8;
            background: rgba(56, 189, 248, 0.1);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .data-structure {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .structure-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .stat-item {
            background: rgba(15, 23, 42, 0.5);
            padding: 18px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .stat-value {
            font-size: 2.2em;
            font-weight: bold;
            color: #5eead4;
            text-shadow: 0 0 20px rgba(94, 234, 212, 0.3);
        }

        .stat-label {
            font-size: 0.8em;
            color: #94a3b8;
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .transaction-list {
            max-height: 350px;
            overflow-y: auto;
            margin-top: 18px;
        }

        .transaction-list::-webkit-scrollbar {
            width: 8px;
        }

        .transaction-list::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.3);
            border-radius: 4px;
        }

        .transaction-list::-webkit-scrollbar-thumb {
            background: rgba(94, 234, 212, 0.3);
            border-radius: 4px;
        }

        .transaction-item {
            background: rgba(15, 23, 42, 0.5);
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(148, 163, 184, 0.1);
            animation: slideIn 0.3s ease;
            transition: all 0.2s;
        }

        .transaction-item:hover {
            border-color: rgba(94, 234, 212, 0.3);
            background: rgba(15, 23, 42, 0.7);
        }

        .transaction-item .id {
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            color: #94a3b8;
        }

        .transaction-item .value {
            font-weight: bold;
            color: #5eead4;
            font-size: 1.1em;
        }

        .timestamp {
            font-size: 0.75em;
            color: #64748b;
            margin-top: 4px;
        }

        .algorithm-box {
            background: rgba(15, 23, 42, 0.8);
            padding: 20px;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 0.82em;
            line-height: 1.7;
            overflow-x: auto;
            margin-top: 18px;
            border: 1px solid rgba(148, 163, 184, 0.15);
        }

        .algorithm-box .keyword { color: #c678dd; font-weight: bold; }
        .algorithm-box .function { color: #61afef; }
        .algorithm-box .string { color: #98c379; }
        .algorithm-box .comment { color: #5c6370; font-style: italic; }
        .algorithm-box .type { color: #e5c07b; }

        .complexity-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            background: rgba(94, 234, 212, 0.15);
            color: #5eead4;
            font-size: 0.85em;
            font-weight: 600;
            margin-top: 12px;
            border: 1px solid rgba(94, 234, 212, 0.3);
        }

        .eod-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .summary-card {
            background: rgba(15, 23, 42, 0.5);
            padding: 22px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .summary-card h3 {
            font-size: 0.85em;
            color: #94a3b8;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .summary-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #fbbf24;
            text-shadow: 0 0 15px rgba(251, 191, 36, 0.3);
        }

        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        button.loading .spinner {
            display: inline-block;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 22px;
            border-radius: 25px;
            background: rgba(15, 23, 42, 0.5);
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(148, 163, 184, 0.2);
            font-weight: 600;
            font-size: 0.9em;
        }

        .tab.active {
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        .tab:hover:not(.active) {
            background: rgba(56, 189, 248, 0.2);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: slideIn 0.3s ease;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #e2e8f0;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75em;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-error { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-info { background: rgba(56, 189, 248, 0.2); color: #38bdf8; }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-group button {
            flex: 1;
        }

        .meta-display {
            font-size: 0.8em;
            color: #64748b;
            margin-top: 4px;
        }

        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
            .eod-summary { grid-template-columns: 1fr; }
            h1 { font-size: 2em; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        .empty-state svg {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔐 Large Scale Transaction System</h1>
            <p class="subtitle">HashSet • HashTable • PriorityHeap • AVL Tree — Synchronized Indices</p>
        </header>

        <div class="grid">
            <!-- INSERT CARD -->
            <div class="card">
                <h2>➕ Insert Transaction</h2>
                <p style="margin-bottom: 18px; color: #94a3b8; font-size: 0.95em;">
                    Algorithm: <code style="background: rgba(56,189,248,0.2); padding: 2px 8px; border-radius: 4px; color: #38bdf8;">InsertLargeScale()</code>
                </p>
                
                <form id="insertForm" onsubmit="return handleInsert(event)">
                    <div class="form-group">
                        <label>Transaction ID (Leave empty for auto-generation)</label>
                        <input type="text" id="paymentId" placeholder="TXN_12345">
                    </div>
                    <div class="form-group">
                        <label>Value ($) *</label>
                        <input type="number" id="paymentValue" step="0.01" min="0.01" placeholder="100.00" required>
                    </div>
                    <div class="form-group">
                        <label>Timestamp (Unix) — Leave empty for current time</label>
                        <input type="number" id="paymentTimestamp" placeholder="<?php echo time(); ?>">
                    </div>
                    <div class="form-group">
                        <label>Metadata (JSON format)</label>
                        <input type="text" id="paymentMeta" placeholder='{"merchant": "Amazon", "category": "electronics"}'>
                    </div>
                    <button type="submit" id="insertBtn">
                        Insert Payment <span class="spinner"></span>
                    </button>
                </form>

                <div id="insertResult" class="result"></div>

                <div class="algorithm-box">
<span class="comment">// ALGORITHM InsertLargeScale</span>
<span class="keyword">IF</span> <span class="function">HashSet.Contains</span>(payment.ID) <span class="keyword">THEN</span>
    <span class="keyword">RETURN</span> <span class="string">"Error: Double Spending Prevented"</span>
<span class="keyword">END IF</span>
<span class="function">HashSet.Insert</span>(payment.ID)
<span class="function">HashTable.Insert</span>(payment.ID, payment)
<span class="function">PriorityHeap.Insert</span>(payment.Value, payment)
<span class="function">AVLTree.Insert</span>(payment.Timestamp, payment)
<span class="keyword">RETURN</span> <span class="string">"Success: Synchronized to all indices"</span>
                </div>
            </div>

            <!-- SEARCH CARD -->
            <div class="card">
                <h2>🔍 Search Transaction</h2>
                <p style="margin-bottom: 18px; color: #94a3b8; font-size: 0.95em;">
                    Algorithm: <code style="background: rgba(56,189,248,0.2); padding: 2px 8px; border-radius: 4px; color: #38bdf8;">SearchLargeScale()</code> — O(1) Lookup
                </p>
                
                <form id="searchForm" onsubmit="return handleSearch(event)">
                    <div class="form-group">
                        <label>Payment ID *</label>
                        <input type="text" id="searchId" placeholder="Enter transaction ID" required>
                    </div>
                    <button type="submit" id="searchBtn">
                        Search <span class="spinner"></span>
                    </button>
                </form>

                <div id="searchResult" class="result"></div>

                <div class="algorithm-box">
<span class="comment">// ALGORITHM SearchLargeScale</span>
target = <span class="function">HashTable.Lookup</span>(paymentID)
<span class="keyword">IF</span> target <span class="keyword">IS NOT NULL THEN</span>
    <span class="keyword">RETURN</span> target
<span class="keyword">ELSE</span>
    <span class="keyword">RETURN</span> <span class="string">"Error: Not Found"</span>
<span class="keyword">END IF</span>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: rgba(15,23,42,0.5); border-radius: 10px; border: 1px solid rgba(148,163,184,0.1);">
                    <h4 style="color: #5eead4; margin-bottom: 10px; font-size: 0.95em;">Quick Search IDs:</h4>
                    <div id="quickSearchIds" style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <!-- SYSTEM ARCHITECTURE CARD -->
            <div class="card">
                <h2>⚙️ System Architecture</h2>
                <div class="data-structure">
                    <div class="structure-tag">🔵 HashSet — O(1)</div>
                    <div class="structure-tag">🟢 HashTable — O(1)</div>
                    <div class="structure-tag">🟡 PriorityHeap — O(log n)</div>
                    <div class="structure-tag">🔴 AVL Tree — O(log n)</div>
                </div>

                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value" id="statHashSet"><?php echo $stats['hashSet_size']; ?></div>
                        <div class="stat-label">HashSet</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="statHashTable"><?php echo $stats['hashTable_size']; ?></div>
                        <div class="stat-label">HashTable</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="statAVL"><?php echo $stats['avlTree_size']; ?></div>
                        <div class="stat-label">AVL Tree</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="statHeap"><?php echo $stats['maxHeap_size']; ?></div>
                        <div class="stat-label">Priority Heap</div>
                    </div>
                </div>

                <div class="btn-group">
                    <button onclick="refreshStats()" class="success">Refresh Stats</button>
                    <button onclick="clearSystem()" class="danger">Clear All</button>
                </div>
            </div>

            <!-- EOD REPORT CARD -->
            <div class="card" style="grid-column: 1 / -1;">
                <h2>📈 End-of-Day Report</h2>
                <p style="margin-bottom: 18px; color: #94a3b8; font-size: 0.95em;">
                    Uses AVL Tree Range Query for efficient timestamp filtering — <code style="background: rgba(56,189,248,0.2); padding: 2px 8px; border-radius: 4px; color: #38bdf8;">O(log n + k)</code>
                </p>
                
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('today')">Today</div>
                    <div class="tab" onclick="switchTab('yesterday')">Yesterday</div>
                    <div class="tab" onclick="switchTab('custom')">Custom Date</div>
                </div>

                <div id="todayTab" class="tab-content active">
                    <button onclick="generateEOD('today')" class="eod-btn">Generate Today's EOD Report</button>
                </div>
                <div id="yesterdayTab" class="tab-content">
                    <button onclick="generateEOD('yesterday')" class="eod-btn">Generate Yesterday's EOD Report</button>
                </div>
                <div id="customTab" class="tab-content">
                    <div class="form-group" style="max-width: 300px;">
                        <label>Select Date</label>
                        <input type="date" id="customDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button onclick="generateEOD('custom')" class="eod-btn">Generate Report</button>
                </div>

                <div id="eodResult" class="result"></div>
            </div>
        </div>

        <!-- TRANSACTIONS LIST -->
        <div class="card">
            <h2>📝 All Transactions (<?php echo count($allTransactions); ?>)</h2>
            <div id="transactionList" class="transaction-list">
                <?php if (empty($allTransactions)): ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p>No transactions yet. Insert a payment to see it here.</p>
                </div>
                <?php else: ?>
                <?php foreach (array_reverse($allTransactions) as $tx): ?>
                <div class="transaction-item" data-id="<?php echo htmlspecialchars($tx['ID']); ?>">
                    <div>
                        <div class="id"><?php echo htmlspecialchars($tx['ID']); ?></div>
                        <div class="timestamp"><?php echo htmlspecialchars($tx['Date']); ?></div>
                        <?php if (!empty($tx['Data'])): ?>
                        <div class="meta-display"><?php echo htmlspecialchars(json_encode($tx['Data'])); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="value">$<?php echo number_format($tx['Value'], 2); ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // --- AJAX HELPERS ---
        async function postData(action, data) {
            const formData = new FormData();
            formData.append('action', action);
            for (let key in data) formData.append(key, data[key]);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            return await response.json();
        }

        // --- INSERT HANDLER ---
        async function handleInsert(e) {
            e.preventDefault();
            const btn = document.getElementById('insertBtn');
            btn.classList.add('loading');
            
            const id = document.getElementById('paymentId').value || 'TXN_' + Date.now();
            const value = document.getElementById('paymentValue').value;
            const timestamp = document.getElementById('paymentTimestamp').value || Math.floor(Date.now() / 1000);
            const meta = document.getElementById('paymentMeta').value;
            
            const result = await postData('insert', {
                id: id,
                value: value,
                timestamp: timestamp,
                metadata: meta
            });
            
            displayResult('insertResult', result);
            
            if (result.status === 'success') {
                document.getElementById('insertForm').reset();
                await refreshStats();
                await loadTransactions();
                updateQuickSearchIds();
            }
            
            btn.classList.remove('loading');
            return false;
        }

        // --- SEARCH HANDLER ---
        async function handleSearch(e) {
            e.preventDefault();
            const btn = document.getElementById('searchBtn');
            btn.classList.add('loading');
            
            const id = document.getElementById('searchId').value;
            const result = await postData('search', { id: id });
            
            displayResult('searchResult', result);
            btn.classList.remove('loading');
            return false;
        }

        // --- DISPLAY RESULTS ---
        function displayResult(elementId, result) {
            const el = document.getElementById(elementId);
            el.className = 'result show ' + (result.status === 'error' ? 'error' : 'success');
            
            let html = '';
            
            if (result.status === 'success') {
                html += `<span class="badge badge-success">SUCCESS</span> <strong>${result.message}</strong><br><br>`;
                html += `<strong>Payment ID:</strong> ${result.payment.ID}<br>`;
                html += `<strong>Value:</strong> $${parseFloat(result.payment.Value).toFixed(2)}<br>`;
                html += `<strong>Timestamp:</strong> ${result.payment.Date}<br>`;
                if (result.payment.Data && Object.keys(result.payment.Data).length > 0) {
                    html += `<strong>Metadata:</strong> <code>${JSON.stringify(result.payment.Data)}</code><br>`;
                }
                html += `<div class="complexity-badge">✓ Synchronized: ${result.indices.join(' → ')}</div>`;
            } else if (result.status === 'found') {
                html += `<span class="badge badge-info">FOUND</span> <strong>O(1) HashTable Lookup</strong><br><br>`;
                html += `<strong>Payment ID:</strong> ${result.payment.ID}<br>`;
                html += `<strong>Value:</strong> $${parseFloat(result.payment.Value).toFixed(2)}<br>`;
                html += `<strong>Time:</strong> ${result.payment.Date}<br>`;
                if (result.payment.Data && Object.keys(result.payment.Data).length > 0) {
                    html += `<strong>Metadata:</strong> <code>${JSON.stringify(result.payment.Data)}</code>`;
                }
            } else {
                html += `<span class="badge badge-error">ERROR</span> <strong>${result.message}</strong><br>`;
                if (result.payment_id) html += `<strong>ID:</strong> ${result.payment_id}`;
            }
            
            el.innerHTML = html;
        }

        // --- REFRESH STATS ---
        async function refreshStats() {
            const result = await postData('stats', {});
            document.getElementById('statHashSet').textContent = result.hashSet_size;
            document.getElementById('statHashTable').textContent = result.hashTable_size;
            document.getElementById('statAVL').textContent = result.avlTree_size;
            document.getElementById('statHeap').textContent = result.maxHeap_size;
        }

        // --- CLEAR SYSTEM ---
        async function clearSystem() {
            if (!confirm('Are you sure you want to clear all transactions?')) return;
            await postData('clear', {});
            await refreshStats();
            await loadTransactions();
            document.getElementById('insertResult').className = 'result';
            document.getElementById('insertResult').innerHTML = '';
            document.getElementById('searchResult').className = 'result';
            document.getElementById('searchResult').innerHTML = '';
            document.getElementById('eodResult').className = 'result';
            document.getElementById('eodResult').innerHTML = '';
            updateQuickSearchIds();
        }

        // --- TAB SWITCHING ---
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById(tab + 'Tab').classList.add('active');
        }

        // --- EOD REPORT ---
        async function generateEOD(type) {
            const btn = event.target;
            btn.classList.add('loading');
            
            let date;
            const now = new Date();
            
            if (type === 'today') {
                date = now.toISOString().split('T')[0];
            } else if (type === 'yesterday') {
                const yest = new Date(now);
                yest.setDate(yest.getDate() - 1);
                date = yest.toISOString().split('T')[0];
            } else {
                date = document.getElementById('customDate').value;
                if (!date) {
                    alert('Please select a date');
                    btn.classList.remove('loading');
                    return;
                }
            }
            
            const result = await postData('eod_report', { date: date });
            displayEODReport(result);
            btn.classList.remove('loading');
        }

        function displayEODReport(report) {
            const el = document.getElementById('eodResult');
            el.className = 'result show info';
            
            let html = `
                <h3 style="margin-bottom: 15px; color: #fbbf24;">📊 ${report.report_type} Report</h3>
                <p style="color: #94a3b8; margin-bottom: 15px;">
                    ${report.date_range.start} → ${report.date_range.end}
                </p>
                <div class="eod-summary">
                    <div class="summary-card">
                        <h3>Transactions</h3>
                        <div class="number">${report.summary.total_transactions}</div>
                    </div>
                    <div class="summary-card">
                        <h3>Total Value</h3>
                        <div class="number">$${report.summary.total_value}</div>
                    </div>
                    <div class="summary-card">
                        <h3>Average</h3>
                        <div class="number">$${report.summary.average_value}</div>
                    </div>
                </div>
                <div class="complexity-badge">${report.query_complexity}</div>
            `;
            
            if (report.transactions.length > 0) {
                html += `<h4 style="margin-top: 20px; color: #5eead4;">Transactions:</h4><div class="transaction-list">`;
                html += report.transactions.map(tx => `
                    <div class="transaction-item">
                        <div>
                            <div class="id">${tx.ID}</div>
                            <div class="timestamp">${tx.Date}</div>
                        </div>
                        <div class="value">$${parseFloat(tx.Value).toFixed(2)}</div>
                    </div>
                `).join('');
                html += `</div>`;
            }
            
            el.innerHTML = html;
        }

        // --- LOAD TRANSACTIONS ---
        async function loadTransactions() {
            // Reload page to get fresh PHP-rendered list
            location.reload();
        }

        // --- QUICK SEARCH IDs ---
        function updateQuickSearchIds() {
            const items = document.querySelectorAll('.transaction-item');
            const container = document.getElementById('quickSearchIds');
            const ids = Array.from(items).slice(0, 8).map(item => item.dataset.id);
            
            if (ids.length === 0) {
                container.innerHTML = '<span style="color: #64748b; font-size: 0.85em;">No transactions available</span>';
                return;
            }
            
            container.innerHTML = ids.map(id => `
                <span class="structure-tag" style="cursor: pointer;" onclick="document.getElementById('searchId').value='${id}'; handleSearch({preventDefault:()=>{},target:document.getElementById('searchForm')})">
                    ${id.length > 20 ? id.substring(0, 17) + '...' : id}
                </span>
            `).join('');
        }

        // --- INITIALIZE ---
        document.addEventListener('DOMContentLoaded', () => {
            updateQuickSearchIds();
        });
    </script>
</body>
</html>