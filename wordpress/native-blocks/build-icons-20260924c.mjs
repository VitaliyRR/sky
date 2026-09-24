/** Replacement ALLVEND feature icons: payment POS reader and interface gear. */
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const destination = path.join(
  path.dirname(fileURLToPath(import.meta.url)),
  'assets',
  'revision-20260924c',
);
fs.mkdirSync(destination, { recursive: true });

const icons = {
  'pos-terminal-20260924c.png': `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none" stroke="#0b63f6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
    <path d="M22 7h36a5 5 0 0 1 5 4.5l5 54a5 5 0 0 1-5 5.5H17a5 5 0 0 1-5-5.5l5-54A5 5 0 0 1 22 7Z"/>
    <rect x="24" y="16" width="32" height="19" rx="2"/>
    <path d="M29 24h14"/>
    <circle cx="29" cy="45" r="1.3"/><circle cx="40" cy="45" r="1.3"/><circle cx="51" cy="45" r="1.3"/>
    <circle cx="29" cy="53" r="1.3"/><circle cx="40" cy="53" r="1.3"/><circle cx="51" cy="53" r="1.3"/>
    <path d="M26 63h28"/>
  </svg>`,
  'gear-20260924c.png': `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none" stroke="#0b63f6" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M35 6h10l1.6 8.3c1.9.6 3.7 1.4 5.3 2.2l7-4.7 7.1 7.1-4.7 7c.9 1.6 1.6 3.4 2.2 5.3L72 33v10l-8.5 1.8a24 24 0 0 1-2.2 5.3l4.7 7-7.1 7.1-7-4.7a24 24 0 0 1-5.3 2.2L45 70H35l-1.6-8.3a24 24 0 0 1-5.3-2.2l-7 4.7-7.1-7.1 4.7-7a24 24 0 0 1-2.2-5.3L8 43V33l8.5-1.8c.6-1.9 1.3-3.7 2.2-5.3l-4.7-7 7.1-7.1 7 4.7c1.6-.9 3.4-1.6 5.3-2.2L35 6Z"/>
    <circle cx="40" cy="38" r="10"/>
  </svg>`,
};

for (const [filename, svg] of Object.entries(icons)) {
  await sharp(Buffer.from(svg), { density: 384 })
    .resize(80, 80)
    .png({ compressionLevel: 9 })
    .toFile(path.join(destination, filename));
  console.log(filename);
}
