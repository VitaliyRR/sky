/** One-time native block styling repair. Starts from a fresh WXR, never old copy. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

export async function repair(w, input, output) {
  if (!input || !output) throw new Error('Usage: build.mjs --repair current.xml output-directory');
  const xml = new w.DOMParser().parseFromString(fs.readFileSync(input, 'utf8'), 'text/xml');
  if (xml.querySelector('parsererror')) throw new Error('Invalid WordPress export');
  const wpns = 'http://wordpress.org/export/1.2/';
  const contentns = 'http://purl.org/rss/1.0/modules/content/';
  const posts = new Map([...xml.querySelectorAll('item')].map(item => {
    const field = key => item.getElementsByTagNameNS(wpns, key)[0]?.textContent;
    const id = Number(field('post_id'));
    return [id, { id, type: field('post_type'), content: item.getElementsByTagNameNS(contentns, 'encoded')[0]?.textContent }];
  }));
  const blue = '#0b63f6', ink = '#071426', line = '#dbe5f1';
  const merge = (a, b) => {
    const result = { ...a };
    for (const [key, value] of Object.entries(b)) result[key] = value && typeof value === 'object' && !Array.isArray(value) ? merge(a?.[key] || {}, value) : value;
    return result;
  };
  const attrs = (block, values) => { block.attributes = merge(block.attributes, values); return block; };
  const walk = (blocks, fn) => blocks.forEach(block => { fn(block); walk(block.innerBlocks, fn); });
  const blocks = id => {
    const post = posts.get(id);
    if (!post?.content) throw new Error('Missing exported post ' + id);
    const parsed = w.wp.blocks.parse(post.content);
    walk(parsed, block => { if (!block.isValid) throw new Error('Input is invalid: ' + id + ' ' + block.name); });
    return parsed;
  };
  const create = (name, attributes, children) => w.wp.blocks.createBlock('core/' + name, attributes, children);
  const textCenter = block => attrs(block, { style: { typography: { textAlign: 'center' } } });
  const stack = (block, gap = '12px', verticalAlignment = 'center') => attrs(block, { layout: { type: 'flex', orientation: 'vertical', justifyContent: 'center', verticalAlignment, flexWrap: 'nowrap' }, style: { spacing: { blockGap: gap } } });
  const page = blocks(67), footer = blocks(69), header = blocks(68);
  const before = { page: semantic(page), footer: semantic(footer), header: semantic(header) };
  const sections = ['participants', 'allvend', 'capabilities', 'security', 'fastsys', 'software', 'providers'];
  const section = id => {
    const found = page.find(block => block.attributes.anchor === id);
    if (!found || found.innerBlocks.length !== 1) throw new Error('Unexpected section structure: ' + id);
    return found;
  };
  // Core Stack settings restore vertical centering while letting mobile content grow.
  for (const id of sections) {
    const outer = section(id);
    attrs(outer, { layout: { type: 'flex', orientation: 'vertical', justifyContent: 'stretch', verticalAlignment: 'center', flexWrap: 'nowrap' }, style: { dimensions: { minHeight: '600px' }, border: { bottom: { color: line, width: '1px' } } } });
    attrs(outer.innerBlocks[0], { layout: { type: 'constrained', contentSize: '1200px', wideSize: '1200px' } });
  }
  const hero = page.find(block => block.attributes.metadata?.name === 'Три баннера');
  if (!hero) throw new Error('Missing hero');
  attrs(hero, { layout: { contentSize: '1200px', wideSize: '1200px' } });
  walk(hero.innerBlocks, block => {
    if (block.name === 'core/column') attrs(block, { width: '50%', verticalAlignment: 'center' });
    if (block.name === 'core/heading') {
      if (block.attributes.style?.typography) delete block.attributes.style.typography.fontSize;
      attrs(block, { fontSize: 'sky-hero', style: { typography: { fontWeight: '750', lineHeight: '1.12', letterSpacing: '-0.045em' } } });
    }
    if (block.name === 'core/image' && !block.attributes.url.includes('allvend-logo')) {
      delete block.attributes.height;
      attrs(block, { width: '100%', aspectRatio: '3/2', scale: 'cover', focalPoint: { x: 1, y: 0.5 } });
    }
    if (block.name === 'core/image' && block.attributes.url.includes('allvend-logo')) attrs(block, { align: 'center' });
  });
  const partnerInner = section('participants').innerBlocks[0];
  attrs(section('participants'), { style: { spacing: { padding: { top: '36px', bottom: '36px' } } } });
  attrs(partnerInner, { style: { spacing: { blockGap: '18px' } } });
  textCenter(partnerInner.innerBlocks[0]);
  const offers = partnerInner.innerBlocks[1];
  if (offers.innerBlocks.length !== 6) throw new Error('Expected six partner directions');
  for (const offer of offers.innerBlocks) {
    const items = [];
    walk(offer.innerBlocks, block => { if (['core/image', 'core/heading', 'core/paragraph'].includes(block.name)) items.push(block); });
    if (items.length !== 3) throw new Error('Unexpected partner card');
    offer.innerBlocks = items;
    stack(offer, '8px', 'top');
    attrs(offer, { style: { dimensions: { minHeight: '165px' }, spacing: { padding: { top: '0px', bottom: '0px' } } } });
    items.forEach(block => {
      if (block.name === 'core/image') attrs(block, { align: 'center', style: { border: { radius: '6px' } } });
      else textCenter(block);
    });
    attrs(items[1], { style: { typography: { fontSize: '18px', fontWeight: '750', lineHeight: '1.4' } } });
  }
  const proof = partnerInner.innerBlocks[2];
  walk(proof.innerBlocks, block => {
    if (block.name === 'core/paragraph') textCenter(block);
    if (block.name === 'core/group') stack(block, '7px', 'top');
  });
  const statColumns = groups => create('columns', { isStackedOnMobile: false, style: { spacing: { blockGap: { top: '18px', left: '20px' } } } }, groups.map(group => {
    group.innerBlocks[0].attributes.fontSize = 'sky-stat';
    delete group.innerBlocks[0].attributes.style.typography.fontSize;
    attrs(group.innerBlocks[0], { style: { typography: { lineHeight: '1.2' } } });
    return create('column', {}, [group]);
  }));
  const stats = [];
  walk(proof.innerBlocks, block => { if (block.name === 'core/group' && block.innerBlocks.length === 2 && block.innerBlocks.every(x => x.name === 'core/paragraph')) stats.push(block); });
  if (stats.length !== 4) throw new Error('Expected four statistics');
  proof.innerBlocks = [create('columns', { style: { spacing: { blockGap: { top: '18px', left: '20px' } } } }, [create('column', {}, [statColumns(stats.slice(0, 2))]), create('column', {}, [statColumns(stats.slice(2))])])];
  proof.attributes.layout = { type: 'default' };
  const allvendInner = section('allvend').innerBlocks[0];
  walk(allvendInner.innerBlocks, block => {
    if (block.name !== 'core/image') return;
    if (block.attributes.url.includes('allvend-logo')) attrs(block, { align: 'center' });
    else { delete block.attributes.height; attrs(block, { width: '100%', aspectRatio: '3/2', scale: 'cover', focalPoint: { x: 1, y: 0.5 } }); }
  });
  for (const id of ['capabilities', 'fastsys']) {
    const art = section(id).innerBlocks[0].innerBlocks[0].innerBlocks[0].innerBlocks[0];
    if (art.name !== 'core/group') throw new Error('Unexpected artwork: ' + id);
    stack(art, '18px');
    attrs(art, { style: { border: { radius: '8px' }, dimensions: { minHeight: '350px' }, spacing: { padding: { top: '35px', bottom: '35px', left: '30px', right: '30px' } } } });
    art.innerBlocks.filter(x => x.name === 'core/paragraph').forEach(textCenter);
  }
  const security = section('security').innerBlocks[0];
  security.innerBlocks.filter(x => ['core/heading', 'core/paragraph'].includes(x.name)).forEach(textCenter);
  for (const card of security.innerBlocks.find(x => x.attributes.layout?.type === 'grid').innerBlocks) {
    stack(card, '12px', 'top');
    attrs(card, { style: { border: { color: line, width: '1px', radius: '8px' } } });
    card.innerBlocks.filter(x => ['core/heading', 'core/paragraph'].includes(x.name)).forEach(textCenter);
  }
  const securityButton = security.innerBlocks.find(x => x.name === 'core/buttons');
  attrs(securityButton, { layout: { type: 'flex', justifyContent: 'center' } });
  attrs(securityButton.innerBlocks[0], { style: { color: { background: '#ffffff' } } });
  const software = section('software').innerBlocks[0];
  software.innerBlocks.filter(x => ['core/heading', 'core/paragraph'].includes(x.name)).forEach(textCenter);
  for (const card of software.innerBlocks.find(x => x.attributes.layout?.type === 'grid').innerBlocks) {
    const buttonIndex = card.innerBlocks.findIndex(x => x.name === 'core/buttons');
    if (buttonIndex !== card.innerBlocks.length - 1) throw new Error('Unexpected software CTA');
    const content = buttonIndex === 1 && card.innerBlocks[0].name === 'core/group' ? card.innerBlocks[0] : create('group', { layout: { type: 'default' }, style: { spacing: { blockGap: '16px' } } }, card.innerBlocks.slice(0, buttonIndex));
    card.innerBlocks = [content, card.innerBlocks[buttonIndex]];
    attrs(card, { layout: { type: 'flex', orientation: 'vertical', justifyContent: 'stretch', verticalAlignment: 'space-between', flexWrap: 'nowrap' }, style: { color: { background: '#ffffff' }, border: { color: line, width: '1px', radius: '8px' }, dimensions: { minHeight: '310px' } } });
  }
  const providers = section('providers').innerBlocks[0];
  textCenter(providers.innerBlocks[0]);
  walk(providers.innerBlocks, block => {
    if (block.name === 'core/group' && block.attributes.layout?.type === 'grid') attrs(block, { layout: { minimumColumnWidth: block.innerBlocks.length === 6 ? '290px' : '230px' } });
    if (block.name === 'core/group' && block.attributes.metadata?.name) {
      stack(block, '16px');
      attrs(block, { style: { border: { color: line, width: '1px', radius: '6px' }, dimensions: { minHeight: '134px' } } });
    }
    if (block.name === 'core/paragraph') { delete block.attributes.align; textCenter(block); attrs(block, { style: { typography: { fontSize: '14px' } } }); }
    if (block.name === 'core/image') attrs(block, { align: 'center', width: '145px', height: '45px', scale: 'contain' });
  });
  // A genuine editable template part, with clear list-view names, not locked HTML.
  attrs(footer[0], { metadata: { name: 'Футер — контакты, поддержка и загрузки' }, layout: { contentSize: '1200px' }, style: { elements: { link: { typography: { textDecoration: 'none' } } } } });
  const footerColumns = footer[0].innerBlocks[0];
  if (footerColumns.name !== 'core/columns' || footerColumns.innerBlocks.length !== 4) throw new Error('Unexpected footer');
  attrs(footerColumns, { verticalAlignment: 'top' });
  const names = ['Логотип', 'Контакты компании', 'Техническая поддержка', 'Популярные загрузки'];
  footerColumns.innerBlocks.forEach((column, i) => attrs(column, { verticalAlignment: 'top', metadata: { name: names[i] }, style: { spacing: { blockGap: '10px' } } }));
  const downloads = footerColumns.innerBlocks[3];
  if (!(downloads.innerBlocks.length === 2 && downloads.innerBlocks[1].attributes.layout?.type === 'grid')) downloads.innerBlocks = [downloads.innerBlocks[0], create('group', { layout: { type: 'grid', minimumColumnWidth: '160px' }, style: { spacing: { blockGap: '12px' } } }, downloads.innerBlocks.slice(1))];
  const bottom = footer[0].innerBlocks.slice(1);
  if (!(bottom.length === 1 && bottom[0].attributes.metadata?.name === 'Копирайт и социальные сети')) footer[0].innerBlocks = [footerColumns, create('group', { metadata: { name: 'Копирайт и социальные сети' }, layout: { type: 'flex', justifyContent: 'space-between', flexWrap: 'wrap' }, style: { border: { top: { color: '#26384e', width: '1px' } }, spacing: { padding: { top: '18px' }, blockGap: '16px' } } }, bottom)];
  attrs(header[0], { layout: { contentSize: '1200px', wideSize: '1200px' } });
  const stylePost = posts.get(66);
  if (!stylePost) throw new Error('Missing global styles');
  const styles = JSON.parse(stylePost.content);
  const newStyles = merge(styles, { settings: { layout: { contentSize: '1200px', wideSize: '1200px' }, typography: { fluid: true, fontSizes: [{ slug: 'sky-hero', name: 'Заголовок баннера', size: '48px', fluid: { min: '30px', max: '48px' } }, { slug: 'sky-section', name: 'Заголовок секции', size: '40px', fluid: { min: '28px', max: '40px' } }, { slug: 'sky-stat', name: 'Показатели', size: '30px', fluid: { min: '21px', max: '30px' } }] } }, styles: { elements: { h2: { typography: { fontSize: 'var:preset|font-size|sky-section', fontWeight: '740', letterSpacing: '-0.04em', lineHeight: '1.16' } } } } });
  for (const [name, tree] of Object.entries({ page, footer, header })) {
    if (JSON.stringify(before[name]) !== JSON.stringify(semantic(tree))) throw new Error('Text/media/link change detected in ' + name);
  }
  fs.mkdirSync(output, { recursive: true });
  const manifest = [];
  for (const [name, id, tree] of [['page', 67, page], ['footer', 69, footer], ['header', 68, header]]) {
    const text = w.wp.blocks.serialize(tree);
    const parsed = w.wp.blocks.parse(text);
    let count = 0;
    walk(parsed, block => {
      count++;
      if (!block.isValid || !block.name.startsWith('core/') || ['core/html', 'core/code', 'core/shortcode', 'core/freeform'].includes(block.name)) throw new Error('Non-editable block in ' + name);
    });
    fs.writeFileSync(path.join(output, name + '.html'), text);
    manifest.push({ name, id, type: posts.get(id).type, beforeHash: hash(posts.get(id).content), afterHash: hash(text), count, invalid: 0 });
  }
  const styleText = JSON.stringify(newStyles, null, 2);
  fs.writeFileSync(path.join(output, 'styles.json'), styleText);
  manifest.push({ name: 'styles', id: 66, type: 'wp_global_styles', beforeHash: hash(stylePost.content), afterHash: hash(styleText) });
  fs.writeFileSync(path.join(output, 'manifest.json'), JSON.stringify(manifest, null, 2));
  console.log(JSON.stringify(manifest, null, 2));

  function semantic(tree) {
    const result = [];
    walk(tree, block => {
      const a = block.attributes;
      if (['core/paragraph', 'core/heading'].includes(block.name)) result.push(['text', a.content]);
      if (block.name === 'core/image') result.push(['image', a.id, a.url, a.alt, a.href]);
      if (block.name === 'core/button') result.push(['button', a.text, a.url]);
      if (block.name === 'core/navigation-link') result.push(['nav', a.label, a.url]);
      if (block.name === 'core/tab-list') result.push(['tabs', a.tabs]);
    });
    return result;
  }
  function hash(text) { return crypto.createHash('sha256').update(text).digest('hex'); }
}
