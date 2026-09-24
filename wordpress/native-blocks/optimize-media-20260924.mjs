/** Reproducible, offline resizing for the 24.09.2026 WordPress media revision. */
import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const root = path.dirname(new URL(import.meta.url).pathname.replace(/^\/(?:[A-Za-z]:)/, match => match.slice(1)));
const assets = path.join(root, 'assets', 'revision-20260924');
const legacy = path.join(root, '..', 'wp-content', 'themes', 'skysend', 'assets', 'images');

const webp = [
  [path.join(legacy, 'banner-providers-20260914.webp'), 'banner-providers-20260924.webp', 1440, 78],
  [path.join(legacy, 'banner-finance-20260921.png'), 'banner-finance-20260924.webp', 1440, 80],
  [path.join(legacy, 'banner-allvend-diagram-20260921.png'), 'banner-allvend-diagram-20260924.webp', 1440, 80],
  [path.join(assets, 'allvend-ui-official-source.png'), 'allvend-ui-official-20260924.webp', 1080, 80],
  [path.join(assets, 'server-cluster-source.png'), 'server-cluster-20260924.webp', 1120, 80],
  [path.join(legacy, 'partner-gateways-no-xml-20260916.webp'), 'partner-gateways-no-xml-20260924.webp', 400, 78],
];

for (const [input, name, width, quality] of webp) {
  const output = path.join(assets, name);
  await sharp(input).resize({ width, withoutEnlargement: true }).webp({ quality, effort: 6 }).toFile(output);
  const meta = await sharp(output).metadata();
  console.log(`${name}: ${meta.width}x${meta.height}, ${fs.statSync(output).size} bytes`);
}

const icon = path.join(assets, 'site-icon-20260924.png');
await sharp(path.join(assets, 'site-icon-source.png'))
  .resize(512, 512, { fit: 'contain' })
  .png({ compressionLevel: 9, palette: true, quality: 95 })
  .toFile(icon);
console.log(`site-icon-20260924.png: ${fs.statSync(icon).size} bytes`);
