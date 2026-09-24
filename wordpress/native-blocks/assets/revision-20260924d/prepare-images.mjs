/** Lossy delivery variants from the two imagegen-edited PNG masters. */
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { stat } from 'node:fs/promises';
import sharp from 'sharp';

const dir = path.dirname(fileURLToPath(import.meta.url));
const assets = [
  ['banner-finance-income-20260924-source.png', 'banner-finance-income-20260924.webp', 1440, 720],
  ['partner-gateways-xml-20260924-source.png', 'partner-gateways-xml-20260924.webp', 400, 324],
];
for (const [source, target, width, height] of assets) {
  await sharp(path.join(dir, source))
    .resize(width, height, { fit: 'cover', position: 'centre' })
    .webp({ quality: 84, effort: 6 })
    .toFile(path.join(dir, target));
  const { size } = await stat(path.join(dir, target));
  console.log(`${target}: ${width}×${height}, ${size} bytes`);
}
