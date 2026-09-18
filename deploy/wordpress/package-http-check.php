<?php
/** Anonymous HTTP smoke test of the restored clone, on loopback only. */
if (PHP_SAPI !== 'cli') { exit(1); }
$base = 'http://127.0.0.1:58081';
$checks = [];
$request = static function($path) use ($base): array {
    $curl = curl_init($base.$path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>15]);
    $body = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
    return [$status, $body];
};
[$status, $body] = $request('/');
$checks['Homepage 200'] = $status === 200;
$checks['One H1'] = preg_match_all('/<h1\b/i', $body) === 1;
$checks['Title/description'] = str_contains($body, '<title>SkySend — система приёма платежей</title>') && preg_match('/<meta name="description" content="SkySend/', $body) === 1;
$checks['Canonical rewritten'] = str_contains($body, '<link rel="canonical" href="'.$base.'/">');
$checks['JSON-LD/OG'] = str_contains($body, '"@type":"Organization"') && str_contains($body, '<meta property="og:image"');
$checks['Carousel and native tabs scripts'] = str_contains($body, 'carousel-block/build/carousel/view.js') && str_contains($body, 'block-library/tabs/view.min.js');
foreach (['/robots.txt','/wp-sitemap.xml','/wp-sitemap-posts-page-1.xml','/wp-sitemap.xsl','/wp-sitemap-index.xsl'] as $path) {
    [$status, $response] = $request($path); $checks[$path.' HTTP 200'] = $status === 200;
    if (str_ends_with($path, '.xml')) { $checks[$path.' valid XML'] = (bool) simplexml_load_string($response); }
}
[$status, $missing] = $request('/package-check-missing'); $checks['Real 404'] = $status === 404;
$checks['All local images/scripts'] = true; $assets = [];
preg_match_all('~<(?:img|script)\b[^>]*src=["\']([^"\']+)["\']~i', $body, $matches);
foreach (array_unique($matches[1]) as $url) {
    $url = html_entity_decode($url);
    if (str_starts_with($url, $base.'/')) {
        $assets[] = $url; [$assetStatus] = $request(substr($url, strlen($base)));
        if ($assetStatus !== 200) { $checks['All local images/scripts'] = false; }
    }
}
echo json_encode(['type'=>'Loopback HTTP/source smoke test, not interactive browser testing', 'localAssets'=>count($assets), 'checks'=>$checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
if (in_array(false, $checks, true)) { exit(1); }
