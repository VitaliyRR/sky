/** Guarded WordPress block revision for the customer's 24.09.2026 notes. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { registerCarouselBlocks } from './carousel.mjs';

const dir = path.dirname(fileURLToPath(import.meta.url));
const repo = path.resolve(dir, '..', '..');
const originalSnapshot = file => execFileSync('git', ['-C', repo, 'show', `be00436:wordpress/native-blocks/${file}`], { encoding: 'utf8' });
const expected = {
  page: '72d7f13ce5c05053992ae22db0b52fe8abc6b9326cbc53ecc02f9bfd15004a54',
  header: '9f017d5eef6928d757731ec573ba247b624b1d8d3705f2e791eb4ced77c751dc',
  footer: 'ad502d8099c0af7fb6a44a993df268bf2795145264f86a62dbc8f3d5b58a03b8',
  styles: '7f74afac2d51db2b8ba8191f72518c66fa6f0c569ee587066befaf5242ed9452',
};
const digest = value => crypto.createHash('sha256').update(value).digest('hex');
const esc = text => String(text).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');

export async function revision20260924(w, origin, pageFile, mediaFile, outputDir) {
  if (!pageFile || !mediaFile || !outputDir) throw new Error('Usage: build.mjs --revision-20260924 live-page.html media.json output-dir');
  await registerCarouselBlocks(w, origin);
  const sources = {
    page: fs.readFileSync(pageFile, 'utf8'),
    header: originalSnapshot('header.html'),
    footer: originalSnapshot('footer.html'),
    styles: originalSnapshot('styles.json'),
  };
  for (const [name, source] of Object.entries(sources)) {
    if (digest(source) !== expected[name]) throw new Error(`Stale ${name} source: ${digest(source)}`);
  }
  const ids = JSON.parse(fs.readFileSync(mediaFile, 'utf8'));
  const required = [
    'banner-providers-20260924.webp', 'banner-finance-20260924.webp', 'banner-allvend-diagram-20260924.webp',
    'allvend-ui-official-20260924.webp', 'server-cluster-20260924.webp', 'site-icon-20260924.png',
    'full_offer_agent.pdf', 'connection_provider.pdf', 'presentation_self-order_kiosks_ru.pdf',
    'buy_terminal_chain.pdf', 'offer_dealer.pdf', 'gateway_payment.pdf', 'pdf-20260924.png',
    'beeline.png', 'mcdonalds.png', 'magnit.png', 'video-20260924.png', 'pos-20260924.png',
    'qr-20260924.png', 'biometric-20260924.png', 'settings-20260924.png', 'remote-20260924.png',
    'partner-gateways-no-xml-20260924.webp',
  ];
  for (const file of required) if (!Number.isInteger(ids[file])) throw new Error('Missing media ID: ' + file);
  const url = file => `${origin}/wp-content/uploads/2026/09/${file}`;
  const media = (file, alt, attrs = {}) => b('image', { id: ids[file], url: url(file), alt, sizeSlug: 'full', linkDestination: 'none', ...attrs });
  const b = (name, attrs = {}, children = []) => w.wp.blocks.createBlock('core/' + name, attrs, children);
  const p = (content, attrs = {}) => b('paragraph', { content, ...attrs });
  const find = (tree, predicate) => {
    const found = [];
    visit(tree, block => { if (predicate(block)) found.push(block); });
    if (found.length !== 1) throw new Error(`Expected one block, found ${found.length}`);
    return found[0];
  };
  const visit = (tree, callback) => tree.forEach(block => { callback(block); visit(block.innerBlocks, callback); });
  const imageAt = (tree, fragment) => find(tree, block => block.name === 'core/image' && String(block.attributes.url).includes(fragment));
  const parsed = {};
  for (const name of ['page', 'header', 'footer']) {
    parsed[name] = w.wp.blocks.parse(sources[name]);
    visit(parsed[name], block => { if (!block.isValid) throw new Error(`Invalid original ${name} block: ${block.name}`); });
  }

  // The three original slides remain editable and retain their carousel timing.
  const hero = find(parsed.page, block => block.attributes.metadata?.name === 'Три баннера');
  const carousel = hero.innerBlocks[0];
  if (carousel.name !== 'cb/carousel-v2' || carousel.innerBlocks.length !== 3 || carousel.attributes.autoplay !== true) throw new Error('Unexpected hero carousel');
  for (const [fragment, file, alt] of [
    ['banner-providers-20260914', 'banner-providers-20260924.webp', 'Банки, операторы и поставщики услуг'],
    ['banner-finance-20260921', 'banner-finance-20260924.webp', 'Платёжный терминал и финансовые условия'],
    ['banner-allvend-diagram-20260921', 'banner-allvend-diagram-20260924.webp', 'Схема применения ПО ALLVEND'],
  ]) {
    const image = imageAt([hero], fragment);
    Object.assign(image.attributes, { id: ids[file], url: url(file), alt, sizeSlug: 'full' });
  }

  const partners = find(parsed.page, block => block.attributes.anchor === 'participants');
  partners.attributes.style.spacing.padding.top = '24px';
  partners.attributes.style.spacing.padding.bottom = '24px';
  const partnerInner = partners.innerBlocks[0];
  partnerInner.attributes.style.spacing.blockGap = '12px';
  const partnerGrid = partnerInner.innerBlocks.find(block => block.attributes.layout?.type === 'grid');
  if (!partnerGrid || partnerGrid.innerBlocks.length !== 6) throw new Error('Expected six partner cards');
  partnerInner.innerBlocks.unshift(b('heading', { content: 'Партнерам', level: 2, style: { typography: { textAlign: 'center' } } }));
  partnerGrid.attributes.style.spacing.blockGap = '12px';
  const offers = [
    ['Платёжным агентам', 'full_offer_agent.pdf', '331 КБ', '2015', 'Скачать архивное КП'],
    ['Провайдерам услуг', 'connection_provider.pdf', '175 КБ', '2016', 'Скачать архивный документ'],
    ['Ресторанам быстрого питания', 'presentation_self-order_kiosks_ru.pdf', '846 КБ', '2021', 'Скачать архивную презентацию'],
    ['Торговым сетям', 'buy_terminal_chain.pdf', '107 КБ', '2015', 'Скачать архивный документ'],
    ['Представителям', 'offer_dealer.pdf', '615 КБ', '2016', 'Скачать архивное КП'],
    ['Шлюзовикам', 'gateway_payment.pdf', '684 КБ', '2015', 'Скачать архивный документ'],
  ];
  for (const [label, file, size, year, action] of offers) {
    const card = find([partnerGrid], block => block.attributes.metadata?.name === label);
    card.attributes.style.dimensions.minHeight = '160px';
    card.attributes.style.spacing.blockGap = '6px';
    const cardPhoto = find([card], block => block.name === 'core/image');
    Object.assign(cardPhoto.attributes, { width: '80px', height: '64px', scale: 'cover' });
    if (label === 'Торговым сетям') {
      const copy = find([card], block => block.name === 'core/paragraph' && String(block.attributes.content).normalize('NFC').replace(/\s+/gu, ' ').trim() === 'Внедрение самообслуживания');
      copy.attributes.content = 'Внедрение систем самообслуживания';
    }
    if (label === 'Шлюзовикам') {
      const gatewayPhoto = imageAt([card], 'partner-gateways.jpg');
      Object.assign(gatewayPhoto.attributes, { id: ids['partner-gateways-no-xml-20260924.webp'], url: url('partner-gateways-no-xml-20260924.webp'), alt: 'Подключение платёжной системы к SkySend' });
    }
    const download = b('group', { layout: { type: 'flex', justifyContent: 'center', verticalAlignment: 'center', flexWrap: 'nowrap' }, style: { spacing: { blockGap: '6px' } } }, [
      media('pdf-20260924.png', '', { width: '18px', height: '18px', scale: 'contain' }),
      p(`<a href="${esc(url(file))}" target="_blank" rel="noopener noreferrer">${esc(action)} · PDF, ${esc(size)} (${year})</a>`, { style: { typography: { fontSize: '12px', textAlign: 'center', lineHeight: '1.3' } } }),
    ]);
    card.innerBlocks.push(download);
  }
  const stats = partnerInner.innerBlocks.find(block => block.name === 'core/columns');
  const disclaimer = p('Архивные материалы: условия и контакты в документах могут быть устаревшими. Актуальную информацию уточняйте при подключении.', { style: { color: { text: '#52647a' }, typography: { fontSize: '12px', textAlign: 'center' } } });
  partnerInner.innerBlocks.splice(partnerInner.innerBlocks.indexOf(stats), 0, disclaimer);

  const allvend = find(parsed.page, block => block.attributes.anchor === 'allvend');
  const allvendCols = find([allvend], block => block.name === 'core/columns');
  const [allvendLeft, allvendRight] = allvendCols.innerBlocks;
  allvendLeft.innerBlocks = [media('allvend-ui-official-20260924.webp', 'Официальный пример экрана ПО ALLVEND', { width: '100%', aspectRatio: '3/2', scale: 'cover', focalPoint: { x: 0.5, y: 0.55 }, style: { border: { radius: '5px' } } })];
  const originalAllvendButton = find([allvendRight], block => block.name === 'core/buttons');
  const featureSpecs = [
    ['Оплата услуг', 'beeline.png'], ['Самообслуживание', 'mcdonalds.png'], ['Трансляция рекламы', 'video-20260924.png'],
    ['Безналичная оплата', 'pos-20260924.png'], ['Считывание QR', 'qr-20260924.png'], ['Биометрическая идентификация', 'biometric-20260924.png'],
    ['Настройка интерфейса', 'settings-20260924.png'], ['Удалённое управление', 'remote-20260924.png'], ['Продажа товаров', 'magnit.png'],
  ];
  const featureGrid = b('group', { layout: { type: 'grid', minimumColumnWidth: '150px' }, style: { spacing: { blockGap: '9px' } } }, featureSpecs.map(([name, file]) => b('group', {
    metadata: { name }, layout: { type: 'flex', orientation: 'vertical', justifyContent: 'center', verticalAlignment: 'center', flexWrap: 'nowrap' },
    style: { color: { background: '#ffffff' }, border: { color: '#dbe5f1', width: '1px', radius: '5px' }, dimensions: { minHeight: '80px' }, spacing: { padding: { top: '8px', bottom: '8px', left: '6px', right: '6px' }, blockGap: '5px' } },
  }, [media(file, '', { width: '34px', height: '34px', scale: 'contain' }), p(esc(name), { style: { typography: { fontSize: '12px', fontWeight: '650', textAlign: 'center', lineHeight: '1.2' } } })])));
  allvendRight.innerBlocks = [
    b('heading', { content: 'Универсальное ПО ALLVEND', level: 2 }),
    p('ПО в виде ISO образа легко устанавливается на более чем 20 типов устройств самообслуживания и имеет широкий спектр функций:'),
    featureGrid,
    originalAllvendButton,
  ];

  const speed = find(parsed.page, block => block.attributes.anchor === 'capabilities');
  const speedCols = find([speed], block => block.name === 'core/columns');
  speedCols.innerBlocks[0].innerBlocks = [media('server-cluster-20260924.webp', 'Иллюстрация кластера серверов с распределением нагрузки', { width: '100%', aspectRatio: '3/2', scale: 'contain' })];
  const speedRight = speedCols.innerBlocks[1];
  const speedButton = find([speedRight], block => block.name === 'core/buttons');
  speedRight.innerBlocks = [
    b('heading', { content: 'Высокая скорость обработки транзакций', level: 2 }),
    p('Серверы системы синхронизируют данные и распределяют поступающую нагрузку.'),
    b('list', { ordered: false, style: { typography: { fontSize: '16px' }, spacing: { blockGap: '12px' } } }, [
      b('list-item', { content: 'Кластер серверов — совместная обработка платежей и распределение нагрузки.' }),
      b('list-item', { content: 'UNIX / FreeBSD — система разработана на базе FreeBSD.' }),
      b('list-item', { content: 'Распределённая архитектура — серверы в разных центрах обработки данных связаны шифрованными туннелями IPSEC.' }),
      b('list-item', { content: 'Язык разработки C/C++' }),
    ]),
    p('Система извлекает максимум производительности из «Железа»', { style: { color: { text: '#0b63f6' }, typography: { fontSize: '16px', fontWeight: '700' } } }),
    speedButton,
  ];

  // Keep internal anchor and contact links in the same tab; only external destinations open separately.
  for (const name of ['page', 'header', 'footer']) visit(parsed[name], block => {
    if (block.name === 'core/button' && /^https?:\/\//i.test(block.attributes.url || '')) {
      block.attributes.linkTarget = '_blank';
      block.attributes.rel = 'noopener noreferrer';
    }
    if (block.name === 'core/paragraph' && /<a\b/i.test(block.attributes.content || '')) {
      block.attributes.content = block.attributes.content.replace(/<a\s+href="(https?:\/\/[^"]+)"(?![^>]*\btarget=)/gi, '<a href="$1" target="_blank" rel="noopener noreferrer"');
    }
  });
  const headerGroup = parsed.header[0]?.innerBlocks[0];
  if (headerGroup?.innerBlocks.length !== 3) throw new Error('Unexpected header layout');
  const [logo, nav, login] = headerGroup.innerBlocks;
  const phone = nav.innerBlocks.find(block => block.attributes.url === 'tel:+78612011221');
  if (!phone) throw new Error('Missing header phone');
  nav.innerBlocks = nav.innerBlocks.filter(block => block !== phone);
  headerGroup.attributes.layout = { type: 'flex', justifyContent: 'space-between', verticalAlignment: 'center', flexWrap: 'wrap' };
  headerGroup.innerBlocks = [logo, nav, b('group', { layout: { type: 'flex', justifyContent: 'right', verticalAlignment: 'center', flexWrap: 'wrap' }, style: { spacing: { blockGap: '14px' } } }, [
    p('<a href="tel:+78612011221">+7 (861) 201-12-21</a>', { style: { typography: { fontSize: '14px', fontWeight: '600' }, elements: { link: { color: { text: '#071426' } } } } }),
    login,
  ])];

  const styles = JSON.parse(sources.styles);
  styles.styles.elements.button[':hover'] = { color: { background: '#8ed0ff', text: '#071426' }, border: { color: '#8ed0ff' } };
  // Native Tabs have no hover control. This small Site Editor Global Styles rule covers Tabs and buttons uniformly.
  styles.styles.css = '.wp-block-button__link:hover, .wp-block-button__link:focus-visible, .wp-block-tab-list button:hover, .wp-block-tab-list button:focus-visible { background-color: #8ed0ff !important; border-color: #8ed0ff !important; color: #071426 !important; transition: background-color .18s ease, border-color .18s ease, color .18s ease; }';

  const output = {};
  const reports = {};
  for (const name of ['page', 'header', 'footer']) {
    output[name] = w.wp.blocks.serialize(parsed[name]);
    const roundTrip = w.wp.blocks.parse(output[name]);
    let count = 0;
    visit(roundTrip, block => {
      count++;
      if (!block.isValid) throw new Error(`Invalid generated ${name} block: ${block.name}`);
      if (!block.name.startsWith('core/') && !['cb/carousel-v2', 'cb/slide-v2'].includes(block.name)) throw new Error('Unexpected block ' + block.name);
    });
    reports[name] = { id: { page: 67, header: 68, footer: 69 }[name], beforeHash: digest(sources[name]), afterHash: digest(output[name]), blocks: count };
  }
  output.styles = JSON.stringify(styles, null, 2) + '\n';
  reports.styles = { id: 66, beforeHash: digest(sources.styles), afterHash: digest(output.styles) };
  fs.mkdirSync(outputDir, { recursive: true });
  for (const [name, content] of Object.entries(output)) fs.writeFileSync(path.join(outputDir, name + (name === 'styles' ? '.json' : '.html')), content);
  const manifest = { revision: '2026-09-24', origin, mediaIds: ids, siteIconId: ids['site-icon-20260924.png'], reports };
  fs.writeFileSync(path.join(outputDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest, null, 2));
}
