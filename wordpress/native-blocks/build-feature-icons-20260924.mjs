/** Compact, consistent pictograms for the editable ALLVEND feature grid. */
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { Film, CreditCard, QrCode, Fingerprint, Settings2, SquareTerminal, FileText, FileArchive } from 'lucide-react';
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
  'zip-20260924.png': FileArchive,
};
for (const [file, Icon] of Object.entries(icons)) {
  const svg = renderToStaticMarkup(createElement(Icon, { width: 64, height: 64, stroke: '#0b63f6', strokeWidth: 1.7, fill: 'none' }));
  await sharp(Buffer.from(svg)).resize(80, 80).png({ compressionLevel: 9 }).toFile(path.join(assets, file));
  console.log(file);
}
// An upright self-service payment kiosk, not a bank-card/POS pictogram.
const kioskSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="#0b63f6" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 5h22a3 3 0 0 1 3 3v45H18V8a3 3 0 0 1 3-3Z"/><rect x="23" y="11" width="18" height="22" rx="1.5"/><path d="M25 39h14M27 45h10M16 53h32v6H16z"/></svg>`;
await sharp(Buffer.from(kioskSvg)).resize(80, 80).png({ compressionLevel: 9 }).toFile(path.join(assets, 'payment-kiosk-20260924.png'));
console.log('payment-kiosk-20260924.png');
