<?php
/** Read-only integrity check. Run outside webroot: php verify-package.php */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = realpath(__DIR__);
$lines = file(__DIR__.'/SHA256SUMS', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$checked = 0;
foreach ($lines as $line) {
    if (!preg_match('/^([a-f0-9]{64})  (.+)$/', $line, $match)) { fwrite(STDERR, "Invalid checksum line\n"); exit(1); }
    $path = realpath($root.'/'.$match[2]);
    if (!$path || !str_starts_with($path, $root.'/') || !is_file($path) || is_link($root.'/'.$match[2]) || hash_file('sha256', $path) !== $match[1]) {
        fwrite(STDERR, 'Checksum/path mismatch: '.$match[2].PHP_EOL); exit(1);
    }
    $checked++;
}
echo 'OK: '.$checked.' files verified.'.PHP_EOL;
