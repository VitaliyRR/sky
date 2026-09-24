/** Crop the authentic FastSYS boot frame from the official ALLVEND manual. */
import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const base = path.join(import.meta.dirname, 'assets', 'revision-20260924');
const source = path.join(base, 'fastsys-boot-official-source.png');
const output = path.join(base, 'fastsys-boot-official-20260924.webp');
const meta = await sharp(source).metadata();
if (meta.width !== 1920 || meta.height !== 1080) {
  throw new Error(`Unexpected official screenshot size: ${meta.width}x${meta.height}`);
}

// Only the black VMware display is retained; no OS/UI pixels are reconstructed.
await sharp(source)
  .extract({ left: 558, top: 128, width: 1018, height: 764 })
  .resize({ width: 900, withoutEnlargement: true })
  .webp({ quality: 82, effort: 6 })
  .toFile(output);

const result = await sharp(output).metadata();
console.log(`${path.basename(output)}: ${result.width}x${result.height}, ${fs.statSync(output).size} bytes`);
