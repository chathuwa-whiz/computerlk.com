<?php
/**
 * Connection test for the POS (Settings → E-Commerce Website → Test Connection).
 * GET ?secret=... → counts and linkage stats.
 */

require_once __DIR__ . '/_bootstrap.php';

pos_require_auth(pos_json_input());

$products = (int)($conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'] ?? 0);
$linked   = (int)($conn->query("SELECT COUNT(*) c FROM products WHERE pos_id IS NOT NULL")->fetch_assoc()['c'] ?? 0);
$pending  = (int)($conn->query("SELECT COUNT(*) c FROM pos_stock_events WHERE applied = 0")->fetch_assoc()['c'] ?? 0);

pos_respond([
    'success'        => true,
    'site'           => SITE_NAME,
    'products'       => $products,
    'linked'         => $linked,
    'pending_events' => $pending,
    'server_time'    => date('c')
]);
