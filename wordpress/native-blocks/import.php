<?php
/** One-time WP-CLI import of ordinary core blocks. Not a theme or plugin. */
if (!defined('WP_CLI') || !WP_CLI) { exit(1); }
$mode = $args[0] ?? 'prepare';
$base = __DIR__;
$theme = 'twentytwentyfive';
$page_option = 'skysend_native_page_id';

// Root-run WP-CLI sideloads must remain readable by Apache and editable by WordPress.
function skysend_native_media_permissions(int $id): void {
    if (!function_exists('posix_geteuid') || posix_geteuid() !== 0) { return; }
    $account = posix_getpwnam('www-data');
    if (!$account) { WP_CLI::error('The Apache account www-data was not found'); }
    $uploads = wp_upload_dir();
    $base = realpath($uploads['basedir']);
    $file = get_attached_file($id);
    $metadata = wp_get_attachment_metadata($id);
    $files = [$file];
    foreach (($metadata['sizes'] ?? []) as $size) { $files[] = dirname($file).'/'.$size['file']; }
    if (!empty($metadata['original_image'])) { $files[] = dirname($file).'/'.$metadata['original_image']; }
    foreach ($files as $candidate) {
        $resolved = realpath($candidate);
        if (!$base || !$resolved || !str_starts_with($resolved,$base.DIRECTORY_SEPARATOR)) { WP_CLI::error('Media path is outside uploads'); }
        if (!chown($resolved,$account['uid']) || !chgrp($resolved,$account['gid']) || !chmod($resolved,0644)) { WP_CLI::error('Could not set media permissions'); }
        for ($directory=dirname($resolved); $directory!==$base; $directory=dirname($directory)) {
            if (!str_starts_with($directory,$base.DIRECTORY_SEPARATOR)) { WP_CLI::error('Invalid upload directory'); }
            if (!chown($directory,$account['uid']) || !chgrp($directory,$account['gid']) || !chmod($directory,0755)) { WP_CLI::error('Could not set upload directory permissions'); }
        }
    }
}

function skysend_native_put(string $type, string $slug, string $title, string $content, array $terms = []): int {
    $existing = get_page_by_path($slug, OBJECT, $type);
    $found = $existing ? [$existing] : [];
    $adopt_empty_styles = false;
    if ($type === 'wp_global_styles' && $existing && !get_post_meta($existing->ID,'_skysend_native_import',true)) {
        $json=json_decode($existing->post_content,true);
        if (is_array($json) && empty($json['styles']) && empty($json['settings'])) { update_post_meta($existing->ID,'_skysend_native_import',1); $adopt_empty_styles=true; }
    }
    if ($found && !get_post_meta($found[0]->ID,'_skysend_native_import',true)) {
        WP_CLI::error('Refusing to overwrite an existing editor-owned item: '.$slug);
    }
    if ($existing && !$adopt_empty_styles) {
        $previous_hash = get_post_meta($existing->ID,'_skysend_native_import_hash',true) ?: hash('sha256',$content);
        if (!hash_equals($previous_hash,hash('sha256',$existing->post_content))) { WP_CLI::error('Editor changes detected; refusing to reimport '.$slug); }
    }
    $id = wp_insert_post(wp_slash(['ID'=>$found?$found[0]->ID:0,'post_type'=>$type,'post_name'=>$slug,'post_title'=>$title,'post_status'=>$type==='page'?'draft':'publish','post_content'=>$content,'comment_status'=>'closed','ping_status'=>'closed']), true);
    if (is_wp_error($id)) { WP_CLI::error($id->get_error_message()); }
    update_post_meta($id,'_skysend_native_import',1);
    update_post_meta($id,'_skysend_native_import_hash',hash('sha256',$content));
    foreach ($terms as $taxonomy=>$term) { wp_set_object_terms($id,$term,$taxonomy); }
    return $id;
}

function skysend_native_validate(string $text): void {
    $queue=parse_blocks($text);
    while ($queue) {
        $block=array_pop($queue);
        if (!$block['blockName']) { if (trim($block['innerHTML'])) { WP_CLI::error('Unstructured HTML'); } continue; }
        if (!str_starts_with($block['blockName'],'core/') || in_array($block['blockName'],['core/html','core/freeform','core/code','core/shortcode'],true)) { WP_CLI::error('Non-native content'); }
        if (!WP_Block_Type_Registry::get_instance()->is_registered($block['blockName'])) { WP_CLI::error('Unregistered '.$block['blockName']); }
        array_push($queue,...$block['innerBlocks']);
    }
}

if ($mode === 'media') {
    if (get_option('skysend_native_migration_complete')) { WP_CLI::error('Migration already published. Manage images in the Media Library.'); }
    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/media.php';
    require_once ABSPATH.'wp-admin/includes/image.php';
    $names=['skysend-logo.png','allvend-logo.png','favicon.png','banner-providers-20260914.webp','banner-finance-20260914.webp','banner-allvend-20260914.webp','partner-agents.jpg','partner-providers.jpg','partner-suppliers.jpg','partner-retail.jpg','partner-representatives.jpg','partner-gateways-no-xml-20260916.webp'];
    foreach (glob(WP_CONTENT_DIR.'/themes/skysend/assets/images/providers/*.png') as $file) { $names[]='providers/'.basename($file); }
    $map=[];
    foreach ($names as $name) {
        $found=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'meta_key'=>'_skysend_native_source','meta_value'=>$name]);
        $id=$found?$found[0]->ID:0;
        if (!$id) {
            $source=WP_CONTENT_DIR.'/themes/skysend/assets/images/'.$name;
            if (!is_file($source)) { WP_CLI::error('Missing source image '.$name); }
            $tmp=wp_tempnam(basename($name));
            if (!$tmp || !copy($source,$tmp)) { WP_CLI::error('Could not stage '.$name); }
            $id=media_handle_sideload(['name'=>basename($name),'tmp_name'=>$tmp],0,pathinfo($name,PATHINFO_FILENAME));
            if(is_wp_error($id)){WP_CLI::error($id->get_error_message());}
            update_post_meta($id,'_skysend_native_source',$name);
        }
        skysend_native_media_permissions((int)$id);
        $map[$name]=['id'=>$id,'url'=>wp_get_attachment_url($id)];
    }
    file_put_contents($base.'/media.json',wp_json_encode($map,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    WP_CLI::success('Media library: '.count($map).' attachments');
    return;
}

if ($mode === 'prepare') {
    if (get_option('skysend_native_migration_complete')) { WP_CLI::error('Migration already published. Edit in WordPress; do not reimport over editor changes.'); }
    foreach (['page','header','footer','template'] as $name) {
        $text=file_get_contents($base.'/'.$name.'.html');
        skysend_native_validate($text);
    }
    $page=skysend_native_put('page','skysend-landing','SkySend — система приёма платежей',file_get_contents($base.'/page.html'));
    update_option($page_option,$page,false);
    update_post_meta($page,'_wp_page_template','default');
    foreach(['header'=>'Шапка','footer'=>'Футер'] as $slug=>$label){
        skysend_native_put('wp_template_part',$slug,'SkySend — '.$label,file_get_contents($base.'/'.$slug.'.html'),['wp_theme'=>$theme,'wp_template_part_area'=>$slug]);
    }
    skysend_native_put('wp_template','front-page','SkySend — Лендинг',file_get_contents($base.'/template.html'),['wp_theme'=>$theme]);
    skysend_native_put('wp_global_styles','wp-global-styles-'.$theme,'Стили SkySend',file_get_contents($base.'/styles.json'),['wp_theme'=>$theme]);
    WP_CLI::success('Draft page '.$page.' ready; current published theme unchanged');
    return;
}

if ($mode === 'activate') {
    if(get_option('skysend_native_migration_complete')){WP_CLI::error('Already active; reimport prohibited');}
    $page=(int)get_option($page_option);
    if(!$page || !has_blocks(get_post_field('post_content',$page))){WP_CLI::error('Prepare the native page first');}
    if (get_post_type($page)!=='page' || wp_get_theme($theme)->errors()) { WP_CLI::error('Missing page or official theme'); }
    skysend_native_validate(get_post_field('post_content',$page));
    foreach (['wp_template_part'=>['header','footer'],'wp_template'=>['front-page']] as $type=>$slugs) {
        foreach ($slugs as $slug) {
            $item=get_page_by_path($slug,OBJECT,$type);
            if (!$item || !has_term($theme,'wp_theme',$item->ID)) { WP_CLI::error('Missing native template '.$slug); }
            skysend_native_validate($item->post_content);
        }
    }
    // Validate the selected block template in the target theme, not the legacy one.
    $previous_theme=get_stylesheet();
    switch_theme($theme);
    update_post_meta($page,'_wp_page_template','default');
    $published=wp_update_post(['ID'=>$page,'post_status'=>'publish'],true);
    if (is_wp_error($published) || get_post_status($page)!=='publish') {
        switch_theme($previous_theme);
        WP_CLI::error(is_wp_error($published)?$published->get_error_message():'Could not publish the native page');
    }
    update_option('show_on_front','page');
    update_option('page_on_front',$page);
    update_option('page_for_posts',0);
    if (get_stylesheet()!==$theme || (int)get_option('page_on_front')!==$page) { WP_CLI::error('Theme activation did not complete'); }
    $logo=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'meta_key'=>'_skysend_native_source','meta_value'=>'skysend-logo.png']);
    if($logo)set_theme_mod('custom_logo',$logo[0]->ID);
    $icon=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'meta_key'=>'_skysend_native_source','meta_value'=>'favicon.png']);
    if($icon)update_option('site_icon',$icon[0]->ID);
    update_option('skysend_native_migration_complete',gmdate('c'),false);
    flush_rewrite_rules(false);
    WP_CLI::success('Native WordPress landing activated, page '.$page);
    return;
}
WP_CLI::error('Unknown mode');
