/** Move the finance callout into its image and restore XML within the gateway photo. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { registerCarouselBlocks } from './carousel.mjs';

const sha = value => crypto.createHash('sha256').update(value).digest('hex');
const previousHash = '93b95d1154213f09d18c4a9be2e4d3d29524b23e7de842492456c5a66c95f08e';

export async function revision20260924d(w, origin, mediaFile, outputDir) {
  if (!mediaFile || !outputDir) throw new Error('Usage: build.mjs --revision-20260924d media-ids.json output-dir');
  await registerCarouselBlocks(w, origin);
  const dir = path.dirname(fileURLToPath(import.meta.url));
  const repo = path.resolve(dir, '../..');
  const source = execFileSync('git', ['-C', repo, 'show', 'c6507ab:wordpress/native-blocks/page.html'], { encoding: 'utf8' });
  if (sha(source) !== previousHash) throw new Error('Previous page snapshot mismatch: ' + sha(source));
  const ids = JSON.parse(fs.readFileSync(mediaFile, 'utf8'));
  for (const file of ['banner-finance-income-20260924.webp', 'partner-gateways-xml-20260924.webp']) {
    if (!Number.isInteger(ids[file])) throw new Error('Missing media ID: ' + file);
  }
  const blocks = w.wp.blocks.parse(source);
  const walk = (tree, fn) => tree.forEach(block => { fn(block); walk(block.innerBlocks, fn); });
  const find = (tree, predicate, description) => {
    const matches = [];
    walk(tree, block => { if (predicate(block)) matches.push(block); });
    if (matches.length !== 1) throw new Error(`Expected one ${description}, got ${matches.length}`);
    return matches[0];
  };
  walk(blocks, block => { if (!block.isValid) throw new Error('Invalid source block: ' + block.name); });

  const financeSlide = find(blocks, block => block.name === 'cb/slide-v2' && block.attributes.metadata?.name === 'Финансовые условия', 'finance slide');
  const financeCols = find([financeSlide], block => block.name === 'core/columns', 'finance columns');
  const left = financeCols.innerBlocks[0];
  const calloutIndex = left.innerBlocks.findIndex(block => block.name === 'core/group' && block.attributes.metadata?.name === 'Доход +20%');
  if (calloutIndex < 0) throw new Error('Separate finance callout missing');
  left.innerBlocks.splice(calloutIndex, 1);
  const financeImage = find([financeSlide], block => block.name === 'core/image' &&
    String(block.attributes.url).endsWith('/banner-finance-terminal-20260924.webp'), 'finance image');
  Object.assign(financeImage.attributes, {
    id: ids['banner-finance-income-20260924.webp'],
    url: `${origin}/wp-content/uploads/2026/09/banner-finance-income-20260924.webp`,
    alt: 'Напольный платёжный терминал. Доход +20%',
  });

  const gatewayImage = find(blocks, block => block.name === 'core/image' &&
    String(block.attributes.url).endsWith('/partner-gateways-no-xml-20260924.webp'), 'gateway partner image');
  Object.assign(gatewayImage.attributes, {
    id: ids['partner-gateways-xml-20260924.webp'],
    url: `${origin}/wp-content/uploads/2026/09/partner-gateways-xml-20260924.webp`,
    alt: 'Интеграция XML-шлюза SkySend',
  });

  const content = w.wp.blocks.serialize(blocks);
  if (content.includes('"name":"Доход +20%"')) throw new Error('Separate finance callout still present');
  let count = 0;
  walk(w.wp.blocks.parse(content), block => {
    count++;
    if (!block.isValid || (!block.name.startsWith('core/') && !['cb/carousel-v2', 'cb/slide-v2'].includes(block.name))) {
      throw new Error('Invalid generated block: ' + block.name);
    }
  });
  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(path.join(outputDir, 'page.html'), content);
  const manifest = { revision: '2026-09-24d', origin, mediaIds: ids, report: { page: { beforeHash: previousHash, afterHash: sha(content), blocks: count } } };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest, null, 2));
}
