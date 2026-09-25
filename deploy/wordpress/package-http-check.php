<?php
/** Anonymous HTTP smoke test of the restored clone, on loopback only. */
if (PHP_SAPI !== 'cli') { exit(1); }
$base = 'http://127.0.0.1:58081';
$site = realpath($argv[1] ?? '');
if (!$site || !is_file($site.'/wp-content/mu-plugins/skysend-provider-catalog/catalog.json')) {
    fwrite(STDERR, "Expected restored clone path with provider catalogue\n");
    exit(1);
}
$catalog = json_decode((string) file_get_contents($site.'/wp-content/mu-plugins/skysend-provider-catalog/catalog.json'), true);
$categories = is_array($catalog['categories'] ?? null) ? $catalog['categories'] : [];
$categoryLabels = array_map(static fn($category) => is_array($category) ? ($category['label'] ?? '') : '', $categories);
$providerCount = array_sum(array_map(static fn($category) => is_array($category['items'] ?? null) ? count($category['items']) : 0, $categories));
$checks = [];
$request = static function($path) use ($base): array {
    $curl = curl_init($base.$path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>15]);
    $body = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
    return [$status, $body];
};
[$status, $body] = $request('/');
$body = is_string($body) ? $body : '';
$hasImage = static function(string $html, string $filename, string $altFragment): bool {
    preg_match_all('~<img\b[^>]*>~iu', $html, $tags);
    foreach ($tags[0] as $tag) {
        if (!preg_match('~\bsrc=["\']([^"\']+)["\']~iu', $tag, $src)
            || !preg_match('~\balt=["\']([^"\']*)["\']~iu', $tag, $alt)) { continue; }
        $path = parse_url(html_entity_decode($src[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), PHP_URL_PATH);
        if (basename((string) $path) === $filename
            && str_contains(html_entity_decode($alt[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $altFragment)) { return true; }
    }
    return false;
};
$checks['Homepage 200'] = $status === 200;
$checks['One H1'] = preg_match_all('/<h1\b/i', $body) === 1;
$checks['Title/description'] = str_contains($body, '<title>SkySend — система приёма платежей</title>') && preg_match('/<meta name="description" content="SkySend/', $body) === 1;
$checks['Canonical rewritten'] = str_contains($body, '<link rel="canonical" href="'.$base.'/">');
$checks['JSON-LD/OG'] = str_contains($body, '"@type":"Organization"') && str_contains($body, '<meta property="og:image"');
$checks['Carousel and native tabs scripts'] = str_contains($body, 'carousel-block/build/carousel/view.js') && str_contains($body, 'block-library/tabs/view.min.js');
$checks['Finance banner graphic with income'] = $hasImage($body, 'banner-finance-transparent-20260925.png', 'Доход +20%');
$checks['Gateway XML graphic'] = $hasImage($body, 'partner-gateways-xml-20260924.webp', 'XML-шлюза');
$checks['FastSYS copy'] = str_contains($body, 'обеспечивает стабильную работу устройств на протяжении десятилетий.');
$checks['Obsolete disclaimer absent'] = !str_contains($body, 'Архивные материалы: условия и контакты');
$checks['All 15 provider categories visible'] = count($categories) === 15 && $providerCount === 5000
    && !array_filter($categoryLabels, static fn($label) => !is_string($label) || $label === '' || !str_contains($body, $label));
preg_match_all('~<button\b(?=[^>]*\brole=["\']tab["\'])[^>]*>(.*?)</button>~siu', $body, $tabMatches);
$visibleTabLabels = array_map(static fn($html) => trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')), $tabMatches[1]);
$checks['Provider tab labels contain no counts'] = $visibleTabLabels === array_values($categoryLabels);
preg_match_all('~\bclass=["\'][^"\']*\bsky-provider-catalog\b[^"\']*["\']~i', $body, $catalogShells);
$checks['All 15 provider catalogue shells rendered'] = count($catalogShells[0]) === 15;
$checks['Provider catalogue assets enqueued'] = str_contains($body, '/mu-plugins/skysend-provider-catalog/catalog.js')
    && str_contains($body, '/mu-plugins/skysend-provider-catalog/catalog.css');
foreach (['catalog.js', 'catalog.css'] as $filename) {
    [$assetStatus] = $request('/wp-content/mu-plugins/skysend-provider-catalog/'.$filename);
    $checks['Provider '.$filename.' HTTP 200'] = $assetStatus === 200;
}
$sampleLogos = [];
$providerApiValid = count($categories) === 15;
foreach ($categories as $slug => $category) {
    $entries = $category['items'] ?? [];
    if (!is_array($entries) || !$entries) { $providerApiValid = false; continue; }
    $sampleLogos[$slug] = $entries[0]['logo'] ?? '';
    $pages = (int) ceil(count($entries) / 9);
    [$firstStatus, $firstBody] = $request('/wp-json/skysend/v1/providers?category='.rawurlencode($slug).'&page=1');
    $first = is_string($firstBody) ? json_decode($firstBody, true) : null;
    if ($firstStatus !== 200 || !is_array($first) || !is_array($first['items'] ?? null)
        || ($first['category'] ?? null) !== $slug
        || ($first['page'] ?? null) !== 1 || ($first['total'] ?? null) !== count($entries)
        || ($first['pages'] ?? null) !== $pages || count($first['items'] ?? []) !== min(9, count($entries))
        || ($first['items'][0] ?? null) !== $entries[0]) {
        $providerApiValid = false;
    }
    if ($pages > 1) {
        [$lastStatus, $lastBody] = $request('/wp-json/skysend/v1/providers?category='.rawurlencode($slug).'&page='.$pages);
        $last = is_string($lastBody) ? json_decode($lastBody, true) : null;
        $lastItems = is_array($last['items'] ?? null) ? $last['items'] : [];
        if ($lastStatus !== 200 || !is_array($last) || ($last['category'] ?? null) !== $slug
            || ($last['page'] ?? null) !== $pages || ($last['total'] ?? null) !== count($entries)
            || ($last['pages'] ?? null) !== $pages || count($lastItems) !== count($entries) - 9 * ($pages - 1)
            || (!$lastItems || $lastItems[count($lastItems) - 1] !== $entries[count($entries) - 1])) {
            $providerApiValid = false;
        }
    }
}
$checks['Provider API serves nine per page in all categories'] = $providerApiValid;
if ($categories) {
    $lastCategory = end($categories);
    $lastEntries = $lastCategory['items'] ?? [];
    $sampleLogos['last-record'] = is_array($lastEntries) && $lastEntries ? (end($lastEntries)['logo'] ?? '') : '';
}
$logoHttpValid = count($sampleLogos) === 16;
foreach ($sampleLogos as $logoPath) {
    if (!is_string($logoPath) || !preg_match('~^/wp-content/uploads/skysend-providers-20260925/[0-9]{4}\.webp$~', $logoPath)) {
        $logoHttpValid = false;
        continue;
    }
    [$logoStatus, $logoBody] = $request($logoPath);
    if ($logoStatus !== 200 || !is_string($logoBody)
        || substr($logoBody, 0, 4) !== 'RIFF' || substr($logoBody, 8, 4) !== 'WEBP') {
        $logoHttpValid = false;
    }
}
$checks['Provider logos HTTP 200 in all categories'] = $logoHttpValid;
$checks['New brand and feature icons'] = !str_contains($body, '/skysend-logo.png')
    && str_contains($body, '/pos-terminal-20260924c.png')
    && str_contains($body, '/gear-20260924c.png');
$brandImages = [];
preg_match_all('~<img\b(?=[^>]*\balt=["\']SkySend["\'])[^>]*\bsrc=["\']([^"\']+)~i', $body, $brandMatches);
foreach ($brandMatches[1] as $imageUrl) { $brandImages[] = html_entity_decode($imageUrl); }
$checks['Distinct header and footer logos'] = count($brandImages) >= 2
    && count(array_unique($brandImages)) >= 2
    && !array_filter($brandImages, static fn($url) => !str_starts_with($url, $base.'/wp-content/uploads/'));
$siteIcon = null;
if (preg_match('~<link\b[^>]*rel=["\']icon["\'][^>]*href=["\']([^"\']+)~i', $body, $iconMatch)) {
    $siteIcon = html_entity_decode($iconMatch[1]);
}
$checks['Site icon is a new local asset'] = is_string($siteIcon)
    && str_starts_with($siteIcon, $base.'/wp-content/uploads/')
    && !str_contains($siteIcon, 'site-icon-20260924.png');
if ($checks['Site icon is a new local asset']) {
    [$iconStatus] = $request(substr($siteIcon, strlen($base)));
    $checks['Site icon HTTP 200'] = $iconStatus === 200;
}
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
