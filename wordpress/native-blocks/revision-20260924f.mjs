/** Guarded revision on the 24 September VM state. Keep all Gutenberg content editable. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { registerCarouselBlocks } from './carousel.mjs';

const base = path.dirname(fileURLToPath(import.meta.url));
const sha = value => crypto.createHash('sha256').update(value).digest('hex');
const expected = {
  page: '8a6b5709c08a7a8b6c37a9f1471bd8add7c40989dbb5c4ba0f6319ef647da53b',
  header: '627dda41580bd5d5825adf64e0713f982fbf8a1073c5f6feff390ce25317422f',
  footer: '01c7d3b23253879a0c56e9cbdd279180a4bd8eeeebc9a7d0f475fe53304a4fdc',
  styles: 'c88b0a4196fe8b1b6d5088dbf14496c11ee6b4f6221dd2a31d70f7838c23dd95',
};
const names = { page: 'page.html', header: 'header.html', footer: 'footer.html', styles: 'styles.json' };
const mediaNames = {
  logoLight: 'skysend-wordmark-light-20260924f.png',
  logoDark: 'skysend-wordmark-dark-20260924f.png',
  icon: 'skysend-site-icon-20260924f.png',
  cluster: 'cluster-official-20260924f-scaled.jpeg',
};
const walk = (tree, fn) => tree.forEach(block => { fn(block); walk(block.innerBlocks, fn); });
const find = (tree, test, label) => {
  const matches = [];
  walk(tree, block => { if (test(block)) matches.push(block); });
  if (matches.length !== 1) throw new Error(`Expected one ${label}, found ${matches.length}`);
  return matches[0];
};

export async function revision20260924f(w, origin, mediaFile, outputDir) {
  if (!mediaFile || !outputDir) throw new Error('Usage: build.mjs --revision-20260924f media-ids.json output-dir');
  await registerCarouselBlocks(w, origin);
  const ids = JSON.parse(fs.readFileSync(mediaFile, 'utf8'));
  for (const file of Object.values(mediaNames)) {
    if (!Number.isInteger(ids[file])) throw new Error('Missing imported media ID: ' + file);
  }
  const sources = Object.fromEntries(Object.entries(names).map(([name, file]) => [name, fs.readFileSync(path.join(base, file), 'utf8')]));
  for (const [name, source] of Object.entries(sources)) {
    if (sha(source) !== expected[name]) throw new Error(`Stale ${name}: ${sha(source)}`);
  }
  const trees = Object.fromEntries(['page', 'header', 'footer'].map(name => [name, w.wp.blocks.parse(sources[name])]));
  for (const [name, tree] of Object.entries(trees)) {
    walk(tree, block => { if (!block.isValid) throw new Error('Invalid source ' + name + ': ' + block.name); });
  }
  const b = (name, attrs = {}, children = []) => w.wp.blocks.createBlock('core/' + name, attrs, children);
  const mediaUrl = file => `${origin}/wp-content/uploads/2026/09/${file}`;
  const updateImage = (block, id, file, alt, extra = {}) => {
    Object.assign(block.attributes, { id, url: mediaUrl(file), alt, sizeSlug: 'full', linkDestination: 'none', ...extra });
  };

  // Section heading only; leave the navigation link to #participants intact.
  const partners = find(trees.page, block => block.attributes.anchor === 'participants', 'partner section');
  const partnerInner = partners.innerBlocks[0];
  const headingIndex = partnerInner.innerBlocks.findIndex(block => block.name === 'core/heading' &&
    String(block.attributes.content).trim() === 'Партнерам');
  if (headingIndex < 0) throw new Error('Partner heading missing');
  partnerInner.innerBlocks.splice(headingIndex, 1);

  // The previous ALLVEND section visual is media attachment 13 on this VM.
  const allvend = find(trees.page, block => block.attributes.anchor === 'allvend', 'ALLVEND section');
  const allvendImage = find([allvend], block => block.name === 'core/image' &&
    String(block.attributes.url).endsWith('/allvend-ui-official-20260924.webp'), 'ALLVEND visual');
  updateImage(allvendImage, 13, 'banner-allvend-20260914.webp',
    'Устройства самообслуживания с ПО ALLVEND',
    { width: '100%', aspectRatio: '3/2', scale: 'cover', focalPoint: { x: 1, y: 0.5 } });

  const speed = find(trees.page, block => block.attributes.anchor === 'capabilities', 'processing section');
  const speedImage = find([speed], block => block.name === 'core/image' &&
    String(block.attributes.url).endsWith('/server-cluster-20260924.webp'), 'processing image');
  updateImage(speedImage, ids[mediaNames.cluster], mediaNames.cluster,
    'Схема кластерной процессинговой структуры SkySend', {
      width: '100%', scale: 'contain',
      linkDestination: 'custom', href: mediaUrl(mediaNames.cluster),
      linkTarget: '_blank', rel: 'noopener noreferrer',
      caption: 'Схема кластера SkySend — открыть в полном размере',
    });
  delete speedImage.attributes.aspectRatio;
  const speedList = find([speed], block => block.name === 'core/list' &&
    block.innerBlocks.some(item => String(item.attributes.content).startsWith('Распределённая архитектура')), 'processing benefits');
  const archIndex = speedList.innerBlocks.findIndex(item =>
    String(item.attributes.content).startsWith('Распределённая архитектура'));
  speedList.innerBlocks.splice(archIndex + 1, 0,
    b('list-item', { content: 'Система извлекает максимум производительности из «Железа»' }));
  const claim = find([speed], block => block.name === 'core/paragraph' &&
    String(block.attributes.content).includes('Система извлекает максимум производительности из «Железа»'), 'standalone processing claim');
  const speedRight = find([speed], block => block.name === 'core/columns', 'processing columns').innerBlocks[1];
  const claimIndex = speedRight.innerBlocks.indexOf(claim);
  if (claimIndex < 0) throw new Error('Standalone processing claim not in right column');
  speedRight.innerBlocks.splice(claimIndex, 1);

  // Fill the width and height alongside the ten category buttons.
  const providers = find(trees.page, block => block.attributes.anchor === 'providers', 'provider section');
  const panels = find([providers], block => block.name === 'core/tab-panels', 'provider panels');
  if (panels.innerBlocks.length !== 10) throw new Error('Expected ten provider categories');
  let totalCards = 0;
  for (const panel of panels.innerBlocks) {
    const carousel = find([panel], block => block.name === 'cb/carousel-v2', panel.attributes.label + ' carousel');
    const cards = carousel.innerBlocks.flatMap(slide => {
      const grid = find([slide], block => block.name === 'core/group' &&
        String(block.attributes.className || '').includes('sky-provider-grid'), 'provider grid');
      return grid.innerBlocks;
    });
    totalCards += cards.length;
    const slideCount = Math.ceil(cards.length / 9);
    if (!slideCount) throw new Error('Empty provider category: ' + panel.attributes.label);
    const nextSlides = [];
    let cursor = 0;
    for (let index = 0; index < slideCount; index++) {
      const remainingSlides = slideCount - index;
      const take = Math.ceil((cards.length - cursor) / remainingSlides);
      const groupCards = cards.slice(cursor, cursor + take);
      cursor += take;
      for (const card of groupCards) {
        card.attributes.style.dimensions.minHeight = '150px';
        const logo = find([card], block => block.name === 'core/image', 'provider logo');
        logo.attributes.height = '80px';
        const label = card.innerBlocks.find(block => block.name === 'core/paragraph');
        if (label) label.attributes.style.typography.fontSize = '14px';
      }
      const grid = b('group', {
        className: 'sky-provider-grid',
        layout: { type: 'grid', minimumColumnWidth: '250px' },
        style: { dimensions: { minHeight: '500px' }, spacing: { blockGap: '12px' } },
      }, groupCards);
      nextSlides.push(w.wp.blocks.createBlock('cb/slide-v2', {
        metadata: { name: `${panel.attributes.label}: ${index + 1}` },
      }, [grid]));
    }
    if (cursor !== cards.length) throw new Error('Provider pagination dropped cards');
    carousel.innerBlocks = nextSlides;
  }
  if (totalCards !== 279) throw new Error('Provider catalogue changed: ' + totalCards);

  // One brand system for the header, footer, favicon, and organization metadata.
  const headerLogo = find(trees.header, block => block.name === 'core/image' &&
    String(block.attributes.url).endsWith('/skysend-wordmark-light.png'), 'header logo');
  updateImage(headerLogo, ids[mediaNames.logoLight], mediaNames.logoLight, 'SkySend', { width: '160px' });
  const footerLogo = find(trees.footer, block => block.name === 'core/image' &&
    String(block.attributes.url).endsWith('/skysend-wordmark-dark.png'), 'footer logo');
  updateImage(footerLogo, ids[mediaNames.logoDark], mediaNames.logoDark, 'SkySend', { width: '180px' });
  const social = find(trees.footer, block => block.name === 'core/social-links', 'social links');
  social.attributes.size = 'has-normal-icon-size';
  social.attributes.style.spacing.blockGap = '12px';

  const styles = JSON.parse(sources.styles);
  styles.styles.css += '\n' + [
    '#provider-categories .sky-provider-grid { min-height:500px; grid-auto-rows:1fr; align-content:stretch; }',
    '#provider-categories .sky-provider-grid > .wp-block-group { min-height:150px; }',
    '@media (max-width:782px) { #provider-categories .sky-provider-grid { min-height:0 !important; grid-auto-rows:auto; grid-template-columns:repeat(2,minmax(0,1fr)) !important; } }',
    '#contacts .wp-block-social-links .wp-social-link a { width:38px; height:38px; display:flex; align-items:center; justify-content:center; }',
    '#contacts .wp-block-social-links .wp-social-link svg { width:26px !important; height:26px !important; }',
  ].join('\n');
  fs.mkdirSync(outputDir, { recursive: true });
  const output = {};
  const report = {};
  for (const [name, tree] of Object.entries(trees)) {
    output[name] = w.wp.blocks.serialize(tree);
    let count = 0;
    walk(w.wp.blocks.parse(output[name]), block => {
      count++;
      if (!block.isValid || (!block.name.startsWith('core/') &&
        !['cb/carousel-v2', 'cb/slide-v2'].includes(block.name))) {
        throw new Error('Invalid generated ' + name + ' block: ' + block.name);
      }
    });
    report[name] = { id: { page: 67, header: 68, footer: 69 }[name],
      beforeHash: sha(sources[name]), afterHash: sha(output[name]), blocks: count };
    fs.writeFileSync(path.join(outputDir, names[name]), output[name]);
  }
  output.styles = JSON.stringify(styles, null, 2) + '\n';
  report.styles = { id: 66, beforeHash: sha(sources.styles), afterHash: sha(output.styles) };
  fs.writeFileSync(path.join(outputDir, names.styles), output.styles);
  const manifest = {
    revision: '2026-09-24f', origin, categories: panels.innerBlocks.length,
    providers: totalCards, previousIconId: 207, mediaNames, mediaIds: ids, report,
  };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify({ revision: manifest.revision, categories: manifest.categories,
    providers: manifest.providers, report }, null, 2));
}
