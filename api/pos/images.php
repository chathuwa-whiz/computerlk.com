<?php
/**
 * Website → POS: product image URLs.
 *
 * Images are uploaded and stored ONLY on the website (uploads/products/).
 * The POS stores just the absolute URL and renders it directly, so there is
 * a single storage location for product images.
 *
 * GET ?secret=... →
 *   { success, images: [ { pos_id, sku, name, image_url } ] }
 */

require_once __DIR__ . '/_bootstrap.php';

pos_require_auth([]);

$base = rtrim(SITE_URL, '/') . '/uploads/products/';
$images = [];

$res = $conn->query(
    "SELECT pos_id, sku, name, image FROM products WHERE image IS NOT NULL AND image != ''"
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $images[] = [
            'pos_id'    => $row['pos_id'] !== null ? (int)$row['pos_id'] : null,
            'sku'       => $row['sku'],
            'name'      => $row['name'],
            'image_url' => $base . rawurlencode($row['image'])
        ];
    }
}

pos_respond(['success' => true, 'images' => $images]);
