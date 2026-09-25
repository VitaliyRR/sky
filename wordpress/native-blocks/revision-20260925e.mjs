/** Restore the requested branded ALLVEND pictograms using native image duotone. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { registerCarouselBlocks } from './carousel.mjs';

const sha = value => crypto.createHash('sha256').update(value).digest('hex');
const beforeHash = 'fb3fc5ed07ea5bf93ba910de8a7c6d743b1f7cf8020be4f4ddbb2636014ee965';
const replacements = [
  { label: 'Оплата услуг', beforeId: 420, id: 119, filename: 'beeline.png',
    duotone: ['#0b2b61', '#0b63f6'] },
  { label: 'Самообслуживание', beforeId: 421, id: 121, filename: 'mcdonalds.png',
    duotone: ['#0b63f6', '#0b63f6'] },
  { label: 'Удалённое управление', beforeId: 427, id: 115, filename: 'remote-20260924.png' },
  { label: 'Продажа товаров', beforeId: 428, id: 120, filename: 'magnit.png',
    duotone: ['#0b63f6', '#0b63f6'] },
];
const walk = (tree, visitor) => tree.forEach(block => { visitor(block); walk(block.innerBlocks, visitor); });
const findOne = (tree, predicate, label) => {
  const matches = [];
  walk(tree, block => { if (predicate(block)) matches.push(block); });
  if (matches.length !== 1) throw new Error(`Expected one ${label}, got ${matches.length}`);
  return matches[0];
};

export async function revision20260925e(w, origin, outputDir) {
  if (!outputDir) throw new Error('Usage: build.mjs --revision-20260925e output-dir');
  await registerCarouselBlocks(w, origin);
  const dir = path.dirname(fileURLToPath(import.meta.url));
  const source = execFileSync('git', ['-C', path.resolve(dir, '../..'), 'show',
    '9a91b77:wordpress/native-blocks/page.html'], { encoding: 'utf8' });
  if (sha(source) !== beforeHash) throw new Error('Page baseline changed: ' + sha(source));
  const blocks = w.wp.blocks.parse(source);
  walk(blocks, block => { if (!block.isValid) throw new Error('Invalid source block: ' + block.name); });
  const allvend = findOne(blocks, block => block.attributes.anchor === 'allvend', 'ALLVEND section');
  const assetHashes = {};
  for (const item of replacements) {
    const group = findOne([allvend], block => block.name === 'core/group' &&
      block.attributes.metadata?.name === item.label, item.label);
    const image = findOne([group], block => block.name === 'core/image', item.label + ' image');
    if (image.attributes.id !== item.beforeId) throw new Error('Unexpected current icon: ' + item.label);
    const file = path.join(dir, 'assets/revision-20260924/icons', item.filename);
    if (!fs.existsSync(file)) throw new Error('Missing original icon: ' + item.filename);
    assetHashes[item.filename] = sha(fs.readFileSync(file));
    image.attributes.id = item.id;
    image.attributes.url = `${origin}/wp-content/uploads/2026/09/${item.filename}`;
    image.attributes.alt = '';
    if (item.duotone) {
      image.attributes.style = { ...image.attributes.style,
        color: { ...image.attributes.style?.color, duotone: item.duotone } };
    } else if (image.attributes.style?.color?.duotone) {
      delete image.attributes.style.color.duotone;
    }
  }
  const content = w.wp.blocks.serialize(blocks);
  let count = 0;
  const reparsed = w.wp.blocks.parse(content);
  walk(reparsed, block => {
    count++;
    if (!block.isValid || (!block.name.startsWith('core/') &&
        !['cb/carousel-v2', 'cb/slide-v2'].includes(block.name))) {
      throw new Error('Invalid generated block: ' + block.name);
    }
  });
  const finalAllvend = findOne(reparsed, block => block.attributes.anchor === 'allvend', 'rendered ALLVEND');
  for (const item of replacements) {
    const group = findOne([finalAllvend], block => block.attributes.metadata?.name === item.label,
      'rendered ' + item.label);
    const image = findOne([group], block => block.name === 'core/image', 'rendered image ' + item.label);
    if (image.attributes.id !== item.id ||
        JSON.stringify(image.attributes.style?.color?.duotone) !== JSON.stringify(item.duotone)) {
      throw new Error('Icon or duotone lost during serialization: ' + item.label);
    }
  }
  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(path.join(outputDir, 'page.html'), content);
  const manifest = { revision: '2026-09-25e', origin, replacements, assetHashes,
    report: { page: { id: 67, beforeHash, afterHash: sha(content), blocks: count } } };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest, null, 2));
}
