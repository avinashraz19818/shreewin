<?php
declare(strict_types=1);

class SupportController
{
    public static function listTickets(array $params): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['draw' => 0, 'data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0];
        }

        try {
            $draw = (int)($params['draw'] ?? 1);
            $start = (int)($params['start'] ?? 0);
            $length = (int)($params['length'] ?? 50);
            if ($length <= 0 || $length > 500) {
                $length = 50;
            }
            $search = trim((string)($params['search']['value'] ?? ''));
            $category = trim((string)($params['category'] ?? ''));
            $statusFilter = trim((string)($params['status'] ?? ''));

            $conditions = [];
            $args = [];
            if ($search !== '') {
                $conditions[] = "(t.title LIKE ? OR u.username LIKE ? OR u.phone LIKE ? OR t.order_no LIKE ?)";
                $wild = "%$search%";
                $args = array_merge($args, [$wild, $wild, $wild, $wild]);
            }
            if ($statusFilter !== '') {
                $conditions[] = "t.status = ?";
                $args[] = $statusFilter;
            }
            $where = !empty($conditions) ? " WHERE " . implode(' AND ', $conditions) : "";

            $stmt = $pdo->query("SELECT COUNT(*) FROM support_tickets");
            $total = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM support_tickets t LEFT JOIN api_users u ON u.id = t.user_id" . $where);
            $stmt->execute($args);
            $filtered = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT t.*, u.username, u.nickname, u.phone
                FROM support_tickets t
                LEFT JOIN api_users u ON u.id = t.user_id
                " . $where . "
                ORDER BY t.updated_at DESC, t.id DESC
                LIMIT $length OFFSET $start
            ");
            $stmt->execute($args);
            $rows = $stmt->fetchAll() ?: [];

            // Category filtering happens in PHP: old rows have no category yet
            // and are mapped from their title so they still show up.
            if ($category !== '') {
                $rows = array_values(array_filter($rows, function ($row) use ($category) {
                    return api_ticket_category($row) === $category;
                }));
            }

            $data = [];
            foreach ($rows as $row) {
                $replies = 0;
                try {
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = ?");
                    $stmtCount->execute([(int)$row['id']]);
                    $replies = (int)$stmtCount->fetchColumn();
                } catch (Throwable $e) {
                }
                $data[] = [
                    'id' => (int)$row['id'],
                    'title' => htmlspecialchars((string)$row['title']),
                    'category' => api_ticket_category($row),
                    'username' => htmlspecialchars((string)($row['username'] ?? 'User #' . (int)$row['user_id'])),
                    'nickname' => htmlspecialchars((string)($row['nickname'] ?? '')),
                    'phone' => htmlspecialchars((string)($row['phone'] ?? '')),
                    'status' => (string)$row['status'],
                    'order_no' => htmlspecialchars((string)($row['order_no'] ?? '')),
                    'replies' => $replies,
                    'created_at' => (string)$row['created_at'],
                    'updated_at' => (string)$row['updated_at'],
                ];
            }

            return [
                'draw' => $draw,
                'data' => $data,
                'recordsTotal' => $total,
                'recordsFiltered' => $category !== '' ? count($data) : $filtered,
            ];
        } catch (Throwable $e) {
            return ['draw' => 0, 'error' => $e->getMessage(), 'data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0];
        }
    }

    /** Per category counters shown on top of each support tab. */
    public static function categoryCounts(): array
    {
        $pdo = api_pdo();
        $counts = [
            'support_deposit' => 0,
            'support_withdraw' => 0,
            'support_ifsc' => 0,
            'support_bank' => 0,
            'support_game' => 0,
        ];
        if (!$pdo) {
            return $counts;
        }
        try {
            $rows = $pdo->query("SELECT * FROM support_tickets")->fetchAll() ?: [];
        } catch (Throwable $e) {
            return $counts;
        }
        foreach ($rows as $row) {
            $category = api_ticket_category($row);
            if (!isset($counts[$category])) {
                $counts[$category] = 0;
            }
            $counts[$category]++;
        }
        return $counts;
    }

    /** Create a ticket from the admin panel (same table the site writes to). */
    public static function createTicket(array $post): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }

        $category = trim((string)($post['category'] ?? 'support_game'));
        $types = api_work_order_types();
        if (!isset($types[$category])) {
            $category = 'support_game';
        }
        $title = trim((string)($post['title'] ?? ''));
        if ($title === '') {
            $title = (string)($types[$category]['display_name'] ?? 'Support request');
        }
        $message = trim((string)($post['message'] ?? ''));
        $orderNo = trim((string)($post['order_no'] ?? ''));
        $userId = (int)($post['user_id'] ?? 0);

        try {
            if ($userId <= 0) {
                $stmt = $pdo->query("SELECT id FROM api_users ORDER BY id ASC LIMIT 1");
                $userId = (int)$stmt->fetchColumn();
            }
            if ($userId <= 0) {
                return ['success' => false, 'message' => 'No player found for this ticket'];
            }

            $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, title, category, status, order_no, created_at, updated_at) VALUES (?, ?, ?, 'open', ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([$userId, $title, $category, $orderNo !== '' ? $orderNo : null]);
            $ticketId = (int)$pdo->lastInsertId();

            if ($message !== '') {
                $pdo->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, sender_id, message) VALUES (?, 'user', ?, ?)")
                    ->execute([$ticketId, $userId, $message]);
            }

            return ['success' => true, 'message' => 'Ticket #' . $ticketId . ' created', 'ticket_id' => $ticketId];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function getTicketDetails(int $ticketId): array
    {
        $pdo = api_pdo();
        if (!$pdo) return [];

        try {
            $stmt = $pdo->prepare("
                SELECT t.*, u.username, u.nickname 
                FROM support_tickets t 
                LEFT JOIN api_users u ON u.id = t.user_id 
                WHERE t.id = ? LIMIT 1
            ");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            if (!$ticket) return [];

            $stmt = $pdo->prepare("SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY id ASC");
            $stmt->execute([$ticketId]);
            $replies = $stmt->fetchAll() ?: [];

            return [
                'ticket' => $ticket,
                'replies' => $replies
            ];
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function replyToTicket(int $ticketId, int $adminId, string $message): array
    {
        $pdo = api_pdo();
        if (!$pdo) return ['success' => false, 'message' => 'Database not available'];

        if (trim($message) === '') {
            return ['success' => false, 'message' => 'Message cannot be empty'];
        }

        try {
            $check = $pdo->prepare("SELECT id FROM support_tickets WHERE id = ? LIMIT 1");
            $check->execute([$ticketId]);
            if (!$check->fetchColumn()) {
                return ['success' => false, 'message' => 'Ticket not found'];
            }

            $pdo->beginTransaction();

            // Insert reply
            $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, sender_id, message) VALUES (?, 'admin', ?, ?)");
            $stmt->execute([$ticketId, $adminId, $message]);

            // Update ticket status
            $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'replied', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$ticketId]);

            $pdo->commit();
            return ['success' => true, 'message' => 'Reply sent successfully'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function closeTicket(int $ticketId): array
    {
        $pdo = api_pdo();
        if (!$pdo) return ['success' => false, 'message' => 'Database not available'];

        try {
            $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'closed', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$ticketId]);
            if ($stmt->rowCount() < 1) {
                return ['success' => false, 'message' => 'Ticket not found'];
            }
            return ['success' => true, 'message' => 'Ticket closed successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
