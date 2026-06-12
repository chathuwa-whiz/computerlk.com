<?php
/**
 * POS Sync — shared bootstrap for all /api/pos HTTP endpoints.
 * Loads site config, auth, JSON helpers, and ensures the sync schema exists.
 */

require_once dirname(__DIR__, 2) . '/config.php';
require_once __DIR__ . '/hooks.php';

// Must match the secret configured in the POS (Settings → E-Commerce Website).
// Override by defining POS_SYNC_SECRET in config.php.
if (!defined('POS_SYNC_SECRET')) {
    define('POS_SYNC_SECRET', 'KODERNETPOS_ECOM_SYNC_2024');
}

// Endpoints return JSON only — discard any output buffered by config.php.
while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json');

function pos_json_input()
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function pos_respond($payload, $code = 200)
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function pos_require_auth(array $data)
{
    $secret = $data['secret'] ?? ($_GET['secret'] ?? '');
    if (!is_string($secret) || !hash_equals(POS_SYNC_SECRET, $secret)) {
        pos_respond(['success' => false, 'error' => 'Invalid sync secret'], 403);
    }
}

pos_ensure_schema($GLOBALS['conn']);
