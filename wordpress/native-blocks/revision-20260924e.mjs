/** Reposition the finance-image lettering without changing editable layout. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { registerCarouselBlocks } from './carousel.mjs';

const sha = value => crypto.createHash('sha256').update(value).digest('hex');
const previousHash = '6e0aade2460b22e2e2e8c1bdb1735a8bdf9a17d3c929da964ba31868b140e19b';
const oldFile = 'banner-finance-income-20260924.webp';
const newFile = 'banner-finance-bag-income-20260924.webp';

export async function revision20260924e(w, origin, mediaFile, outputDir) {
  if (!mediaFile || !outputDir) throw new Error('Usage: build.mjs --revision-20260924e media-ids.json output-dir');
  await registerCarouselBlocks(w, origin);
  const dir = path.dirname(fileURLToPath(import.meta.url));
  const repo = path.resolve(dir, '../..');
  const source = execFileSync('git', ['-C', repo, 'show', '9a7765e:wordpress/native-blocks/page.html'], { encoding: 'utf8' });
  if (sha(source) !== previousHash) throw new Error('Previous page snapshot mismatch: ' + sha(source));
  const ids = JSON.parse(fs.readFileSync(mediaFile, 'utf8'));
  if (!Number.isInteger(ids[newFile])) throw new Error('Missing media ID: ' + newFile);
  const blocks = w.wp.blocks.parse(source);
  const images = [];
  const walk = tree => tree.forEach(block => {
    if (!block.isValid) throw new Error('Invalid source block: ' + block.name);
    if (block.name === 'core/image' && String(block.attributes.url).endsWith('/' + oldFile)) images.push(block);
    walk(block.innerBlocks);
  });
  walk(blocks);
  if (images.length !== 1) throw new Error(`Expected one finance image, got ${images.length}`);
  Object.assign(images[0].attributes, {
    id: ids[newFile],
    url: `${origin}/wp-content/uploads/2026/09/${newFile}`,
    alt: 'Напольный платёжный терминал. Доход +20%: слово на мешке, процент справа сверху',
  });
  const content = w.wp.blocks.serialize(blocks);
  let count = 0;
  const check = tree => tree.forEach(block => {
    count++;
    if (!block.isValid || (!block.name.startsWith('core/') && !['cb/carousel-v2', 'cb/slide-v2'].includes(block.name))) {
      throw new Error('Invalid generated block: ' + block.name);
    }
    check(block.innerBlocks);
  });
  check(w.wp.blocks.parse(content));
  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(path.join(outputDir, 'page.html'), content);
  const manifest = { revision: '2026-09-24e', origin, mediaIds: ids, report: { page: { beforeHash: previousHash, afterHash: sha(content), blocks: count } } };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest, null, 2));
}
