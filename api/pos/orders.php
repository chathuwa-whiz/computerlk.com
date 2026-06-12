<?php
/**
 * Website → POS: stock event queue.
 *
 * GET  ?secret=...   → unapplied stock events with product identity:
 *   { success, events: [ { id, pos_id, sku, name, qty_delta, order_number, reason, created_at } ] }
 *   qty_delta is signed from the POS perspective:
 *     online sale       → -qty  (POS must deduct)
 *     order cancelled   → +qty  (POS must restock)
 *
 * POST { secret, applied_ids: [..] } → acknowledge processed events.
 */

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    pos_require_auth([]);

    $events = [];
    $res = $conn->query(
        "SELECT e.id, e.qty_delta, e.order_number, e.reason, e.created_at,
                p.pos_id, p.sku, p.name
         FROM pos_stock_events e
         JOIN products p ON p.id = e.product_id
         WHERE e.applied = 0
         ORDER BY e.id ASC
         LIMIT 200"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $events[] = [
                'id'           => (int)$row['id'],
                'pos_id'       => $row['pos_id'] !== null ? (int)$row['pos_id'] : null,
                'sku'          => $row['sku'],
                'name'         => $row['name'],
                'qty_delta'    => (int)$row['qty_delta'],
                'order_number' => $row['order_number'],
                'reason'       => $row['reason'],
                'created_at'   => $row['created_at']
            ];
        }
    }
    pos_respond(['success' => true, 'events' => $events]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = pos_json_input();
    pos_require_auth($data);

    $ids = array_values(array_filter(array_map('intval', $data['applied_ids'] ?? []), fn($v) => $v > 0));
    if (!count($ids)) {
        pos_respond(['success' => true, 'acknowledged' => 0]);
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("UPDATE pos_stock_events SET applied = 1, applied_at = NOW() WHERE id IN ($placeholders) AND applied = 0");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();

    pos_respond(['success' => true, 'acknowledged' => $stmt->affected_rows]);
}

pos_respond(['success' => false, 'error' => 'Method not allowed'], 405);
