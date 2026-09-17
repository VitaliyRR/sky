/** One-time update of native Providers layout; does not run on the website. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

export async function providersLayout(w, input, output) {
  if (!input || !output) throw new Error('Usage: build.mjs --providers-layout current-page.xml output-directory');
  const xml = new w.DOMParser().parseFromString(fs.readFileSync(input, 'utf8'), 'text/xml');
  if (xml.querySelector('parsererror')) throw new Error('Invalid WXR');
  const wpns = 'http://wordpress.org/export/1.2/';
  const contentns = 'http://purl.org/rss/1.0/modules/content/';
  const item = [...xml.querySelectorAll('item')].find(x => x.getElementsByTagNameNS(wpns, 'post_id')[0]?.textContent === '67');
  if (!item || item.getElementsByTagNameNS(wpns, 'post_type')[0]?.textContent !== 'page') throw new Error('Missing current page 67');
  const original = item.getElementsByTagNameNS(contentns, 'encoded')[0]?.textContent;
  const blocks = w.wp.blocks.parse(original);
  const walk = (tree, fn) => tree.forEach(block => { fn(block); walk(block.innerBlocks, fn); });
  const merge = (a, b) => {
    const result = { ...a };
    for (const [key, value] of Object.entries(b)) result[key] = value && typeof value === 'object' && !Array.isArray(value) ? merge(a?.[key] || {}, value) : value;
    return result;
  };
  const set = (block, value) => { block.attributes = merge(block.attributes, value); };
  const providers = blocks.find(block => block.attributes.anchor === 'providers');
  if (!providers) throw new Error('Missing Providers');
  const unchanged = blocks.filter(block => block !== providers).map(block => w.wp.blocks.serialize([block]));
  const providerBefore = content(providers);
  let tabs;
  walk([providers], block => { if (block.name === 'core/tabs') { if (tabs) throw new Error('Multiple provider tabs'); tabs = block; } });
  if (!tabs || tabs.innerBlocks.length !== 2 || tabs.innerBlocks[0].name !== 'core/tab-list' || tabs.innerBlocks[1].name !== 'core/tab-panels') throw new Error('Unexpected native Tabs structure');
  const [list, panels] = tabs.innerBlocks;
  if (list.attributes.tabs.length !== 6 || panels.innerBlocks.length !== 6) throw new Error('Expected six categories');
  // Keep required direct children: the core editor uses them to synchronize tabs.
  set(tabs, { activeTabIndex: 0, metadata: { name: 'Провайдеры — категории слева, логотипы справа' }, style: { spacing: { blockGap: '32px' } } });
  tabs.attributes.layout = { type: 'grid', minimumColumnWidth: '250px' };
  set(list, { metadata: { name: 'Категории провайдеров' }, style: { layout: { selfStretch: 'fill', flexSize: null, columnSpan: 1 }, typography: { fontSize: '14px', textAlign: 'left' }, spacing: { blockGap: '8px', padding: { top: '12px', bottom: '12px', left: '40px', right: '40px' } } } });
  list.attributes.layout = { type: 'flex', flexWrap: 'wrap' };
  set(panels, { metadata: { name: 'Логотипы провайдеров' }, style: { layout: { selfStretch: 'fit', flexSize: null, columnSpan: 3 } } });
  for (const panel of panels.innerBlocks) {
    const grids = panel.innerBlocks.filter(block => block.name === 'core/group' && block.attributes.layout?.type === 'grid');
    if (grids.length !== 1) throw new Error('Unexpected provider grid');
    set(grids[0], { layout: { minimumColumnWidth: grids[0].innerBlocks.length === 6 ? '240px' : '200px' }, style: { spacing: { blockGap: '16px' } } });
  }
  if (JSON.stringify(providerBefore) !== JSON.stringify(content(providers))) throw new Error('Provider content change');
  if (JSON.stringify(unchanged) !== JSON.stringify(blocks.filter(block => block !== providers).map(block => w.wp.blocks.serialize([block])))) throw new Error('Change outside Providers');
  const text = w.wp.blocks.serialize(blocks);
  const parsed = w.wp.blocks.parse(text);
  let count = 0;
  walk(parsed, block => {
    count++;
    if (!block.isValid || !block.name.startsWith('core/') || ['core/html','core/freeform','core/code','core/shortcode'].includes(block.name)) throw new Error('Non-native or invalid block ' + block.name);
  });
  fs.mkdirSync(output, { recursive: true });
  const manifest = [{ name: 'page', id: 67, type: 'page', beforeHash: hash(original), afterHash: hash(text), count, invalid: 0 }];
  fs.writeFileSync(path.join(output, 'page.html'), text);
  fs.writeFileSync(path.join(output, 'manifest.json'), JSON.stringify(manifest, null, 2));
  console.log(JSON.stringify(manifest, null, 2));
  function content(tree) {
    const result = [];
    walk([tree], block => {
      const a = block.attributes;
      if (block.name === 'core/paragraph' || block.name === 'core/heading') result.push([block.name,a.content]);
      if (block.name === 'core/image') result.push([block.name,a.id,a.url,a.alt,a.href]);
      if (block.name === 'core/tab-list') result.push([block.name,a.tabs]);
      if (block.name === 'core/tab-panel') result.push([block.name,a.label]);
      if (block.name === 'core/button') result.push([block.name,a.text,a.url]);
    });
    return result;
  }
  function hash(text) { return crypto.createHash('sha256').update(text).digest('hex'); }
}
