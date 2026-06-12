<?php
/**
 * POS Sync — internal hooks, schema bootstrap and product matching.
 *
 * This file contains NO HTTP handling and produces NO output. It is included
 * by the /api/pos/* endpoints and by checkout.php / admin order_view.php.
 * Every function takes the mysqli connection explicitly.
 *
 * Data-flow design:
 *   POS → Website : catalog.php receives absolute product data (price/stock/
 *                   name/active). Absolute values are idempotent — a retried
 *                   push can never double-apply, and every push heals drift.
 *   Website → POS : stock movements are queued in pos_stock_events with a
 *                   signed qty_delta (online sale = -qty, cancellation = +qty).
 *                   The POS polls orders.php, applies the deltas, acknowledges
 *                   them, then pushes its absolute stock back to equalise.
 *
 * Product identity: products.pos_id (the product's id in the POS SQLite DB)
 * is authoritative. Fallback matching: pos_id → sku → exact name. Whenever a
 * fallback match succeeds the pos_id/sku are backfilled, so the link becomes
 * id-based automatically ("self-healing").
 */

function pos_ensure_schema(mysqli $conn)
{
    static $done = false;
    if ($done) return;
    $done = true;

    if (!pos_column_exists($conn, 'products', 'pos_id')) {
        $conn->query("ALTER TABLE products ADD COLUMN pos_id INT NULL, ADD UNIQUE KEY uniq_pos_id (pos_id)");
    }
    if (!pos_column_exists($conn, 'products', 'sku')) {
        $conn->query("ALTER TABLE products ADD COLUMN sku VARCHAR(100) NULL, ADD KEY idx_sku (sku)");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS pos_stock_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        qty_delta INT NOT NULL,
        order_id INT NULL,
        order_number VARCHAR(50),
        reason VARCHAR(30) DEFAULT 'online_sale',
        applied TINYINT(1) DEFAULT 0,
        applied_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_applied (applied),
        KEY idx_order (order_id)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

function pos_column_exists(mysqli $conn, $table, $column)
{
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '" . $conn->real_escape_string($column) . "'");
    return $res && $res->num_rows > 0;
}

/**
 * Find the website product for a POS payload row.
 * Precedence: pos_id → sku → exact name (case/whitespace-insensitive).
 * Backfills pos_id (and sku, if provided) on fallback matches.
 * Returns the product row as assoc array, or null.
 */
function pos_match_product(mysqli $conn, array $item)
{
    $posId = isset($item['pos_id']) ? (int)$item['pos_id'] : 0;
    $sku   = trim((string)($item['sku'] ?? ''));
    $name  = trim((string)($item['name'] ?? ''));

    if ($posId > 0) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE pos_id = ? LIMIT 1");
        $stmt->bind_param('i', $posId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) return $row;
    }

    $row = null;
    if ($sku !== '') {
        $stmt = $conn->prepare("SELECT * FROM products WHERE sku = ? AND sku != '' LIMIT 1");
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
    }
    if (!$row && $name !== '') {
        $stmt = $conn->prepare("SELECT * FROM products WHERE LOWER(TRIM(name)) = LOWER(?) LIMIT 1");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
    }

    if ($row && $posId > 0 && (int)($row['pos_id'] ?? 0) !== $posId) {
        $skuToSet = $sku !== '' ? $sku : (string)($row['sku'] ?? '');
        $id = (int)$row['id'];
        $stmt = $conn->prepare("UPDATE products SET pos_id = ?, sku = ? WHERE id = ?");
        $stmt->bind_param('isi', $posId, $skuToSet, $id);
        $stmt->execute();
        $row['pos_id'] = $posId;
        $row['sku'] = $skuToSet;
    }
    return $row ?: null;
}

/** Find-or-create a website category by name. Returns category id or null. */
function pos_category_id(mysqli $conn, $name)
{
    $name = trim((string)$name);
    if ($name === '') return null;

    $stmt = $conn->prepare("SELECT id FROM categories WHERE LOWER(TRIM(name)) = LOWER(?) LIMIT 1");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) return (int)$row['id'];

    $slug = pos_unique_slug($conn, 'categories', $name);
    $stmt = $conn->prepare("INSERT INTO categories (name, slug, status) VALUES (?, ?, 'active')");
    $stmt->bind_param('ss', $name, $slug);
    $stmt->execute();
    return (int)$conn->insert_id;
}

/** Generate a unique URL slug for the given table (categories or products). */
function pos_unique_slug(mysqli $conn, $table, $name, $excludeId = 0)
{
    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
    if ($base === '') $base = 'item';
    $slug = $base;
    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM `$table` WHERE slug = ? AND id != ?");
        $stmt->bind_param('si', $slug, $excludeId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) return $slug;
        $slug = $base . '-' . $counter;
        $counter++;
    }
}

/**
 * Hook: called from checkout.php right after the order + order_items insert.
 * Reserves website stock immediately (prevents overselling) and queues
 * deduction events for the POS.
 */
function pos_sync_order_placed(mysqli $conn, $order_id)
{
    pos_ensure_schema($conn);
    $order_id = (int)$order_id;
    $res = $conn->query("SELECT order_number FROM orders WHERE id = $order_id");
    $order_number = ($res && ($o = $res->fetch_assoc())) ? $o['order_number'] : '';

    $items = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
    if (!$items) return;

    $ins = $conn->prepare(
        "INSERT INTO pos_stock_events (product_id, qty_delta, order_id, order_number, reason) VALUES (?,?,?,?, 'online_sale')"
    );
    while ($it = $items->fetch_assoc()) {
        $pid = (int)$it['product_id'];
        $qty = (int)$it['quantity'];
        if ($pid < 1 || $qty < 1) continue;
        $conn->query("UPDATE products SET stock = GREATEST(0, stock - $qty) WHERE id = $pid");
        $delta = -$qty;
        $ins->bind_param('iiis', $pid, $delta, $order_id, $order_number);
        $ins->execute();
    }
}

/**
 * Hook: called from admin order_view.php after a status change.
 * Cancellation restocks the website and queues restock events for the POS
 * (or simply voids the original sale event if the POS never consumed it).
 * Re-instating a cancelled order behaves like a fresh placement.
 */
function pos_sync_order_status_changed(mysqli $conn, $order_id, $old_status, $new_status)
{
    pos_ensure_schema($conn);
    if ($old_status === $new_status) return;
    $order_id = (int)$order_id;

    if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
        $res = $conn->query("SELECT order_number FROM orders WHERE id = $order_id");
        $order_number = ($res && ($o = $res->fetch_assoc())) ? $o['order_number'] : '';

        $items = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
        if (!$items) return;

        $ins = $conn->prepare(
            "INSERT INTO pos_stock_events (product_id, qty_delta, order_id, order_number, reason) VALUES (?,?,?,?, 'order_cancelled')"
        );
        while ($it = $items->fetch_assoc()) {
            $pid = (int)$it['product_id'];
            $qty = (int)$it['quantity'];
            if ($pid < 1 || $qty < 1) continue;
            $conn->query("UPDATE products SET stock = stock + $qty WHERE id = $pid");

            $pending = $conn->query(
                "SELECT id FROM pos_stock_events
                 WHERE order_id = $order_id AND product_id = $pid AND reason = 'online_sale' AND applied = 0
                 LIMIT 1"
            );
            if ($pending && ($p = $pending->fetch_assoc())) {
                // POS never saw the sale — void it instead of emitting a counter-event
                $conn->query("UPDATE pos_stock_events SET applied = 1, applied_at = NOW(), reason = 'voided' WHERE id = " . (int)$p['id']);
            } else {
                $ins->bind_param('iiis', $pid, $qty, $order_id, $order_number);
                $ins->execute();
            }
        }
    } elseif ($old_status === 'cancelled' && $new_status !== 'cancelled') {
        pos_sync_order_placed($conn, $order_id);
    }
}
