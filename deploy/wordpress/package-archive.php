<?php
/** Private packaging utility, never part of WordPress runtime. */
if (PHP_SAPI !== 'cli') { exit(1); }
$mode = $argv[1] ?? '';
$read = static fn($path) => json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
$fail = static function($message): never { fwrite(STDERR, $message.PHP_EOL); exit(1); };
$scan = static function(string $root) use ($fail): void {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $file) {
        $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root)+1));
        if ($file->isLink() || preg_match('~(?:^|/)(?:wp-config\.php|wp-cli\.ya?ml|\.env(?:\..*)?|\.git|\.svn|id_rsa|debug\.log)(?:$|/)~i', $path)
            || preg_match('/\.(?:sql|log|pem|key|bak)$/i', $path)
            || (str_starts_with($path, 'wp-content/uploads/') && preg_match('/\.(?:php[0-9]?|phtml|phar|zip|gz)$/i', $path))) { $fail('Unexpected sensitive file/link: '.$path); }
    }
    $htaccess = file_get_contents($root.'/.htaccess');
    if (preg_match('/AuthUserFile|^\s*SetEnv\s/im', $htaccess)) { $fail('Review environment/HTTP auth settings before packaging'); }
};
if ($mode === 'check-source') {
    $snapshot = $read($argv[2]);
    if ($snapshot['wordpress'] !== '7.1.1' || $snapshot['theme'] !== 'twentytwentyfive' || $snapshot['themeVersion'] !== '1.5'
        || $snapshot['prefix'] !== 'wp_' || $snapshot['sourceUrl'] !== 'http://31.129.98.28'
        || $snapshot['plugins'] !== ['carousel-block/plugin.php'=>'2.1.5', 'wp-seopress/seopress.php'=>'10.2']) { $fail('Unexpected source versions/settings; review before packaging'); }
    exit;
}
if ($mode === 'scan') { $scan(realpath($argv[2])); exit; }
if ($mode === 'compare') {
    $before = $read($argv[2]); $restored = $read($argv[3]); $after = $read($argv[4]);
    if ($before !== $after) { $fail('Source edited during snapshot: rebuild from a fresh snapshot'); }
    unset($before['privateUserAuthHash'], $restored['privateUserAuthHash']);
    if ($before !== $restored) { $fail('Restored content/settings do not match the source'); }
    echo "OK: restored content, identities, SEO and versions match; source unchanged.\n";
    exit;
}
if ($mode !== 'create') { $fail('Unknown mode'); }
$root = realpath($argv[2]);
if (!$root || !str_starts_with($root, '/opt/skysend-packages/')) { $fail('Unexpected package path'); }
$scan($root.'/site');
$sql = file_get_contents($root.'/database.sql');
// MariaDB's sandbox directive is a client control line, not WordPress data.
// Strip only that directive for compatibility with other MySQL/MariaDB clients.
$sql = preg_replace('/^\/\*M!999999\\\\- enable the sandbox mode \*\/\r?\n/m', '', $sql);
if (preg_match('/(?:^|\n)\s*(?:CREATE DATABASE|USE\s|GRANT\s|CREATE USER|CREATE\s+(?:DEFINER|TRIGGER|EVENT|PROCEDURE|FUNCTION)|DROP TABLE)/i', $sql) || preg_match('/\bDEFINER\s*=/i', $sql)) { $fail('Unexpected database/global/destructive statement in dump'); }
file_put_contents($root.'/database.sql', $sql);
$snapshot = $read($argv[3]); unset($snapshot['privateUserAuthHash']);
$manifest = [
    'createdAtUtc' => gmdate('c'), 'sourceGitCommit' => trim(shell_exec('git -C /opt/skysend rev-parse HEAD')),
    'type' => 'Functional WordPress migration, not Ubuntu/server image', 'snapshot' => $snapshot,
    'targetExample' => 'https://skysend.ru',
    'authentication' => 'All copied passwords randomized; sessions/application passwords/reset keys and source IndexNow key removed; IndexNow autosubmission disabled; target admin must set a new password. Source untouched.',
    'excluded' => ['wp-config.php','server/Ubuntu configurations','inactive themes/plugins','drop-ins/caches','Git','logs','private keys','source-private.sql','root credentials'],
    'restoredSqlContentVerified' => true, 'sourceUnchanged' => true,
    'blocksCheck' => $read($argv[4]), 'httpCheck' => $read($argv[5]),
];
file_put_contents($root.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
copy(__DIR__.'/verify-package.php', $root.'/verify-package.php');
$files = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->isFile()) { $relative = substr($file->getPathname(), strlen($root)+1); $files[$relative] = hash_file('sha256', $file->getPathname()); }
}
ksort($files, SORT_STRING);
$sums = '';
foreach ($files as $path => $hash) { $sums .= $hash.'  '.$path.PHP_EOL; }
file_put_contents($root.'/SHA256SUMS', $sums);
$files['SHA256SUMS'] = hash_file('sha256', $root.'/SHA256SUMS');
$name = basename($root);
$zipPath = dirname($root).'/'.$name.'.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) { $fail('Could not create new ZIP'); }
foreach ($files as $path => $hash) { if (!$zip->addFile($root.'/'.$path, $name.'/'.$path)) { $fail('ZIP add failed'); } }
if (!$zip->close()) { $fail('ZIP close failed'); }
if ($zip->open($zipPath, ZipArchive::CHECKCONS) !== true || $zip->numFiles !== count($files)) { $fail('ZIP integrity failed'); }
foreach ($files as $path => $hash) {
    $stream = $zip->getStream($name.'/'.$path);
    if (!$stream) { $fail('Missing ZIP member: '.$path); }
    $context = hash_init('sha256'); hash_update_stream($context, $stream); fclose($stream);
    if (hash_final($context) !== $hash) { $fail('ZIP member checksum mismatch: '.$path); }
}
$zip->close();
echo 'OK: ZIP verified, '.count($files).' files, '.filesize($zipPath).' bytes.'.PHP_EOL;
