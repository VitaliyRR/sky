/**
 * Reproducible, brand-neutral ALLVEND pictograms.
 * Run from the repository root: node wordpress/native-blocks/assets/allvend-icons-20260925/build-icons.mjs
 * The nine SVG sources and 96x96 transparent PNGs share the same blue,
 * line weight, rounded ends, and 64x64 drawing area.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import sharp from 'sharp';
import {
  ReceiptText,
  SquareMousePointer,
  MonitorPlay,
  QrCode,
  Fingerprint,
  Settings,
  Terminal,
  ShoppingBag,
} from 'lucide-react';

const directory = path.dirname(fileURLToPath(import.meta.url));
const blue = '#0B63F6';
const icons = [
  ['payment-services', 'Оплата услуг', ReceiptText],
  ['self-service', 'Самообслуживание', SquareMousePointer],
  ['advertising-video', 'Трансляция рекламы', MonitorPlay],
  ['cashless-pos', 'Безналичная оплата', null],
  ['qr-scan', 'Считывание QR', QrCode],
  ['biometric', 'Биометрическая идентификация', Fingerprint],
  ['interface-settings', 'Настройка интерфейса', Settings],
  ['remote-console', 'Удалённое управление', Terminal],
  ['goods-sales', 'Продажа товаров', ShoppingBag],
];

function content(component, name) {
  if (name === 'cashless-pos') {
    // A handheld POS terminal with a screen and keypad, not a bank card.
    return '<rect x="6" y="2" width="12" height="20" rx="2"/>' +
      '<rect x="8.5" y="5" width="7" height="5" rx=".5"/>' +
      '<path d="M9 13.5h.01M12 13.5h.01M15 13.5h.01M9 16.5h.01M12 16.5h.01M15 16.5h.01M10 19.5h4" stroke-width="2.8"/>';
  }
  const rendered = renderToStaticMarkup(React.createElement(component, {
    color: blue,
    strokeWidth: 1.8,
    'aria-hidden': true,
  }));
  return rendered.replace(/^<svg[^>]*>/, '').replace(/<\/svg>$/, '');
}

function svg(component, name) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96" role="img" aria-label="${name}">` +
    `<svg x="16" y="16" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="${blue}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">` +
    content(component, name) + '</svg></svg>\n';
}

const contact = [];
for (const [name, label, component] of icons) {
  const vector = svg(component, name);
  fs.writeFileSync(path.join(directory, `${name}.svg`), vector);
  const bitmap = await sharp(Buffer.from(vector)).png().toBuffer();
  const metadata = await sharp(bitmap).metadata();
  if (metadata.width !== 96 || metadata.height !== 96 || metadata.channels !== 4) {
    throw new Error(`Unexpected output dimensions or transparency for ${name}`);
  }
  fs.writeFileSync(path.join(directory, `${name}.png`), bitmap);
  contact.push({ input: bitmap, left: 36 + (contact.length % 3) * 160, top: 26 + Math.floor(contact.length / 3) * 150 });
  console.log(`${name}.png\t${label}\t${bitmap.length} bytes`);
}

await sharp({ create: { width: 480, height: 450, channels: 4, background: '#ffffff' } })
  .composite(contact)
  .png()
  .toFile(path.join(directory, 'contact-sheet.png'));
