/** Apply the partner, ALLVEND and provider-catalog copy/layout changes in native blocks. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { registerCarouselBlocks } from './carousel.mjs';

const sha = value => crypto.createHash('sha256').update(value).digest('hex');
const beforeHash = '54281d067b0ef4bdf101e50d5b42fa1f155b1c0acc2b498904c4afcae4639256';
const icons = [
  ['Оплата услуг', 'payment-services.png'],
  ['Самообслуживание', 'self-service.png'],
  ['Трансляция рекламы', 'advertising-video.png'],
  ['Безналичная оплата', 'cashless-pos.png'],
  ['Считывание QR', 'qr-scan.png'],
  ['Биометрическая идентификация', 'biometric.png'],
  ['Настройка интерфейса', 'interface-settings.png'],
  ['Удалённое управление', 'remote-console.png'],
  ['Продажа товаров', 'goods-sales.png'],
];
const walk = (tree, fn) => tree.forEach(block => { fn(block); walk(block.innerBlocks, fn); });
const find = (tree, predicate, label) => {
  const matches = [];
  walk(tree, block => { if (predicate(block)) matches.push(block); });
  if (matches.length !== 1) throw new Error(`Expected one ${label}, got ${matches.length}`);
  return matches[0];
};

export async function revision20260925d(w, origin, mediaFile, outputDir) {
  if (!mediaFile || !outputDir) throw new Error('Usage: build.mjs --revision-20260925d media-ids.json output-dir');
  await registerCarouselBlocks(w, origin);
  const dir = path.dirname(fileURLToPath(import.meta.url));
  const source = execFileSync('git', ['-C', path.resolve(dir, '../..'), 'show',
    'aef96a6:wordpress/native-blocks/page.html'], { encoding: 'utf8' });
  if (sha(source) !== beforeHash) throw new Error('Page baseline changed: ' + sha(source));
  const ids = JSON.parse(fs.readFileSync(mediaFile, 'utf8'));
  const catalog = JSON.parse(fs.readFileSync(path.join(dir, '../wp-content/mu-plugins/skysend-provider-catalog/catalog.json'), 'utf8'));
  const categories = Object.entries(catalog.categories || {});
  if (categories.length !== 10 || categories.reduce((sum, [, value]) => sum + value.items.length, 0) !== 2600) {
    throw new Error('Expected restored 600 + 2000 provider catalog');
  }

  const blocks = w.wp.blocks.parse(source);
  walk(blocks, block => { if (!block.isValid) throw new Error('Invalid source block: ' + block.name); });
  const partners = find(blocks, block => block.attributes.anchor === 'participants', 'partner section');
  let pdfCount = 0;
  walk([partners], block => {
    if (block.name !== 'core/paragraph' || !String(block.attributes.content).includes('.pdf')) return;
    const content = String(block.attributes.content);
    const match = content.match(/>(Скачать [^<]+) · PDF, (\d+) КБ \(20\d{2}\)<\/a>/u);
    if (!match) throw new Error('Unexpected partner PDF label: ' + content);
    block.attributes.content = content.replace(match[0], `>Скачать презентацию · PDF, ${match[2]} КБ</a>`);
    pdfCount++;
  });
  if (pdfCount !== 6) throw new Error(`Expected six partner PDFs, got ${pdfCount}`);

  const allvend = find(blocks, block => block.attributes.anchor === 'allvend', 'ALLVEND section');
  const iconHashes = {};
  for (const [label, filename] of icons) {
    const group = find([allvend], block => block.name === 'core/group' && block.attributes.metadata?.name === label, label);
    const image = find([group], block => block.name === 'core/image', label + ' image');
    const id = ids[filename];
    if (!Number.isInteger(id) || id <= 0) throw new Error('Missing media ID: ' + filename);
    const file = path.join(dir, 'assets/allvend-icons-20260925', filename);
    if (!fs.existsSync(file)) throw new Error('Missing icon asset: ' + filename);
    iconHashes[filename] = sha(fs.readFileSync(file));
    Object.assign(image.attributes, {
      id,
      url: `${origin}/wp-content/uploads/2026/09/${filename}`,
      alt: '',
    });
  }

  const capabilities = find(blocks, block => block.attributes.anchor === 'capabilities', 'processing section');
  const list = find([capabilities], block => block.name === 'core/list' && block.innerBlocks.some(item =>
    String(item.attributes.content).includes('Распределённая архитектура')), 'processing list');
  const removed = list.innerBlocks.filter(item => String(item.attributes.content).includes('Распределённая архитектура'));
  if (removed.length !== 1 || list.innerBlocks.length !== 5) throw new Error('Unexpected processing bullets');
  list.innerBlocks = list.innerBlocks.filter(item => item !== removed[0]);

  const providers = find(blocks, block => block.attributes.anchor === 'providers', 'provider section');
  const tabs = find([providers], block => block.name === 'core/tabs' && block.attributes.anchor === 'provider-categories', 'provider tabs');
  if (tabs.innerBlocks[0]?.name !== 'core/tab-list' || tabs.innerBlocks[1]?.name !== 'core/tab-panels' ||
      tabs.innerBlocks[1].innerBlocks.length !== 15) throw new Error('Unexpected provider tab structure');
  const b = (name, attrs = {}, children = []) => w.wp.blocks.createBlock(`core/${name}`, attrs, children);
  tabs.innerBlocks[0].attributes.tabs = categories.map(([, category]) => ({ label: category.label }));
  tabs.innerBlocks[1].innerBlocks = categories.map(([slug, category]) => {
    const grid = b('group', {
      className: 'sky-provider-grid',
      layout: { type: 'default' },
      metadata: { name: 'Плитки провайдеров' },
    });
    const shell = b('group', {
      anchor: `provider-catalog-${slug}`,
      className: 'sky-provider-catalog',
      layout: { type: 'default' },
      metadata: { name: `Каталог: ${category.label}` },
    }, [grid]);
    return b('tab-panel', { label: category.label, layout: { type: 'default' } }, [shell]);
  });

  const content = w.wp.blocks.serialize(blocks);
  let count = 0;
  walk(w.wp.blocks.parse(content), block => {
    count++;
    if (!block.isValid || (!block.name.startsWith('core/') && !['cb/carousel-v2', 'cb/slide-v2'].includes(block.name))) {
      throw new Error('Invalid generated block: ' + block.name);
    }
  });
  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(path.join(outputDir, 'page.html'), content);
  const manifest = { revision: '2026-09-25d', origin, mediaIds: ids, iconHashes,
    partnerPdfs: pdfCount, categories: categories.length, entries: 2600,
    report: { page: { id: 67, beforeHash, afterHash: sha(content), blocks: count } } };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest, null, 2));
}
