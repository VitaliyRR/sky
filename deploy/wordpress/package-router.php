<?php
/** Loopback-only temporary HTTP verifier, never included in the delivery site. */
if (PHP_SAPI !== 'cli-server' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit; }
$root = realpath($_SERVER['DOCUMENT_ROOT']);
if (!$root || !str_starts_with($root, '/opt/skysend-packages/')) { http_response_code(403); exit; }
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($root.'/'.$path);
if ($file && str_starts_with($file, $root.'/') && is_file($file)) { return false; }
require $root.'/index.php';
