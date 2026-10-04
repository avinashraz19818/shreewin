<?php
declare(strict_types=1);

class SettingsController
{
    public static function saveSetting(string $key, string $value): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }

        try {
            $driver = api_db_driver($pdo);
            if ($driver === 'mysql') {
                $stmt = $pdo->prepare("INSERT INTO api_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
                $stmt->execute([$key, $value]);
            } else {
                $exists = $pdo->prepare("SELECT id FROM api_settings WHERE setting_key = ? LIMIT 1");
                $exists->execute([$key]);
                if ($exists->fetchColumn()) {
                    $stmt = $pdo->prepare("UPDATE api_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO api_settings (setting_key, setting_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
                    $stmt->execute([$key, $value]);
                }
            }
            return ['success' => true, 'message' => "Setting '$key' saved successfully"];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function savePaymentMethod(array $post): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }

        $id = (int)($post['id'] ?? 0);
        $name = trim((string)($post['method_name'] ?? 'UPI'));
        $type = trim((string)($post['method_type'] ?? 'UPI'));
        $accountName = trim((string)($post['account_name'] ?? ''));
        $accountValue = trim((string)($post['account_value'] ?? ''));
        $min = (float)($post['min_amount'] ?? 100);
        $max = (float)($post['max_amount'] ?? 50000);
        $sort = (int)($post['sort_order'] ?? 0);
        $enabled = isset($post['enabled']) ? (int)$post['enabled'] : 1;
        $qrText = trim((string)($post['qr_text'] ?? ''));

        if ($name === '' || $accountValue === '') {
            return ['success' => false, 'message' => 'Method name and Account value are required'];
        }

        if ($qrText === '' && strtoupper($type) === 'UPI') {
            $qrText = 'upi://pay?pa=' . rawurlencode($accountValue) . '&pn=' . rawurlencode($accountName) . '&cu=INR';
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE payment_methods SET method_name = ?, method_type = ?, account_name = ?, account_value = ?, qr_text = ?, min_amount = ?, max_amount = ?, sort_order = ?, enabled = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$name, $type, $accountName, $accountValue, $qrText, $min, $max, $sort, $enabled, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO payment_methods (method_name, method_type, account_name, account_value, qr_text, min_amount, max_amount, sort_order, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $type, $accountName, $accountValue, $qrText, $min, $max, $sort, $enabled]);
            }
            return ['success' => true, 'message' => 'UPI method saved successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function saveUsdtMethod(array $post): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }

        $id = (int)($post['id'] ?? 0);
        $name = trim((string)($post['wallet_name'] ?? 'USDT'));
        $address = trim((string)($post['wallet_address'] ?? ''));
        $network = trim((string)($post['network'] ?? 'TRC20'));
        $min = (float)($post['min_amount'] ?? 10);
        $max = (float)($post['max_amount'] ?? 10000);
        $sort = (int)($post['sort_order'] ?? 0);
        $enabled = isset($post['enabled']) ? (int)$post['enabled'] : 1;
        $qrText = trim((string)($post['qr_text'] ?? ''));

        if ($name === '' || $address === '') {
            return ['success' => false, 'message' => 'Wallet name and address are required'];
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE usdt_methods SET wallet_name = ?, wallet_address = ?, network = ?, qr_text = ?, min_amount = ?, max_amount = ?, sort_order = ?, enabled = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$name, $address, $network, $qrText, $min, $max, $sort, $enabled, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO usdt_methods (wallet_name, wallet_address, network, qr_text, min_amount, max_amount, sort_order, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $address, $network, $qrText, $min, $max, $sort, $enabled]);
            }
            return ['success' => true, 'message' => 'USDT method saved successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Upload (or link) a QR / gateway image for a UPI or USDT method and store
     * it in the rows the site's deposit page reads.
     */
    public static function saveGatewayImage(string $table, int $id, array $post, array $files = []): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }
        if (!in_array($table, ['payment_methods', 'usdt_methods'], true)) {
            return ['success' => false, 'message' => 'Invalid gateway table'];
        }

        $imageUrl = trim((string)($post['image_url'] ?? ''));
        $imageData = (string)($post['image_data'] ?? '');

        // 1) Real file upload (multipart form from the panel)
        if (!empty($files['image']['tmp_name']) && is_uploaded_file((string)$files['image']['tmp_name'])) {
            $original = (string)($files['image']['name'] ?? 'qr.png');
            $extension = strtolower((string)pathinfo($original, PATHINFO_EXTENSION));
            if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
                $extension = 'png';
            }
            $uploadDir = dirname(__DIR__, 2) . '/uploads/gateways';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $fileName = $table . '-' . $id . '-' . substr(md5((string)microtime(true)), 0, 8) . '.' . $extension;
            $target = $uploadDir . '/' . $fileName;
            if (!@move_uploaded_file((string)$files['image']['tmp_name'], $target)) {
                return ['success' => false, 'message' => 'Could not store the uploaded image (check folder permissions on /uploads)'];
            }
            @chmod($target, 0644);
            $imageUrl = '/uploads/gateways/' . $fileName;
        } elseif ($imageData !== '' && preg_match('#^data:image/[a-z]+;base64,#i', $imageData)) {
            // 2) Base64 image pasted into the panel
            $parts = explode(',', $imageData, 2);
            $binary = base64_decode((string)($parts[1] ?? ''), true);
            if ($binary === false) {
                return ['success' => false, 'message' => 'Invalid base64 image data'];
            }
            $uploadDir = dirname(__DIR__, 2) . '/uploads/gateways';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $fileName = $table . '-' . $id . '-' . substr(md5((string)microtime(true)), 0, 8) . '.png';
            if (@file_put_contents($uploadDir . '/' . $fileName, $binary) === false) {
                return ['success' => false, 'message' => 'Could not store the pasted image'];
            }
            $imageUrl = '/uploads/gateways/' . $fileName;
        }

        if ($imageUrl === '') {
            return ['success' => false, 'message' => 'Choose an image file or paste an image URL first'];
        }

        try {
            $stmt = $pdo->prepare("UPDATE `$table` SET qr_image = ?, icon_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$imageUrl, $imageUrl, $id]);
            if ($stmt->rowCount() < 1) {
                // Row may not have an updated_at column on very old installs.
                $stmt = $pdo->prepare("UPDATE `$table` SET qr_image = ?, icon_url = ? WHERE id = ?");
                $stmt->execute([$imageUrl, $imageUrl, $id]);
            }
            return ['success' => true, 'message' => 'Gateway image saved', 'image_url' => $imageUrl];
        } catch (Throwable $e) {
            // Retry without updated_at so the image still gets stored.
            try {
                $stmt = $pdo->prepare("UPDATE `$table` SET qr_image = ?, icon_url = ? WHERE id = ?");
                $stmt->execute([$imageUrl, $imageUrl, $id]);
                return ['success' => true, 'message' => 'Gateway image saved', 'image_url' => $imageUrl];
            } catch (Throwable $e2) {
                return ['success' => false, 'message' => 'Error: ' . $e2->getMessage()];
            }
        }
    }

    public static function getPaymentMethods(): array
    {
        $pdo = api_pdo();
        if (!$pdo) return [];
        try {
            return $pdo->query("SELECT * FROM payment_methods ORDER BY sort_order DESC, id ASC")->fetchAll() ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function getUsdtMethods(): array
    {
        $pdo = api_pdo();
        if (!$pdo) return [];
        try {
            return $pdo->query("SELECT * FROM usdt_methods ORDER BY sort_order DESC, id ASC")->fetchAll() ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function deletePaymentMethod(int $id): array
    {
        $pdo = api_pdo();
        if (!$pdo) return ['success' => false, 'message' => 'Database not available'];
        try {
            $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'UPI method deleted successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function deleteUsdtMethod(int $id): array
    {
        $pdo = api_pdo();
        if (!$pdo) return ['success' => false, 'message' => 'Database not available'];
        try {
            $stmt = $pdo->prepare("DELETE FROM usdt_methods WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'USDT method deleted successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
