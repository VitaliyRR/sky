/** Import selected legacy SkySend catalogue logos as small, local, lossless PNGs. */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(scriptDir, '../..');
const cataloguePath = path.join(scriptDir, 'providers-expanded-20260924.json');
const catalogue = JSON.parse(fs.readFileSync(cataloguePath, 'utf8'));
const targetDir = path.join(scriptDir, 'assets/revision-20260924/providers');
const pending = catalogue.categories.flatMap(category => category.items).filter(item => !item.image);

if (pending.length !== 66 || new Set(pending.map(item => item.id)).size !== 66) {
  throw new Error(`Unexpected set of new logos: ${pending.length}`);
}
fs.mkdirSync(targetDir, { recursive: true });

const results = [];
for (let offset = 0; offset < pending.length; offset += 8) {
  await Promise.all(pending.slice(offset, offset + 8).map(async item => {
    const filename = `provider-${item.id}.png`;
    const fullPath = path.join(targetDir, filename);
    const mediaFile = path.relative(repoRoot, fullPath).replaceAll(path.sep, '/');
    if (fs.existsSync(fullPath)) {
      // A rerun must not silently overwrite an asset with a different source.
      const existing = await sharp(fullPath).metadata();
      if (existing.format !== 'png' || !existing.width || !existing.height) {
        throw new Error(`Invalid existing logo ${mediaFile}`);
      }
      item.mediaFile = mediaFile;
      results.push({ id: item.id, bytes: fs.statSync(fullPath).size, reused: true });
      return;
    }

    const url = catalogue.logoUrlTemplate.replace('{id}', String(item.id));
    const response = await fetch(url, { signal: AbortSignal.timeout(15000) });
    if (!response.ok || !response.headers.get('content-type')?.startsWith('image/png')) {
      throw new Error(`Could not fetch PNG logo ${item.id}: ${response.status}`);
    }
    const original = Buffer.from(await response.arrayBuffer());
    if (!original.subarray(0, 8).equals(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]))) {
      throw new Error(`Invalid PNG signature for ${item.id}`);
    }
    const metadata = await sharp(original).metadata();
    if (metadata.format !== 'png' || !metadata.width || !metadata.height || metadata.width > 4000 || metadata.height > 4000) {
      throw new Error(`Invalid dimensions for ${item.id}`);
    }
    // Lossless compression only. Some source PNGs are already smaller; retain those byte-for-byte.
    const compressed = await sharp(original).png({ compressionLevel: 9, effort: 10, palette: false }).toBuffer();
    const output = compressed.length < original.length ? compressed : original;
    if (output.length > 100000) throw new Error(`Logo exceeds 100 KB: ${item.id}`);
    fs.writeFileSync(fullPath, output, { flag: 'wx' });
    item.mediaFile = mediaFile;
    results.push({ id: item.id, bytes: output.length, width: metadata.width, height: metadata.height });
  }));
}

fs.writeFileSync(cataloguePath, JSON.stringify(catalogue, null, 2) + '\n');
results.sort((a, b) => a.id - b.id);
console.log(JSON.stringify({ count: results.length, totalBytes: results.reduce((sum, item) => sum + item.bytes, 0), maxBytes: Math.max(...results.map(item => item.bytes)), reused: results.filter(item => item.reused).length }, null, 2));
