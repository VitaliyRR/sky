/** Optimized delivery variant of the approved finance banner edit. */
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { stat } from 'node:fs/promises';
import sharp from 'sharp';

const dir = path.dirname(fileURLToPath(import.meta.url));
const file = 'banner-finance-bag-income-20260924.webp';
await sharp(path.join(dir, 'banner-finance-bag-income-20260924-source.png'))
  .resize(1440, 720, { fit: 'cover', position: 'centre' })
  .webp({ quality: 84, effort: 6 })
  .toFile(path.join(dir, file));
const { size } = await stat(path.join(dir, file));
console.log(`${file}: 1440×720, ${size} bytes`);
