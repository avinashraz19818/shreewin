<?php
// ============================================
// FILE: sync_transactions.php
// PURPOSE: Sync thevani and betting tables to user_transactions
// ============================================

include "../../conn.php";

if (!$conn) {
    die("Database connection failed");
}

echo "🔄 Starting transaction sync...\n\n";

// ========== SYNC DEPOSITS ==========
echo "📥 Syncing deposits from thevani...\n";

$depositSql = "INSERT INTO user_transactions (user_id, amount, transaction_type, remarks, created_at)
               SELECT balakedara, CAST(motta AS DECIMAL(10,2)), 'deposit', CONCAT('Deposit #', shonu), STR_TO_DATE(dinankavannuracisi, '%Y-%m-%d %H:%i:%s')
               FROM thevani 
               WHERE sthiti = 1 
               AND NOT EXISTS (
                   SELECT 1 FROM user_transactions 
                   WHERE user_transactions.user_id = thevani.balakedara 
                   AND user_transactions.amount = CAST(thevani.motta AS DECIMAL(10,2))
                   AND user_transactions.transaction_type = 'deposit'
                   AND DATE(user_transactions.created_at) = DATE(STR_TO_DATE(thevani.dinankavannuracisi, '%Y-%m-%d %H:%i:%s'))
               )";

$depositResult = $conn->query($depositSql);

if ($depositResult) {
    echo "✅ Deposits synced: " . $conn->affected_rows . " records added\n";
} else {
    echo "❌ Error syncing deposits: " . $conn->error . "\n";
}

// ========== SYNC BETS ==========
echo "\n📤 Syncing bets from bajikattuttate_zehn...\n";

// Check if bajikattuttate_zehn exists
$checkZehn = $conn->query("SHOW TABLES LIKE 'bajikattuttate_zehn'");
if ($checkZehn->num_rows > 0) {
    $betSql = "INSERT INTO user_transactions (user_id, amount, transaction_type, remarks, created_at)
               SELECT byabaharkarta, ketebida, 'bet', CONCAT('Bet from zehn - ', kalaparichaya), tiarikala
               FROM bajikattuttate_zehn 
               WHERE NOT EXISTS (
                   SELECT 1 FROM user_transactions 
                   WHERE user_transactions.user_id = bajikattuttate_zehn.byabaharkarta 
                   AND user_transactions.amount = bajikattuttate_zehn.ketebida 
                   AND user_transactions.transaction_type = 'bet'
                   AND DATE(user_transactions.created_at) = DATE(bajikattuttate_zehn.tiarikala)
               )";
    
    $betResult = $conn->query($betSql);
    
    if ($betResult) {
        echo "✅ Bets synced from zehn: " . $conn->affected_rows . " records added\n";
    } else {
        echo "❌ Error syncing bets: " . $conn->error . "\n";
    }
} else {
    echo "⚠️ Table bajikattuttate_zehn not found\n";
}

// ========== SHOW STATS FOR USER 1175722 ==========
echo "\n📊 User 1175722 statistics:\n";

$statsSql = "SELECT 
            COALESCE(SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END), 0) as total_deposit,
            COALESCE(SUM(CASE WHEN transaction_type = 'bet' THEN amount ELSE 0 END), 0) as total_bet,
            COUNT(*) as total_transactions
            FROM user_transactions 
            WHERE user_id = 1175722";

$statsResult = $conn->query($statsSql);
$statsRow = $statsResult->fetch_assoc();

echo "Total transactions: " . $statsRow['total_transactions'] . "\n";
echo "Total deposit (all time): ₹" . number_format($statsRow['total_deposit'], 2) . "\n";
echo "Total bet (all time): ₹" . number_format($statsRow['total_bet'], 2) . "\n";

// Last 24 hours
$dailySql = "SELECT 
            COALESCE(SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END), 0) as daily_deposit,
            COALESCE(SUM(CASE WHEN transaction_type = 'bet' THEN amount ELSE 0 END), 0) as daily_bet
            FROM user_transactions 
            WHERE user_id = 1175722 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";

$dailyResult = $conn->query($dailySql);
$dailyRow = $dailyResult->fetch_assoc();

echo "\nLast 24 hours:\n";
echo "Deposit: ₹" . number_format($dailyRow['daily_deposit'], 2) . "\n";
echo "Bet: ₹" . number_format($dailyRow['daily_bet'], 2) . "\n";

$conn->close();
?>