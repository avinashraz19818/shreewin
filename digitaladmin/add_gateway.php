
<?php
// admin/add_gateway.php
require_once 'api/conn.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        $_POST['payTypeID'],
        2, // payID
        $_POST['payName'],
        $_POST['paySysName'],
        $_POST['miniPrice'],
        $_POST['maxPrice'],
        $_POST['scope'],
        $_POST['paySendUrl'],
        '00:00', // startTime
        '24:00', // endTime
        0.00,    // rechargeRifts
        DB_selectOne("SELECT MAX(sort_order) + 10 AS next FROM payment_channels")['next'] ?? 10,
        isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Insert gateway
    $gatewayId = DB_insert("
        INSERT INTO payment_channels (
            payTypeID, payID, payName, paySysName, miniPrice, maxPrice, 
            scope, paySendUrl, startTime, endTime, rechargeRifts, sort_order, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ", $data);
    
    // Insert amounts
    foreach ($_POST['amounts'] as $amount) {
        if (!empty($amount['rechargeAmount'])) {
            DB_insert("
                INSERT INTO payment_channel_amounts (channel_id, rechargeAmount, giftAmount) 
                VALUES (?, ?, ?)
            ", [$gatewayId, $amount['rechargeAmount'], $amount['giftAmount'] ?? 0]);
        }
    }
    
    header("Location: payment_gateways.php?success=Gateway+added");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Payment Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Add New Payment Gateway</h2>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Gateway Name</label>
                        <input type="text" name="payName" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">System Name</label>
                        <input type="text" name="paySysName" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Pay Type ID</label>
                        <input type="number" name="payTypeID" class="form-control" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Minimum Amount</label>
                        <input type="number" name="miniPrice" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Maximum Amount</label>
                        <input type="number" name="maxPrice" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Scope (pipe separated)</label>
                        <input type="text" name="scope" class="form-control" value="200|500|1000|5000|10000|50000" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Payment Endpoint URL</label>
                <input type="text" name="paySendUrl" class="form-control" placeholder="/pay/yourgateway" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Quick Amounts</label>
                <div id="amounts-container">
                    <div class="row mb-2 amount-row">
                        <div class="col-md-5">
                            <input type="number" name="amounts[0][rechargeAmount]" class="form-control" placeholder="Amount" step="0.01" required>
                        </div>
                        <div class="col-md-5">
                            <input type="number" name="amounts[0][giftAmount]" class="form-control" placeholder="Bonus" step="0.01" value="0">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-amount">Remove</button>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-amount" class="btn btn-secondary">Add Amount</button>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Gateway</button>
            <a href="payment_gateways.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let amountCounter = 1;
        
        document.getElementById('add-amount').addEventListener('click', function() {
            const container = document.getElementById('amounts-container');
            const newRow = document.createElement('div');
            newRow.className = 'row mb-2 amount-row';
            newRow.innerHTML = `
                <div class="col-md-5">
                    <input type="number" name="amounts[${amountCounter}][rechargeAmount]" class="form-control" placeholder="Amount" step="0.01" required>
                </div>
                <div class="col-md-5">
                    <input type="number" name="amounts[${amountCounter}][giftAmount]" class="form-control" placeholder="Bonus" step="0.01" value="0">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-amount">Remove</button>
                </div>
            `;
            container.appendChild(newRow);
            amountCounter++;
        });
        
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-amount')) {
                e.target.closest('.amount-row').remove();
            }
        });
    </script>
</body>
</html>