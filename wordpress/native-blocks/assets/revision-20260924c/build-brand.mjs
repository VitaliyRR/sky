import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const here = path.dirname(fileURLToPath(import.meta.url));
const blue = '#0B63F6';
const navy = '#071426';

// The icon is deliberately typographic: no swoosh, old diagonal stroke or shadow.
// Keep all three assets derived from this same blue square and white S.
const mark = (x, y, size) => `<rect x="${x}" y="${y}" width="${size}" height="${size}" rx="${Math.round(size * 0.19)}" fill="${blue}"/>`;

function wordmark(textColor) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="430" height="120" viewBox="0 0 430 120" role="img" aria-label="SkySend">
  ${mark(8, 12, 96)}
  <text x="56" y="88" text-anchor="middle" fill="#FFFFFF" font-family="Arial, Segoe UI, sans-serif" font-size="78" font-weight="700">S</text>
  <text x="128" y="83" fill="${textColor}" font-family="Arial, Segoe UI, sans-serif" font-size="68" font-weight="700" letter-spacing="-2.2">Sky</text>
  <text x="249" y="83" fill="${textColor === '#FFFFFF' ? '#FFFFFF' : blue}" font-family="Arial, Segoe UI, sans-serif" font-size="68" font-weight="700" letter-spacing="-2.2">Send</text>
</svg>`;
}

const favicon = `<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512" role="img" aria-label="SkySend">
  ${mark(16, 16, 480)}
  <text x="256" y="379" text-anchor="middle" fill="#FFFFFF" font-family="Arial, Segoe UI, sans-serif" font-size="392" font-weight="700">S</text>
</svg>`;

const assets = [
  ['skysend-wordmark-light', wordmark(navy), 860],
  ['skysend-wordmark-dark', wordmark('#FFFFFF'), 860],
  ['skysend-site-icon', favicon, 512],
];

for (const [name, svg, width] of assets) {
  const svgPath = path.join(here, `${name}.svg`);
  const pngPath = path.join(here, `${name}.png`);
  await fs.writeFile(svgPath, `${svg}\n`, 'utf8');
  await sharp(Buffer.from(svg)).resize({ width }).png({ compressionLevel: 9 }).toFile(pngPath);
  const info = await sharp(pngPath).metadata();
  const stat = await fs.stat(pngPath);
  console.log(`${name}: ${info.width}×${info.height}, ${stat.size} bytes`);
}
