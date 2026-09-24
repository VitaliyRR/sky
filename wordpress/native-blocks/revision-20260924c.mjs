/** Final 24 September pass: editable WordPress blocks, historical provider excerpt. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { registerCarouselBlocks } from './carousel.mjs';

const hash = value => crypto.createHash('sha256').update(value).digest('hex');
const expected = {
  page: '93c41e3f2539f1e42a2e0c05357131d2f0a82e8be8002cbf9e535fa662ba4627',
  header: '18890b978dad682a6a9b2569203cc17fba1ff3e05a93d2ee5b2d0287ab649095',
  footer: '39e8070586e11e623238960a478139b9b63ab3017fa1e2f331f4ca2ad34097f7',
  styles: 'd0ae23168386331c6c4229c4eebeb5293407a89b1795de763c70e789c96574f2',
};
const esc = value => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');

export async function revision20260924c(w, origin, mediaFile, providersFile, outputDir) {
  if (!mediaFile || !providersFile || !outputDir) throw new Error('Usage: build.mjs --revision-20260924c media-ids.json providers.json output-dir');
  await registerCarouselBlocks(w, origin);
  const dir = path.dirname(fileURLToPath(import.meta.url));
  const repo = path.resolve(dir, '../..');
  const sources = Object.fromEntries(Object.keys(expected).map(name => [name,
    execFileSync('git', ['-C', repo, 'show', `254edcf:wordpress/native-blocks/${name + (name === 'styles' ? '.json' : '.html')}`], { encoding: 'utf8' })]));
  for (const [name, content] of Object.entries(sources)) {
    if (hash(content) !== expected[name]) throw new Error(`Stale ${name}: ${hash(content)}`);
  }
  const ids = JSON.parse(fs.readFileSync(mediaFile, 'utf8'));
  const data = JSON.parse(fs.readFileSync(providersFile, 'utf8'));
  if (data.selectedCount !== 279 || data.categories?.length !== 10) throw new Error('Provider selection incomplete');
  for (const file of ['skysend-wordmark-light.png', 'skysend-wordmark-dark.png', 'skysend-site-icon.png', 'pos-terminal-20260924c.png', 'gear-20260924c.png']) {
    if (!Number.isInteger(ids[file])) throw new Error('Missing imported media: ' + file);
  }
  const b = (name, attrs = {}, children = []) => w.wp.blocks.createBlock(`core/${name}`, attrs, children);
  const p = (content, attrs = {}) => b('paragraph', { content: esc(content), ...attrs });
  const url = file => `${origin}/wp-content/uploads/2026/09/${file}`;
  const image = (file, alt, attrs = {}) => b('image', { id: ids[file], url: url(file), alt, sizeSlug: 'full', linkDestination: 'none', ...attrs });
  const walk = (tree, fn) => tree.forEach(block => { fn(block); walk(block.innerBlocks, fn); });
  const find = (tree, predicate, description) => {
    const matches = [];
    walk(tree, block => { if (predicate(block)) matches.push(block); });
    if (matches.length !== 1) throw new Error(`Expected one ${description}; got ${matches.length}`);
    return matches[0];
  };
  const replace = (tree, target, replacement) => {
    for (const parent of tree) {
      const index = parent.innerBlocks.indexOf(target);
      if (index >= 0) { parent.innerBlocks[index] = replacement; return true; }
      if (replace(parent.innerBlocks, target, replacement)) return true;
    }
    return false;
  };
  const page = w.wp.blocks.parse(sources.page);
  const header = w.wp.blocks.parse(sources.header);
  const footer = w.wp.blocks.parse(sources.footer);
  for (const [name, tree] of [['page', page], ['header', header], ['footer', footer]]) {
    walk(tree, block => { if (!block.isValid) throw new Error(`Invalid source ${name}: ${block.name}`); });
  }

  // Keep all three brand placements tied to a single flat visual system.
  const headerLogo = find(header, block => block.name === 'core/image' && String(block.attributes.url).endsWith('/skysend-logo.png'), 'header logo');
  Object.assign(headerLogo.attributes, { id: ids['skysend-wordmark-light.png'], url: url('skysend-wordmark-light.png'), width: '160px', alt: 'SkySend' });
  const footerLogo = find(footer, block => block.name === 'core/image' && String(block.attributes.url).endsWith('/skysend-logo.png'), 'footer logo');
  Object.assign(footerLogo.attributes, { id: ids['skysend-wordmark-dark.png'], url: url('skysend-wordmark-dark.png'), width: '180px', alt: 'SkySend' });
  delete footerLogo.attributes.style;

  // Restore the requested finance callout without bringing back the POS-device banner.
  const finance = find(page, block => block.name === 'cb/slide-v2' && block.attributes.metadata?.name === 'Финансовые условия', 'finance slide');
  const financeColumns = find([finance], block => block.name === 'core/columns', 'finance columns');
  const financeLeft = financeColumns.innerBlocks[0];
  const financeButton = financeLeft.innerBlocks.findIndex(block => block.name === 'core/buttons');
  if (financeButton < 0) throw new Error('Finance CTA missing');
  financeLeft.innerBlocks.splice(financeButton, 0, b('group', {
    metadata: { name: 'Доход +20%' },
    layout: { type: 'flex', orientation: 'vertical', justifyContent: 'left', flexWrap: 'nowrap' },
    style: { spacing: { blockGap: '0px', margin: { top: '18px' } } },
  }, [
    p('Доход', { style: { typography: { fontSize: '16px', fontWeight: '650' }, color: { text: '#52647a' } } }),
    p('+20%', { style: { typography: { fontSize: '48px', fontWeight: '800', lineHeight: '1' }, color: { text: '#0b63f6' } } }),
  ]));

  const archiveNote = find(page, block => block.name === 'core/paragraph' &&
    String(block.attributes.content).includes('Архивные материалы: условия и контакты'), 'archive disclaimer');
  if (!replace(page, archiveNote, null)) throw new Error('Archive disclaimer not found in tree');
  const stripNulls = tree => tree.forEach(block => { block.innerBlocks = block.innerBlocks.filter(Boolean); stripNulls(block.innerBlocks); });
  stripNulls(page);

  const pos = find(page, block => block.name === 'core/image' && String(block.attributes.url).endsWith('/pos-20260924.png'), 'bank card icon');
  Object.assign(pos.attributes, { id: ids['pos-terminal-20260924c.png'], url: url('pos-terminal-20260924c.png') });
  const gear = find(page, block => block.name === 'core/image' && String(block.attributes.url).endsWith('/settings-20260924.png'), 'interface icon');
  Object.assign(gear.attributes, { id: ids['gear-20260924c.png'], url: url('gear-20260924c.png') });
  const fastsysText = find(page, block => block.name === 'core/paragraph' &&
    String(block.attributes.content).startsWith('FastSYS 5 поставляется с ПО ALLVEND'), 'FastSYS text');
  fastsysText.attributes.content = 'FastSYS 5 поставляется с ПО ALLVEND как готовое решение в виде ISO образа и обеспечивает стабильную работу устройств на протяжении десятилетий.';

  // Source is the original SkySend catalogue, not a claim that these services all remain active.
  const providers = find(page, block => block.attributes.anchor === 'providers', 'providers section');
  const tabs = find([providers], block => block.name === 'core/tabs', 'provider tabs');
  if (tabs.innerBlocks[0]?.name !== 'core/tab-list' || tabs.innerBlocks[1]?.name !== 'core/tab-panels') throw new Error('Unexpected provider tab structure');
  const originalImages = new Map();
  const originalImagesByFile = new Map();
  walk(tabs.innerBlocks[1].innerBlocks, block => {
    if (block.name !== 'core/group' || !block.attributes.metadata?.name) return;
    const logo = block.innerBlocks.find(child => child.name === 'core/image');
    if (!logo) return;
    originalImages.set(block.attributes.metadata.name.toLocaleLowerCase('ru-RU'), logo.attributes);
    originalImagesByFile.set(String(logo.attributes.url).split('/').pop().toLowerCase(), logo.attributes);
  });
  const providerCard = item => {
    const oldLogo = originalImages.get(item.name.toLocaleLowerCase('ru-RU')) ||
      (item.image ? originalImagesByFile.get(String(item.image).split('/').pop().toLowerCase()) : undefined);
    const mediaName = item.mediaFile ? path.basename(item.mediaFile) : null;
    const logo = oldLogo
      ? b('image', { id: oldLogo.id, url: oldLogo.url, alt: item.name, sizeSlug: 'full', linkDestination: 'none', width: '100%', height: '58px', scale: 'contain' })
      : mediaName && Number.isInteger(ids[mediaName])
        ? image(mediaName, item.name, { width: '100%', height: '58px', scale: 'contain' })
        : null;
    if (!logo) throw new Error('Provider logo missing: ' + item.id + ' ' + item.name);
    return b('group', {
      metadata: { name: item.name },
      layout: { type: 'flex', orientation: 'vertical', justifyContent: 'center', verticalAlignment: 'center', flexWrap: 'nowrap' },
      style: { color: { background: '#ffffff' }, border: { color: '#dbe5f1', width: '1px', radius: '5px' }, dimensions: { minHeight: '116px' }, spacing: { padding: { top: '10px', bottom: '10px', left: '8px', right: '8px' }, blockGap: '8px' } },
    }, [logo, p(item.name, { style: { typography: { fontSize: '13px', fontWeight: '650', textAlign: 'center', lineHeight: '1.25' } } })]);
  };
  const idsSeen = new Set();
  const panels = data.categories.map(category => {
    if (!category.title || !Array.isArray(category.items) || category.items.length < 4) throw new Error('Empty provider category: ' + category.title);
    const slides = [];
    for (const item of category.items) {
      if (idsSeen.has(item.id)) throw new Error('Duplicate provider ID: ' + item.id);
      idsSeen.add(item.id);
    }
    for (let i = 0; i < category.items.length; i += 6) {
      slides.push(w.wp.blocks.createBlock('cb/slide-v2', { metadata: { name: `${category.title}: ${Math.floor(i / 6) + 1}` } }, [
        b('group', { className: 'sky-provider-grid', layout: { type: 'grid', minimumColumnWidth: '250px' }, style: { spacing: { blockGap: '12px' } } }, category.items.slice(i, i + 6).map(providerCard)),
      ]));
    }
    return b('tab-panel', { label: category.title, layout: { type: 'default' } }, [
      w.wp.blocks.createBlock('cb/carousel-v2', {
        slidesPerView: 1, slidesPerGroup: 1, spaceBetween: 0, speed: 350,
        navigation: true, pagination: true, autoplay: false, loop: false,
        observer: true, observeParents: true, resizeObserver: true, observeSlideChildren: true,
        breakpoints: [{ width: 768, slidesPerView: 1, slidesPerGroup: 1 }],
        metadata: { name: `${category.title} — листать провайдеров` },
      }, slides),
    ]);
  });
  if (idsSeen.size !== 279) throw new Error('Provider IDs missing: ' + idsSeen.size);
  tabs.innerBlocks[0].attributes.tabs = data.categories.map(category => ({ label: category.title }));
  tabs.innerBlocks[1].innerBlocks = panels;

  const styles = JSON.parse(sources.styles);
  styles.styles.css += '\n' + [
    'header a[href^="tel:"] { text-decoration:none !important; }',
    'header a[href^="tel:"]:hover, header a[href^="tel:"]:focus-visible { text-decoration:none !important; }',
    '#provider-categories .wp-block-tab-list { border:0 !important; box-shadow:none !important; background:transparent !important; }',
    '#provider-categories .wp-block-tab-list button { border:1px solid #dbe5f1 !important; box-shadow:none !important; }',
    '#provider-categories .wp-block-tab-list button[aria-selected="true"] { background:#e5e7eb !important; border-color:#e5e7eb !important; color:#071426 !important; }',
    '#provider-categories .wp-block-tab-list button:hover { background:#8ed0ff !important; border-color:#8ed0ff !important; color:#071426 !important; }',
  ].join('\n');

  const output = {};
  const report = {};
  for (const [name, tree] of [['page', page], ['header', header], ['footer', footer]]) {
    output[name] = w.wp.blocks.serialize(tree);
    let count = 0;
    walk(w.wp.blocks.parse(output[name]), block => {
      count++;
      if (!block.isValid || (!block.name.startsWith('core/') && !['cb/carousel-v2', 'cb/slide-v2'].includes(block.name))) {
        throw new Error(`Invalid generated ${name} block: ${block.name}`);
      }
    });
    report[name] = { beforeHash: hash(sources[name]), afterHash: hash(output[name]), blocks: count };
  }
  output.styles = JSON.stringify(styles, null, 2) + '\n';
  report.styles = { beforeHash: hash(sources.styles), afterHash: hash(output.styles) };
  fs.mkdirSync(outputDir, { recursive: true });
  for (const [name, content] of Object.entries(output)) fs.writeFileSync(path.join(outputDir, name + (name === 'styles' ? '.json' : '.html')), content);
  const manifest = { revision: '2026-09-24c', origin, providerSource: data.sourceEndpoint, categories: data.categories.length, providers: idsSeen.size, mediaIds: ids, report };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify({ revision: manifest.revision, providerSource: manifest.providerSource, categories: manifest.categories, providers: manifest.providers, report }, null, 2));
}
