<?php
/**
 * POS → Website: product catalog upsert.
 *
 * POST JSON:
 * {
 *   "secret": "...",
 *   "create_missing": true,
 *   "products": [
 *     { "pos_id": 12, "sku": "HDMI-10M", "name": "HDMI Cable 10M",
 *       "price": 2500, "stock": 14, "active": 1, "category": "Cables" }
 *   ]
 * }
 *
 * Only the fields PRESENT in each item are written. A stock-only push
 * ({pos_id, sku, name, stock}) never touches price or name. Website-managed
 * fields (image, description, slug, badge, old_price, weight, rating) are
 * NEVER overwritten by the POS.
 *
 * Response: { success, updated, created, skipped: [names], errors: [..] }
 */

require_once __DIR__ . '/_bootstrap.php';

$data = pos_json_input();
pos_require_auth($data);

$items = $data['products'] ?? [];
if (!is_array($items) || !count($items)) {
    pos_respond(['success' => false, 'error' => 'No products in payload'], 400);
}
$createMissing = !empty($data['create_missing']);

$updated = 0;
$created = 0;
$skipped = [];
$errors  = [];

foreach ($items as $item) {
    if (!is_array($item)) continue;
    $name = trim((string)($item['name'] ?? ''));
    $label = $name !== '' ? $name : ('pos#' . ($item['pos_id'] ?? '?'));

    try {
        $row = pos_match_product($conn, $item);

        if ($row) {
            // Build a dynamic UPDATE from only the fields present in the payload.
            $sets = [];
            $types = '';
            $vals = [];

            if ($name !== '' && $name !== $row['name']) { $sets[] = 'name = ?'; $types .= 's'; $vals[] = $name; }
            if (array_key_exists('price', $item)) { $sets[] = 'price = ?'; $types .= 'd'; $vals[] = (float)$item['price']; }
            if (array_key_exists('stock', $item)) { $sets[] = 'stock = ?'; $types .= 'i'; $vals[] = max(0, (int)$item['stock']); }
            if (array_key_exists('active', $item)) {
                $sets[] = 'status = ?'; $types .= 's'; $vals[] = ((int)$item['active']) ? 'active' : 'inactive';
            }
            if (!empty($item['sku']) && trim((string)$item['sku']) !== (string)($row['sku'] ?? '')) {
                $sets[] = 'sku = ?'; $types .= 's'; $vals[] = trim((string)$item['sku']);
            }
            // Category: only assign when the website product has none yet, so a
            // manually curated website category is never overwritten.
            if (!empty($item['category']) && empty($row['category_id'])) {
                $catId = pos_category_id($conn, $item['category']);
                if ($catId) { $sets[] = 'category_id = ?'; $types .= 'i'; $vals[] = $catId; }
            }

            if ($sets) {
                $types .= 'i';
                $vals[] = (int)$row['id'];
                $stmt = $conn->prepare("UPDATE products SET " . implode(', ', $sets) . " WHERE id = ?");
                $stmt->bind_param($types, ...$vals);
                $stmt->execute();
            }
            $updated++;
        } elseif ($createMissing && $name !== '') {
            $posId    = isset($item['pos_id']) ? (int)$item['pos_id'] : null;
            $sku      = trim((string)($item['sku'] ?? ''));
            $price    = (float)($item['price'] ?? 0);
            $stock    = max(0, (int)($item['stock'] ?? 0));
            $status   = (!isset($item['active']) || (int)$item['active']) ? 'active' : 'inactive';
            $catId    = !empty($item['category']) ? pos_category_id($conn, $item['category']) : null;
            $slug     = pos_unique_slug($conn, 'products', $name);

            $stmt = $conn->prepare(
                "INSERT INTO products (pos_id, sku, category_id, name, slug, description, price, stock, status)
                 VALUES (?, ?, ?, ?, ?, '', ?, ?, ?)"
            );
            $stmt->bind_param('isissdis', $posId, $sku, $catId, $name, $slug, $price, $stock, $status);
            $stmt->execute();
            $created++;
        } else {
            $skipped[] = $label;
        }
    } catch (Throwable $e) {
        $errors[] = $label . ': ' . $e->getMessage();
    }
}

pos_respond([
    'success' => true,
    'updated' => $updated,
    'created' => $created,
    'skipped' => $skipped,
    'errors'  => $errors
]);
