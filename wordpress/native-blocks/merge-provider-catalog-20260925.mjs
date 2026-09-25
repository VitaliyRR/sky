// Restore the original SkySend provider categories and entries, then add a
// deterministic subset of the owner-supplied 5,000-logo archive.
//
// The full converted archive is pinned to the commit that introduced it so
// this script stays reproducible after the two current catalog files change.
// Usage: node wordpress/native-blocks/merge-provider-catalog-20260925.mjs

import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = dirname(fileURLToPath(import.meta.url));
const repoRoot = resolve(scriptDir, '../..');
const originalPath = resolve(repoRoot, 'tmp/provider-work-20260925/skysend-provider-catalog/catalog.json');
const outputPaths = [
  resolve(scriptDir, 'assets/provider-catalog-20260925/catalog.json'),
  resolve(repoRoot, 'wordpress/wp-content/mu-plugins/skysend-provider-catalog/catalog.json'),
];
const sourceRevision = 'c1f24843ee8cfdefa105df19ce0a93475ff99f29';
const sourceGitPath = 'wordpress/native-blocks/assets/provider-catalog-20260925/catalog.json';
const expectedArchiveHash = 'df2c5280fb1786a84607ec938c2ba33a19ba66616eba83521699a3ed640f1c72';
const targetAdditionalCount = 2_000;

// The local backup is authoritative for the initial merge. A later checkout
// can reconstruct those same 600 records from the already generated catalog.
const original = existsSync(originalPath)
  ? JSON.parse(readFileSync(originalPath, 'utf8'))
  : (() => {
      const prior = JSON.parse(readFileSync(outputPaths[0], 'utf8'));
      assert(prior.source?.originalCount === 600, 'Original backup not found and output has no original baseline');
      return {
        source: prior.source.original,
        categories: Object.fromEntries(Object.entries(prior.categories).map(([key, category]) => [key, {
          label: category.label,
          items: category.items.filter(item => !item.id.startsWith('archive-')),
        }])),
      };
    })();
const archive = JSON.parse(execFileSync('git', ['show', `${sourceRevision}:${sourceGitPath}`], {
  cwd: repoRoot,
  encoding: 'utf8',
  maxBuffer: 16 * 1024 * 1024,
}));
const originalCategoryKeys = [
  'mobile', 'internet', 'tv', 'banks', 'games', 'utilities',
  'auto', 'government', 'other', 'transport',
];
const primaryArchiveCategories = [
  'communications-internet',
  'banking-payments-insurance',
  'utilities-energy',
  'transport-logistics',
  'government-services',
  'retail-food-hospitality',
  'education-science',
  'medicine-health',
  'manufacturing-goods',
  'other-organizations-services',
  'culture-leisure',
  'media-digital',
];

function assert(condition, message) {
  if (!condition) throw new Error(message);
}

function normalizedName(name) {
  return name.toLocaleLowerCase('ru-RU')
    .replaceAll('ё', 'е')
    .normalize('NFKC')
    .replace(/[^\p{L}\p{N}]/gu, '');
}

const archiveHash = archive.source?.sha256?.toLowerCase();
assert(archiveHash === expectedArchiveHash, 'Unexpected owner archive identity');
assert(Object.values(archive.categories).reduce((sum, category) => sum + category.items.length, 0) === 5_000,
  'Expected exactly 5,000 records in the source archive');
assert(JSON.stringify(Object.keys(original.categories)) === JSON.stringify(originalCategoryKeys),
  'Original SkySend category order changed');
assert(Object.values(original.categories).reduce((sum, category) => sum + category.items.length, 0) === 600,
  'Expected exactly 600 original SkySend records');

const originalItems = Object.values(original.categories).flatMap(category => category.items);
const usedNames = new Set(originalItems.map(item => normalizedName(item.name)));
const usedIds = new Set(originalItems.map(item => item.id));
const selected = [];

function include(item, sourceCategory) {
  assert(typeof item.name === 'string' && item.name.trim(), `Missing name in ${sourceCategory}`);
  assert(/^archive-\d{4}$/.test(item.id), `Unexpected archive ID: ${item.id}`);
  assert(/^\/wp-content\/uploads\/skysend-providers-20260925\/\d{4}\.webp$/.test(item.logo),
    `Unexpected archive logo: ${item.logo}`);
  const key = normalizedName(item.name);
  if (usedNames.has(key)) return false;
  assert(!usedIds.has(item.id), `Duplicate ID: ${item.id}`);
  usedNames.add(key);
  usedIds.add(item.id);
  selected.push({ item, sourceCategory });
  return true;
}

for (const category of primaryArchiveCategories) {
  assert(archive.categories[category], `Missing archive category: ${category}`);
  for (const item of archive.categories[category].items) include(item, category);
}

// Evenly sample the remaining records from the broad digital-services source,
// avoiding a display dominated by names from one alphabetical segment.
const digital = archive.categories['digital-services']?.items ?? [];
assert(selected.length === 1_805, `Unexpected primary selection count: ${selected.length}`);
const remaining = targetAdditionalCount - selected.length;
assert(digital.length >= remaining, 'Not enough digital-service records');
for (let index = 0; index < remaining; index += 1) {
  const sourceIndex = Math.floor((index + 0.5) * digital.length / remaining);
  assert(include(digital[sourceIndex], 'digital-services'), 'Duplicate selected digital-service name');
}
assert(selected.length === targetAdditionalCount, 'Additional provider target not met');

const mobileOperatorNames = new Set([
  'Вымпел-Коммуникации', 'К-Телеком', 'Мегафон', 'МТС', 'Скай Линк',
  'T2', 'Tele2-Коми', 'Yota',
].map(normalizedName));
const roadAndCarNames = new Set([
  'Автодор', 'Делимобиль', 'Максим', 'Яндекс Такси', 'BelkaCar', 'InDrive', 'Wheely',
].map(normalizedName));
const tvPattern = /канал|телевиз|телерадио|(?:^|[\s-])(?:тв|tv)(?:$|[\s-])|нтв|тнт|стс|пятниц/i;
const gamesNames = new Set(['Страна игр'].map(normalizedName));
const utilitiesPattern = /водоканал|энерг[оия]|электро|газпром промгаз|тепло|коммун|водоснаб|водосток|сбыт|сетев/i;

function destinationFor({ item, sourceCategory }) {
  switch (sourceCategory) {
    case 'communications-internet':
      return mobileOperatorNames.has(normalizedName(item.name)) ? 'mobile' : 'internet';
    case 'banking-payments-insurance': return 'banks';
    case 'utilities-energy':
      return utilitiesPattern.test(item.name) ? 'utilities' : 'other';
    case 'transport-logistics':
      return roadAndCarNames.has(normalizedName(item.name)) ? 'auto' : 'transport';
    case 'government-services': return 'government';
    case 'media-digital':
      if (gamesNames.has(normalizedName(item.name))) return 'games';
      if (tvPattern.test(item.name)) return 'tv';
      return 'other';
    default: return 'other';
  }
}

// JSON round trip deliberately preserves every original item and label while
// ensuring no object reference is shared with the input object.
const categories = structuredClone(original.categories);
const additionsByCategory = Object.fromEntries(originalCategoryKeys.map(key => [key, 0]));
for (const entry of selected) {
  const destination = destinationFor(entry);
  assert(categories[destination], `Unknown destination: ${destination}`);
  categories[destination].items.push(structuredClone(entry.item));
  additionsByCategory[destination] += 1;
}

for (const key of originalCategoryKeys) {
  const before = original.categories[key].items;
  const after = categories[key].items.slice(0, before.length);
  assert(JSON.stringify(before) === JSON.stringify(after), `Original records modified in ${key}`);
}

const result = {
  version: '2026-09-25-original-categories-plus-2000',
  source: {
    original: original.source,
    archive: archive.source,
    selection: 'All 1,805 non-duplicate records from 12 mapped source categories, plus 195 evenly sampled digital-services records',
    originalCount: originalItems.length,
    additionalCount: selected.length,
  },
  categories,
};
const output = `${JSON.stringify(result, null, 2)}\n`;
for (const outputPath of outputPaths) writeFileSync(outputPath, output);

const digest = createHash('sha256').update(output).digest('hex');
console.log(`Wrote ${outputPaths.length} identical catalogs, SHA-256 ${digest}`);
console.log(`Preserved ${originalItems.length} original entries; added ${selected.length} archive entries`);
for (const key of originalCategoryKeys) {
  console.log(`${key}: ${original.categories[key].items.length} + ${additionsByCategory[key]} = ${categories[key].items.length}`);
}
