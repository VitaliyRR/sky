param([string]$BaseUrl = 'http://31.129.98.28')
$ErrorActionPreference = 'Stop'
$BaseUrl = $BaseUrl.TrimEnd('/')
$profile = Get-Content -Raw -LiteralPath "$PSScriptRoot/../../wordpress/native-blocks/seo-settings.json" | ConvertFrom-Json
$handler = [System.Net.Http.HttpClientHandler]::new()
$handler.AllowAutoRedirect = $false
$client = [System.Net.Http.HttpClient]::new($handler)
$client.Timeout = [TimeSpan]::FromSeconds(25)
$checks = [System.Collections.Generic.List[object]]::new()
function Check([string]$Name, [bool]$Passed) { $checks.Add([pscustomobject]@{name=$Name;passed=$Passed}) }
function Request([string]$Path, [string]$Method = 'GET') {
    $uri = if ($Path -match '^https?://') { $Path } else { "$BaseUrl$Path" }
    $message = [System.Net.Http.HttpRequestMessage]::new([System.Net.Http.HttpMethod]::new($Method), $uri)
    $response = $client.SendAsync($message).GetAwaiter().GetResult()
    try {
        [pscustomobject]@{
            status = [int]$response.StatusCode
            type = [string]$response.Content.Headers.ContentType
            location = [string]$response.Headers.Location
            robots = if ($response.Headers.Contains('X-Robots-Tag')) { $response.Headers.GetValues('X-Robots-Tag') -join ', ' } else { '' }
            body = if ($Method -eq 'HEAD') { '' } else { $response.Content.ReadAsStringAsync().GetAwaiter().GetResult() }
        }
    } finally { $response.Dispose(); $message.Dispose() }
}
try {
    $homepage = Request '/'
    Check 'Homepage HTTP 200' ($homepage.status -eq 200)
    Check 'Homepage X-Robots-Tag does not prohibit indexing' ($homepage.robots -notmatch 'noindex|none')
    Check 'Russian language' ($homepage.body -match '<html\b[^>]*lang=["'']ru-RU["'']')
    Check 'One H1' ([regex]::Matches($homepage.body, '<h1\b').Count -eq 1)
    $head = [regex]::Match($homepage.body, '(?s)<head\b.*?</head>').Value
    $title = [regex]::Matches($head, '(?s)<title\b[^>]*>(.*?)</title>')
    Check 'One accurate title' ($title.Count -eq 1 -and [System.Net.WebUtility]::HtmlDecode($title[0].Groups[1].Value) -ceq $profile.title)
    $meta = @{}
    foreach ($tag in [regex]::Matches($head, '<meta\b[^>]*>')) {
        $key = [regex]::Match($tag.Value, '(?:name|property)=["'']([^"'']+)["'']').Groups[1].Value
        $value = [System.Net.WebUtility]::HtmlDecode([regex]::Match($tag.Value, 'content=["'']([^"'']*)["'']').Groups[1].Value)
        if ($key) {
            if (-not $meta.ContainsKey($key)) { $meta[$key] = @() }
            $meta[$key] += $value
        }
    }
    foreach ($key in @('description','robots','og:url','og:site_name','og:locale','og:type','og:title','og:description','og:image','og:image:width','og:image:height','twitter:card','twitter:title','twitter:description','twitter:image','twitter:site')) {
        Check "One nonempty $key" ($meta[$key].Count -eq 1 -and [bool]$meta[$key][0])
    }
    Check 'Confirmed description' ($meta['description'][0] -ceq $profile.description)
    Check 'Open Graph title/description' ($meta['og:title'][0] -ceq $profile.title -and $meta['og:description'][0] -ceq $profile.description)
    Check 'X title/description' ($meta['twitter:title'][0] -ceq $profile.title -and $meta['twitter:description'][0] -ceq $profile.description)
    Check 'Indexable homepage robots' ($meta['robots'][0] -match '\bindex\b' -and $meta['robots'][0] -notmatch 'noindex|nofollow|\bnone\b')
    $canonical = [regex]::Matches($head, '<link\b[^>]*rel=["'']canonical["''][^>]*>')
    $canonicalUrl = if ($canonical.Count) { [regex]::Match($canonical[0].Value, 'href=["'']([^"'']+)["'']').Groups[1].Value } else { '' }
    Check 'One canonical for current host' ($canonical.Count -eq 1 -and $canonicalUrl -ceq "$BaseUrl/")
    Check 'OG URL matches canonical' ($meta['og:url'][0] -ceq $canonicalUrl)
    Check 'Large X Card and valid handle' ($meta['twitter:card'][0] -ceq 'summary_large_image' -and $meta['twitter:site'][0] -ceq '@infsysgroup')
    Check 'Same real image for OG and X' ($meta['og:image'][0] -ceq $meta['twitter:image'][0] -and $meta['og:image'][0].EndsWith('/'+$profile.socialImageFile))
    Check 'Social image available' ((Request $meta['og:image'][0] 'HEAD').status -eq 200)
    Check 'Adequate social image dimensions' ([int]$meta['og:image:width'][0] -ge 1200 -and [int]$meta['og:image:height'][0] -ge 630)
    $schemas = @([regex]::Matches($head, '(?s)<script\b[^>]*type=["'']application/ld\+json["''][^>]*>(.*?)</script>') | ForEach-Object { $_.Groups[1].Value | ConvertFrom-Json })
    $organizations = @($schemas | Where-Object { $_.'@type' -eq 'Organization' })
    Check 'One Organization JSON-LD' ($organizations.Count -eq 1)
    Check 'One WebSite JSON-LD' (@($schemas | Where-Object { $_.'@type' -eq 'WebSite' }).Count -eq 1)
    $organization = $organizations[0]
    Check 'Correct organization name/URL' ($organization.name -ceq 'SkySend' -and $organization.url.TrimEnd('/') -ceq $BaseUrl)
    Check 'Confirmed organization contacts' ($organization.contactPoint[0].telephone -ceq $profile.organization.phone -and $organization.contactPoint[0].email -ceq $profile.organization.email)
    Check 'No office address or invented organization metrics' (-not $organization.address -and -not $organization.foundingDate -and -not $organization.numberOfEmployees -and -not $organization.vatID)
    Check 'Organization logo available' ((Request $organization.logo 'HEAD').status -eq 200)
    Check 'No malformed social URLs' (@($organization.sameAs | Where-Object { $_ -match 'https?://.+https?://' -or $_ -notmatch '^https://[^/]+/.+' }).Count -eq 0)
    Check 'Correct X profile in JSON-LD' ('https://x.com/infsysgroup' -cin $organization.sameAs)
    $robots = Request '/robots.txt'
    Check 'Robots HTTP 200, plain text' ($robots.status -eq 200 -and $robots.type -match '^text/plain')
    Check 'Robots advertises working native sitemap' ($robots.body -match [regex]::Escape("Sitemap: $BaseUrl/wp-sitemap.xml"))
    Check 'Robots does not block whole site' ($robots.body -notmatch '(?m)^Disallow:\s*/\s*$')
    $index = Request '/wp-sitemap.xml'
    Check 'Native sitemap HTTP 200/XML' ($index.status -eq 200 -and $index.type -match '^application/xml')
    [xml]$indexXml = $index.body
    $sitemapUrls = @($indexXml.SelectNodes('//*[local-name()="sitemap"]/*[local-name()="loc"]') | ForEach-Object { $_.InnerText })
    Check 'Native sitemap contains only published page map' ($sitemapUrls.Count -eq 1 -and $sitemapUrls[0] -ceq "$BaseUrl/wp-sitemap-posts-page-1.xml")
    $pages = Request '/wp-sitemap-posts-page-1.xml'
    Check 'Native page map HTTP 200/XML' ($pages.status -eq 200 -and $pages.type -match '^application/xml')
    [xml]$pageXml = $pages.body
    $pageUrls = @($pageXml.SelectNodes('//*[local-name()="url"]/*[local-name()="loc"]') | ForEach-Object { $_.InnerText })
    Check 'Only real homepage in sitemap, no drafts or removed pages' ($pageUrls.Count -eq 1 -and $pageUrls[0] -ceq "$BaseUrl/")
    foreach ($path in @('/wp-sitemap.xsl','/wp-sitemap-index.xsl')) { Check "$path HTTP 200" ((Request $path).status -eq 200) }
    $oldMap = Request '/wp-sitemap-skysend-1.xml'
    Check 'Old sitemap 301 to native page map' ($oldMap.status -eq 301 -and $oldMap.location -ceq "$BaseUrl/wp-sitemap-posts-page-1.xml")
    foreach ($partner in @('agents','providers','suppliers','retailers','representatives','gateways','advertisers')) {
        foreach ($slash in @('', '/')) {
            $path = "/participants/$partner$slash"
            $oldPage = Request $path
            Check "$path 301 to participants" ($oldPage.status -eq 301 -and $oldPage.location -ceq "$BaseUrl/#participants")
        }
    }
    $missing = Request '/seo-audit-missing-20260918'
    Check 'Unknown path remains real HTTP 404, noindex' ($missing.status -eq 404 -and $missing.body -match '<meta\b[^>]*name=["'']robots["''][^>]*content=["''][^"'']*noindex')
    $search = Request '/?s=seo-audit-20260918'
    Check 'Search results noindex' ($search.body -match '<meta\b[^>]*name=["'']robots["''][^>]*content=["''][^"'']*noindex')
    $assetUrls = @([regex]::Matches($homepage.body, '<(?:img|script)\b[^>]*src=["'']([^"'']+)["'']') | ForEach-Object { [System.Net.WebUtility]::HtmlDecode($_.Groups[1].Value) } | Where-Object { $_.StartsWith("$BaseUrl/") } | Sort-Object -Unique)
    foreach ($asset in $assetUrls) { Check "Local asset HTTP 200: $([uri]$asset | ForEach-Object AbsolutePath)" ((Request $asset 'HEAD').status -eq 200) }
    $result = [ordered]@{
        date = Get-Date -Format 'yyyy-MM-dd'
        baseUrl = $BaseUrl
        checkType = 'Anonymous HTTP/source audit, not interactive browser testing'
        passed = @($checks | Where-Object passed).Count
        failed = @($checks | Where-Object { -not $_.passed }).Count
        localAssets = $assetUrls.Count
        title = $profile.title
        description = $profile.description
        descriptionCharacters = $profile.description.Length
        checks = $checks.ToArray()
    }
    $result | ConvertTo-Json -Depth 8
    if ($result.failed) { exit 1 }
} finally { $client.Dispose(); $handler.Dispose() }
