/** Replace only the finance banner image in the editable WordPress page. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { registerCarouselBlocks } from './carousel.mjs';

const sha = value => crypto.createHash('sha256').update(value).digest('hex');
const beforeHash = 'b29edeba1404e7f84a593043b56de1827ab8fd1a6056d247f49216cb0f5f7528';
const oldFile = 'banner-finance-bag-income-20260924.webp';
const newFile = 'banner-finance-transparent-20260925.png';

export async function revision20260925b(w, origin, mediaFile, outputDir) {
  if (!mediaFile || !outputDir) throw new Error('Usage: build.mjs --revision-20260925b media-ids.json output-dir');
  await registerCarouselBlocks(w, origin);
  const dir = path.dirname(fileURLToPath(import.meta.url));
  const repo = path.resolve(dir, '../..');
  const source = execFileSync('git', ['-C', repo, 'show', 'fb3b5a1:wordpress/native-blocks/page.html'], { encoding: 'utf8' });
  if (sha(source) !== beforeHash) throw new Error('Baseline page checksum mismatch: ' + sha(source));
  const ids = JSON.parse(fs.readFileSync(mediaFile, 'utf8'));
  const id = ids[newFile];
  if (!Number.isInteger(id) || id <= 0) throw new Error('Missing new media ID');
  const asset = fs.readFileSync(path.join(dir, 'assets/revision-20260925b', newFile));
  const assetHash = sha(asset);
  if (assetHash !== '83059de6a269059237377103589bef679d9c448fd11bdf31940390eb454b879a') {
    throw new Error('Image asset checksum mismatch');
  }
  const blocks = w.wp.blocks.parse(source);
  const images = [];
  const walk = tree => tree.forEach(block => {
    if (!block.isValid) throw new Error('Invalid source block: ' + block.name);
    if (block.name === 'core/image' && String(block.attributes.url).endsWith('/' + oldFile)) images.push(block);
    walk(block.innerBlocks);
  });
  walk(blocks);
  if (images.length !== 1 || images[0].attributes.id !== 388) throw new Error('Unexpected finance image block');
  Object.assign(images[0].attributes, {
    id,
    url: `${origin}/wp-content/uploads/2026/09/${newFile}`,
    alt: 'Напольный платёжный терминал. Доход +20%: слово на мешке, процент справа сверху',
  });
  const content = w.wp.blocks.serialize(blocks);
  let count = 0;
  walk(w.wp.blocks.parse(content), block => { count++; if (!block.isValid) throw new Error('Invalid generated block'); });
  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(path.join(outputDir, 'page.html'), content);
  const manifest = { revision: '2026-09-25b', origin, mediaIds: ids, assetHash,
    report: { page: { id: 67, beforeHash, afterHash: sha(content), blocks: count } } };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest, null, 2));
}
