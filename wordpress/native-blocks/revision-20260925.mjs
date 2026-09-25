/** Replace the live provider tabs with the owner-supplied 5,000-entry catalog. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { registerCarouselBlocks } from './carousel.mjs';

const sha = value => crypto.createHash('sha256').update(value).digest('hex');
const sourceHash = '02fe69e830d52c883f72aaebf23355b0fefd6357238903533bf3be9bf45e8df9';
const walk = (tree, fn) => tree.forEach(block => { fn(block); walk(block.innerBlocks, fn); });
const findOne = (tree, predicate, label) => {
  const found = [];
  walk(tree, block => { if (predicate(block)) found.push(block); });
  if (found.length !== 1) throw new Error(`Expected one ${label}, got ${found.length}`);
  return found[0];
};

export async function revision20260925(w, origin, outputDir) {
  if (!outputDir) throw new Error('Usage: build.mjs --revision-20260925 output-dir');
  await registerCarouselBlocks(w, origin);
  const dir = path.dirname(fileURLToPath(import.meta.url));
  const source = fs.readFileSync(path.join(dir, 'page-before-provider-20260925.html'), 'utf8');
  if (sha(source) !== sourceHash) throw new Error('Live page baseline changed: ' + sha(source));
  const catalog = JSON.parse(fs.readFileSync(path.join(dir, '../wp-content/mu-plugins/skysend-provider-catalog/catalog.json'), 'utf8'));
  const categories = Object.entries(catalog.categories || {});
  if (catalog.version !== '2026-09-25' || categories.length !== 15 ||
      categories.reduce((sum, [, value]) => sum + value.items.length, 0) !== 5000) {
    throw new Error('Unexpected archive catalog');
  }
  const slugs = new Set();
  for (const [slug, category] of categories) {
    if (!/^[a-z0-9_-]{1,64}$/.test(slug) || slugs.has(slug) || !category.label || !category.items.length) {
      throw new Error('Invalid category ' + slug);
    }
    slugs.add(slug);
  }
  const blocks = w.wp.blocks.parse(source);
  walk(blocks, block => { if (!block.isValid) throw new Error('Invalid live source block: ' + block.name); });
  const providerSection = findOne(blocks, block => block.attributes.anchor === 'providers', 'providers section');
  const tabs = findOne([providerSection], block => block.name === 'core/tabs' && block.attributes.anchor === 'provider-categories', 'provider tabs');
  if (tabs.innerBlocks[0]?.name !== 'core/tab-list' || tabs.innerBlocks[1]?.name !== 'core/tab-panels' ||
      tabs.innerBlocks[1].innerBlocks.length !== 10) throw new Error('Unexpected live tab structure');
  const b = (name, attrs = {}, children = []) => w.wp.blocks.createBlock(`core/${name}`, attrs, children);
  tabs.innerBlocks[0].attributes.tabs = categories.map(([, category]) => ({ label: category.label }));
  tabs.innerBlocks[1].innerBlocks = categories.map(([slug, category]) => b('tab-panel', {
    label: category.label,
    layout: { type: 'default' },
  }, [b('group', {
    anchor: `provider-catalog-${slug}`,
    className: 'sky-provider-catalog',
    layout: { type: 'default' },
    metadata: { name: `Каталог: ${category.label}` },
  }, [b('group', {
    className: 'sky-provider-grid',
    layout: { type: 'default' },
    metadata: { name: 'Плитки провайдеров' },
  })])]));

  // The old note refers to the previous historical excerpt, not this archive.
  const oldNote = findOne([providerSection], block => block.name === 'core/paragraph' &&
    String(block.attributes.content).includes('Ниже — примеры из каталога SkySend'), 'old excerpt note');
  const remove = tree => {
    for (const block of tree) {
      const index = block.innerBlocks.indexOf(oldNote);
      if (index >= 0) { block.innerBlocks.splice(index, 1); return true; }
      if (remove(block.innerBlocks)) return true;
    }
    return false;
  };
  if (!remove([providerSection])) throw new Error('Could not remove old note');

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
  const manifest = { revision: '2026-09-25', origin, sourceArchiveSha256: catalog.source.sha256,
    categories: categories.length, entries: 5000,
    report: { page: { id: 67, beforeHash: sourceHash, afterHash: sha(content), blocks: count } } };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest, null, 2));
}
