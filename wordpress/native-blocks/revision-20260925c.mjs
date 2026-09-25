/** Compact the active, editable WordPress header without changing its contents. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';

const sha = value => crypto.createHash('sha256').update(value).digest('hex');
const beforeHash = '5e3fa3cdf77c0496e29e4715548c022755896a3e60082a5985478edffc3a517f';

export async function revision20260925c(w, origin, outputDir) {
  if (!outputDir) throw new Error('Usage: build.mjs --revision-20260925c output-dir');
  const dir = path.dirname(fileURLToPath(import.meta.url));
  const source = fs.readFileSync(path.join(dir, 'header-before-compact-20260925.html'), 'utf8');
  if (sha(source) !== beforeHash) throw new Error('Active header baseline mismatch');
  const blocks = w.wp.blocks.parse(source);
  if (blocks.length !== 1 || blocks[0].name !== 'core/group' || !blocks[0].isValid ||
      blocks[0].attributes.style?.spacing?.padding?.top !== '8px' ||
      blocks[0].attributes.style?.spacing?.padding?.bottom !== '8px') {
    throw new Error('Unexpected header wrapper');
  }
  const images = [];
  const visit = (tree, fn) => tree.forEach(block => { fn(block); visit(block.innerBlocks, fn); });
  visit(blocks, block => {
    if (!block.isValid) throw new Error('Invalid existing header block: ' + block.name);
    if (block.name === 'core/image' && block.attributes.id === 400 &&
        String(block.attributes.url).endsWith('/skysend-header-20260924h.png')) images.push(block);
  });
  if (images.length !== 1 || images[0].attributes.width !== '120px') throw new Error('Unexpected header logo');
  images[0].attributes.width = '96px';
  blocks[0].attributes.style.spacing.padding.top = '6px';
  blocks[0].attributes.style.spacing.padding.bottom = '6px';
  const content = w.wp.blocks.serialize(blocks);
  let count = 0;
  visit(w.wp.blocks.parse(content), block => {
    count++;
    if (!block.isValid) throw new Error('Invalid generated header block: ' + block.name);
  });
  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(path.join(outputDir, 'header.html'), content);
  const manifest = { revision: '2026-09-25c', origin, report: {
    header: { id: 68, beforeHash, afterHash: sha(content), blocks: count, logoWidth: '96px', verticalPadding: '6px' },
  } };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest, null, 2));
}
