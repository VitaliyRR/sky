/** Second 24 September pass: guarded, editable Gutenberg changes only. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { registerCarouselBlocks } from './carousel.mjs';
import { updateFooter } from './footer-revision-20260924b.mjs';

const sha = value => crypto.createHash('sha256').update(value).digest('hex');
const expected = {
  page: 'c31b8960d6bdcdd4693f698dce6e434c6345533d42b9eaedbd40566dc06406c4',
  footer: 'a62609bd3c5ef76aa993590b51cdba57da196b6c657611c890a172c71f9b2842',
  styles: '39d81909ce8b15d3b25835167dd842dbec101061e89a664b27532c33e13961d0',
};
const esc = value => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');

export async function revision20260924b(w, origin, mediaFile, providersFile, outputDir) {
  if (!mediaFile || !providersFile || !outputDir) throw new Error('Usage: build.mjs --revision-20260924b media-ids.json providers.json output-dir');
  await registerCarouselBlocks(w, origin);
  const dir = path.dirname(fileURLToPath(import.meta.url));
  const repo = path.resolve(dir, '../..');
  const originalSnapshot = file => execFileSync('git', ['-C', repo, 'show', `7aee0df:wordpress/native-blocks/${file}`], { encoding: 'utf8' });
  const sources = {
    page: originalSnapshot('page.html'),
    footer: originalSnapshot('footer.html'),
    styles: originalSnapshot('styles.json'),
  };
  for (const [name, content] of Object.entries(sources)) {
    if (sha(content) !== expected[name]) throw new Error(`Stale ${name}: ${sha(content)}`);
  }
  const ids = JSON.parse(fs.readFileSync(mediaFile, 'utf8'));
  const providerData = JSON.parse(fs.readFileSync(providersFile, 'utf8'));
  if (!Array.isArray(providerData.categories) || providerData.categories.length < 8) throw new Error('Provider source incomplete');
  for (const file of ['banner-finance-terminal-20260924.webp', 'fastsys-boot-official-20260924.webp', 'payment-kiosk-20260924.png', 'zip-20260924.png', 'pdf-20260924.png']) {
    if (!Number.isInteger(ids[file])) throw new Error('Missing uploaded media: ' + file);
  }
  const url = file => `${origin}/wp-content/uploads/2026/09/${file}`;
  const b = (name, attrs = {}, innerBlocks = []) => w.wp.blocks.createBlock('core/' + name, attrs, innerBlocks);
  const p = (content, attrs = {}) => b('paragraph', { content, ...attrs });
  const image = (file, alt, attrs = {}) => b('image', { id: ids[file], url: url(file), alt, sizeSlug: 'full', linkDestination: 'none', ...attrs });
  const walk = (tree, fn) => tree.forEach(block => { fn(block); walk(block.innerBlocks, fn); });
  const find = (tree, predicate, description) => {
    const matches = [];
    walk(tree, block => { if (predicate(block)) matches.push(block); });
    if (matches.length !== 1) throw new Error(`Expected one ${description}; got ${matches.length}`);
    return matches[0];
  };
  const page = w.wp.blocks.parse(sources.page);
  const footer = w.wp.blocks.parse(sources.footer);
  for (const tree of [page, footer]) walk(tree, block => { if (!block.isValid) throw new Error('Invalid source block: ' + block.name); });

  const finance = find(page, block => block.name === 'core/image' && String(block.attributes.url).includes('banner-finance-20260924.webp'), 'finance banner');
  Object.assign(finance.attributes, {
    id: ids['banner-finance-terminal-20260924.webp'], url: url('banner-finance-terminal-20260924.webp'),
    alt: 'Напольный платёжный терминал', sizeSlug: 'full',
  });

  const fastsys = find(page, block => block.attributes.anchor === 'fastsys', 'FastSYS section');
  const fastsysCols = find([fastsys], block => block.name === 'core/columns', 'FastSYS columns');
  fastsysCols.innerBlocks[0].innerBlocks = [image('fastsys-boot-official-20260924.webp', 'Официальный экран загрузки FastSYS', {
    width: '100%', aspectRatio: '4/3', scale: 'cover', style: { border: { radius: '5px' } },
  })];
  const fastsysRight = fastsysCols.innerBlocks[1];
  const fastsysButton = find([fastsysRight], block => block.name === 'core/buttons', 'FastSYS link');
  fastsysRight.innerBlocks = [
    b('heading', { content: 'Собственная операционная система', level: 2 }),
    p('FastSYS 5 поставляется с ПО ALLVEND как готовое решение в виде ISO-образа и обеспечивает стабильную работу устройств самообслуживания.'),
    b('list', { ordered: false, style: { typography: { fontSize: '17px' }, spacing: { blockGap: '14px' } } }, [
      b('list-item', { content: 'Работа с flash-накопителя' }),
      b('list-item', { content: 'Система бинарных обновлений' }),
      b('list-item', { content: '5 степеней криптозащиты' }),
    ]),
    fastsysButton,
  ];

  const software = find(page, block => block.attributes.anchor === 'software', 'software section');
  const allvendCard = find([software], block => block.attributes.metadata?.name === 'ПО ALLVEND', 'ALLVEND software card');
  const oldIcon = find([allvendCard], block => block.name === 'core/icon', 'ALLVEND payment card icon');
  const replace = (tree, target, replacement) => {
    for (const parent of tree) {
      const index = parent.innerBlocks.indexOf(target);
      if (index >= 0) { parent.innerBlocks[index] = replacement; return true; }
      if (replace(parent.innerBlocks, target, replacement)) return true;
    }
    return false;
  };
  if (!replace([allvendCard], oldIcon, image('payment-kiosk-20260924.png', '', { width: '40px', height: '40px', scale: 'contain' }))) throw new Error('Could not replace ALLVEND icon');

  const providers = find(page, block => block.attributes.anchor === 'providers', 'providers section');
  const tabs = find([providers], block => block.name === 'core/tabs', 'providers tabs');
  if (tabs.innerBlocks[0]?.name !== 'core/tab-list' || tabs.innerBlocks[1]?.name !== 'core/tab-panels') throw new Error('Unexpected providers structure');
  const originalImages = new Map();
  const originalImagesByFile = new Map();
  walk(tabs.innerBlocks[1].innerBlocks, block => {
    if (block.name !== 'core/group' || !block.attributes.metadata?.name) return;
    const logo = block.innerBlocks.find(child => child.name === 'core/image');
    if (logo) {
      originalImages.set(block.attributes.metadata.name.toLocaleLowerCase('ru-RU'), logo.attributes);
      originalImagesByFile.set(String(logo.attributes.url).split('/').pop().toLowerCase(), logo.attributes);
    }
  });
  const providerCard = item => {
    const logo = originalImages.get(item.name.toLocaleLowerCase('ru-RU')) ||
      (item.image ? originalImagesByFile.get(String(item.image).split('/').pop().toLowerCase()) : undefined);
    const contents = [];
    if (logo) contents.push(b('image', { id: logo.id, url: logo.url, alt: item.name, sizeSlug: 'full', linkDestination: 'none', width: '100%', height: '58px', scale: 'contain' }));
    const mediaFile = item.mediaFile ? path.basename(item.mediaFile) : null;
    if (!logo && mediaFile && Number.isInteger(ids[mediaFile])) contents.push(image(mediaFile, item.name, { width: '100%', height: '58px', scale: 'contain' }));
    if (!logo && mediaFile && !Number.isInteger(ids[mediaFile])) throw new Error('Provider logo not uploaded: ' + mediaFile);
    contents.push(p(esc(item.name), { style: { typography: { fontSize: logo || item.mediaFile ? '13px' : '15px', fontWeight: '650', textAlign: 'center', lineHeight: '1.25' } } }));
    return b('group', {
      metadata: { name: item.name },
      layout: { type: 'flex', orientation: 'vertical', justifyContent: 'center', verticalAlignment: 'center', flexWrap: 'nowrap' },
      style: { color: { background: '#ffffff' }, border: { color: '#dbe5f1', width: '1px', radius: '5px' }, dimensions: { minHeight: '116px' }, spacing: { padding: { top: '10px', bottom: '10px', left: '8px', right: '8px' }, blockGap: '8px' } },
    }, contents);
  };
  const providerPanels = providerData.categories.map(category => {
    if (!category.title || !Array.isArray(category.items) || category.items.length < 10) throw new Error('Short provider category: ' + category.title);
    const seen = new Set();
    for (const item of category.items) {
      if (!item.name || seen.has(item.name)) throw new Error('Invalid/duplicate provider name: ' + item.name);
      seen.add(item.name);
    }
    const slides = [];
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
  tabs.innerBlocks[0].attributes.tabs = providerData.categories.map(category => ({ label: category.title }));
  tabs.innerBlocks[1].innerBlocks = providerPanels;
  const providerInner = providers.innerBlocks[0];
  if (!providerInner?.innerBlocks.includes(tabs)) throw new Error('Provider heading structure changed');
  providerInner.innerBlocks.splice(providerInner.innerBlocks.indexOf(tabs), 0,
    p('Более 5 000 провайдеров услуг. Ниже — примеры из каталога SkySend; актуальный перечень уточняйте при подключении.', {
      style: { typography: { fontSize: '15px', textAlign: 'center' }, color: { text: '#52647a' } },
    }));

  updateFooter(w, footer, { origin, mediaIds: ids });

  const styles = JSON.parse(sources.styles);
  const css = styles.styles.css || '';
  styles.styles.css = css + '\n' + [
    '#provider-categories .wp-block-tab-list button { background:#fff !important; border:1px solid #dbe5f1 !important; border-radius:5px !important; color:#0b63f6 !important; box-shadow:none !important; }',
    '#provider-categories .wp-block-tab-list button::before { content:none !important; border:0 !important; }',
    '#provider-categories .wp-block-tab-list button[aria-selected="true"] { background:#e5e7eb !important; border-color:#e5e7eb !important; color:#071426 !important; }',
    '#provider-categories .wp-block-tab-list button:hover, #provider-categories .wp-block-tab-list button:focus-visible { background:#8ed0ff !important; border-color:#8ed0ff !important; color:#071426 !important; }',
    '#provider-categories .wp-block-tab-list button:focus-visible { outline:2px solid #0b63f6 !important; outline-offset:2px; }',
    '#provider-categories .sky-provider-grid { grid-template-columns:repeat(3,minmax(0,1fr)) !important; }',
    '@media (max-width:700px) { #provider-categories .sky-provider-grid { grid-template-columns:repeat(2,minmax(0,1fr)) !important; } }',
    '#provider-categories .cb-carousel-block .swiper-button-next:hover, #provider-categories .cb-carousel-block .swiper-button-prev:hover { color:#8ed0ff !important; }',
  ].join('\n');

  const output = {};
  const report = {};
  for (const [name, tree] of [['page', page], ['footer', footer]]) {
    output[name] = w.wp.blocks.serialize(tree);
    let count = 0;
    walk(w.wp.blocks.parse(output[name]), block => {
      count++;
      if (!block.isValid || (!block.name.startsWith('core/') && !['cb/carousel-v2', 'cb/slide-v2'].includes(block.name))) throw new Error(`Invalid generated ${name} block: ${block.name}`);
    });
    report[name] = { beforeHash: sha(sources[name]), afterHash: sha(output[name]), blocks: count };
  }
  output.styles = JSON.stringify(styles, null, 2) + '\n';
  report.styles = { beforeHash: sha(sources.styles), afterHash: sha(output.styles) };
  fs.mkdirSync(outputDir, { recursive: true });
  for (const [name, content] of Object.entries(output)) fs.writeFileSync(path.join(outputDir, name + (name === 'styles' ? '.json' : '.html')), content);
  const manifest = { revision: '2026-09-24b', origin, providerSource: providerData.url || providerData.source, categories: providerData.categories.length, providers: providerData.categories.reduce((n, x) => n + x.items.length, 0), mediaIds: ids, report };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest, null, 2));
}
