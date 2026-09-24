/** Compact, consistent pictograms for the editable ALLVEND feature grid. */
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { Film, CreditCard, QrCode, Fingerprint, Settings2, SquareTerminal, FileText } from 'lucide-react';
import sharp from 'sharp';

const assets = path.join(path.dirname(fileURLToPath(import.meta.url)), 'assets', 'revision-20260924', 'icons');
fs.mkdirSync(assets, { recursive: true });
const icons = {
  'video-20260924.png': Film,
  'pos-20260924.png': CreditCard,
  'qr-20260924.png': QrCode,
  'biometric-20260924.png': Fingerprint,
  'settings-20260924.png': Settings2,
  'remote-20260924.png': SquareTerminal,
  'pdf-20260924.png': FileText,
};
for (const [file, Icon] of Object.entries(icons)) {
  const svg = renderToStaticMarkup(createElement(Icon, { width: 64, height: 64, stroke: '#0b63f6', strokeWidth: 1.7, fill: 'none' }));
  await sharp(Buffer.from(svg)).resize(80, 80).png({ compressionLevel: 9 }).toFile(path.join(assets, file));
  console.log(file);
}
