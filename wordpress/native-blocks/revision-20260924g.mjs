/** Finish layout spacing after the live visual check. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { registerCarouselBlocks } from './carousel.mjs';

const dir = path.dirname(fileURLToPath(import.meta.url));
const sha = value => crypto.createHash('sha256').update(value).digest('hex');
const expected = {
  page: '302304c984bc0e486f5669b39f05f75c4d61792347727aa6cb88a48df79a7057',
  styles: '72157eea8aff9985fc0505ba8a86659c098feb47cbb9a73068a98722f75c23c6',
};
const walk = (tree, fn) => tree.forEach(block => { fn(block); walk(block.innerBlocks, fn); });
const one = (tree, fn, label) => {
  const found = [];
  walk(tree, block => { if (fn(block)) found.push(block); });
  if (found.length !== 1) throw new Error(`Expected one ${label}, got ${found.length}`);
  return found[0];
};
export async function revision20260924g(w, origin, outputDir) {
  if (!outputDir) throw new Error('Usage: build.mjs --revision-20260924g output-dir');
  await registerCarouselBlocks(w, origin);
  const sourcePage = fs.readFileSync(path.join(dir, 'page.html'), 'utf8');
  const sourceStyles = fs.readFileSync(path.join(dir, 'styles.json'), 'utf8');
  if (sha(sourcePage) !== expected.page || sha(sourceStyles) !== expected.styles) {
    throw new Error('Live source changed after revision f');
  }
  const page = w.wp.blocks.parse(sourcePage);
  walk(page, block => { if (!block.isValid) throw new Error('Invalid source block ' + block.name); });
  const partners = one(page, block => block.attributes.anchor === 'participants', 'partner section');
  if (partners.attributes.style?.dimensions?.minHeight !== '600px') {
    throw new Error('Partner section height changed');
  }
  delete partners.attributes.style.dimensions.minHeight;

  const provider = one(page, block => block.attributes.anchor === 'providers', 'provider section');
  const panels = one([provider], block => block.name === 'core/tab-panels', 'provider panels');
  if (panels.innerBlocks.length !== 10) throw new Error('Provider category count changed');
  let cards = 0;
  for (const panel of panels.innerBlocks) {
    const carousel = one([panel], block => block.name === 'cb/carousel-v2', 'category carousel');
    for (const slide of carousel.innerBlocks) {
      const grid = one([slide], block => block.name === 'core/group' &&
        String(block.attributes.className).includes('sky-provider-grid'), 'provider grid');
      const count = grid.innerBlocks.length;
      if (count < 6 || count > 9) throw new Error('Unexpected provider page size ' + count);
      grid.attributes.className = `sky-provider-grid sky-provider-grid--${count}`;
      cards += count;
    }
  }
  if (cards !== 279) throw new Error('Provider cards changed: ' + cards);
  const nextPage = w.wp.blocks.serialize(page);
  let blockCount = 0;
  walk(w.wp.blocks.parse(nextPage), block => {
    blockCount++;
    if (!block.isValid || (!block.name.startsWith('core/') &&
      !['cb/carousel-v2', 'cb/slide-v2'].includes(block.name))) {
      throw new Error('Invalid generated block ' + block.name);
    }
  });
  const styles = JSON.parse(sourceStyles);
  styles.styles.css += '\n' + [
    '#provider-categories .sky-provider-grid { grid-template-columns:repeat(12,minmax(0,1fr)) !important; }',
    '#provider-categories .sky-provider-grid > .wp-block-group { grid-column:span 4; }',
    '#provider-categories .sky-provider-grid--7 > .wp-block-group:nth-child(-n+4), #provider-categories .sky-provider-grid--8 > .wp-block-group { grid-column:span 3; }',
    '@media (max-width:782px) { #provider-categories .sky-provider-grid { grid-template-columns:repeat(2,minmax(0,1fr)) !important; } #provider-categories .sky-provider-grid > .wp-block-group { grid-column:auto !important; } }',
  ].join('\n');
  const nextStyles = JSON.stringify(styles, null, 2) + '\n';
  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(path.join(outputDir, 'page.html'), nextPage);
  fs.writeFileSync(path.join(outputDir, 'styles.json'), nextStyles);
  const manifest = { revision: '2026-09-24g', categories: 10, providers: cards,
    report: {
      page: { id: 67, beforeHash: sha(sourcePage), afterHash: sha(nextPage), blocks: blockCount },
      styles: { id: 66, beforeHash: sha(sourceStyles), afterHash: sha(nextStyles) },
    } };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest, null, 2));
}
