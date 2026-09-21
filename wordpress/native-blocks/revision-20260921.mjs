/** Targeted customer revision from 21.09.2026; starts from a fresh WXR export. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { registerCarouselBlocks } from './carousel.mjs';

export async function revision20260921(w, origin, input, mediaFile, output) {
  if (!input || !mediaFile || !output) throw new Error('Usage: build.mjs --revision-20260921 current.xml revision-media.json output-directory');
  await registerCarouselBlocks(w, origin);
  const xml = new w.DOMParser().parseFromString(fs.readFileSync(input, 'utf8'), 'text/xml');
  if (xml.querySelector('parsererror')) throw new Error('Invalid WordPress export');
  const wpns = 'http://wordpress.org/export/1.2/';
  const contentns = 'http://purl.org/rss/1.0/modules/content/';
  const item = [...xml.querySelectorAll('item')].find(node => node.getElementsByTagNameNS(wpns, 'post_id')[0]?.textContent === '67');
  if (!item || item.getElementsByTagNameNS(wpns, 'post_type')[0]?.textContent !== 'page') throw new Error('Missing current page 67');
  const original = item.getElementsByTagNameNS(contentns, 'encoded')[0]?.textContent;
  const blocks = w.wp.blocks.parse(original);
  const media = JSON.parse(fs.readFileSync(mediaFile, 'utf8'));
  const requiredMedia = ['banner-finance-20260921.png','banner-allvend-diagram-20260921.png','partner-fast-food-20260921.jpg','partner-gateways.jpg'];
  for (const name of requiredMedia) {
    if (!Number.isInteger(media[name]?.id) || !String(media[name]?.url || '').startsWith(origin + '/')) throw new Error('Missing target media: ' + name);
  }
  const walk = (tree, fn) => tree.forEach(block => { fn(block); walk(block.innerBlocks, fn); });
  walk(blocks, block => { if (!block.isValid) throw new Error('Invalid input block: ' + block.name); });
  const merge = (a, b) => {
    const result = { ...a };
    for (const [key, value] of Object.entries(b)) result[key] = value && typeof value === 'object' && !Array.isArray(value) ? merge(a?.[key] || {}, value) : value;
    return result;
  };
  const set = (block, values) => { block.attributes = merge(block.attributes, values); };
  const expect = (condition, message) => { if (!condition) throw new Error(message); };
  const normalizedText = value => String(value).normalize('NFC').replace(/\s+/gu, ' ').trim();
  const setText = (block, before, after) => {
    expect(block && ['core/heading','core/paragraph'].includes(block.name), 'Expected a text block');
    expect(normalizedText(block.attributes.content) === normalizedText(before), `Unexpected text: ${block.attributes.content}`);
    block.attributes.content = after;
  };
  const setImage = (block, expectedUrlPart, name, alt) => {
    expect(block?.name === 'core/image' && String(block.attributes.url).includes(expectedUrlPart), 'Unexpected source image: ' + block?.attributes?.url);
    set(block, { id: media[name].id, url: media[name].url, alt, sizeSlug: 'full', linkDestination: 'none' });
  };
  const named = (tree, name) => {
    const found = [];
    walk(tree, block => { if (block.attributes.metadata?.name === name) found.push(block); });
    expect(found.length === 1, `Expected one named block “${name}”, found ${found.length}`);
    return found[0];
  };
  const hero = blocks.find(block => block.attributes.metadata?.name === 'Три баннера');
  const partners = blocks.find(block => block.attributes.anchor === 'participants');
  expect(hero && partners, 'Missing hero or partner section');
  const unchanged = blocks.filter(block => block !== hero && block !== partners).map(block => w.wp.blocks.serialize([block]));

  expect(hero.innerBlocks.length === 1 && hero.innerBlocks[0].name === 'cb/carousel-v2', 'Unexpected carousel');
  const carousel = hero.innerBlocks[0];
  expect(carousel.innerBlocks.length === 3 && carousel.attributes.autoplay === true && carousel.attributes.autoplaySpeed === 6000, 'Unexpected carousel settings');
  set(hero, { style: { dimensions: { minHeight: '500px' }, spacing: { padding: { top: '8px', bottom: '12px' } } } });
  carousel.innerBlocks.forEach((slide, index) => {
    expect(slide.name === 'cb/slide-v2' && slide.innerBlocks.length === 1 && slide.innerBlocks[0].name === 'core/columns', 'Unexpected slide structure');
    const columns = slide.innerBlocks[0].innerBlocks;
    expect(columns.length === 2 && columns.every(block => block.name === 'core/column'), 'Expected two slide columns');
    const button = columns[0].innerBlocks.find(block => block.name === 'core/buttons');
    const buttonHtml = button ? w.wp.blocks.serialize([button]) : '';
    expect(
      button?.innerBlocks.length === 1 &&
      button.innerBlocks[0].name === 'core/button' &&
      />\s*Подробнее\s*<\/a>/.test(buttonHtml),
      'Missing Подробнее button',
    );
    set(button, { style: { spacing: { margin: { top: 'clamp(36px, 10vw, 120px)' } } } });
    if (index === 1) {
      const image = columns[1].innerBlocks.find(block => block.name === 'core/image');
      setImage(image, 'banner-finance-20260914', 'banner-finance-20260921.png', 'Платёжный терминал, доход и рост на 20 процентов');
    }
    if (index === 2) {
      const paragraph = columns[0].innerBlocks.find(block => block.name === 'core/paragraph');
      setText(paragraph, 'ALLVEND — единое решение для разных устройств самообслуживания.', 'ALLVEND — единое ПО для разных типов устройств самообслуживания.');
      const images = columns[1].innerBlocks.filter(block => block.name === 'core/image');
      expect(images.length === 2 && String(images[1].attributes.url).includes('allvend-logo'), 'Expected separate ALLVEND logo');
      setImage(images[0], 'banner-allvend-20260914', 'banner-allvend-diagram-20260921.png', 'Единое ПО ALLVEND связывает разные типы устройств самообслуживания');
      columns[1].innerBlocks = columns[1].innerBlocks.filter(block => block !== images[1]);
    }
  });

  expect(partners.innerBlocks.length === 1, 'Unexpected partner wrapper');
  const partnerInner = partners.innerBlocks[0];
  const visibleHeadings = partnerInner.innerBlocks.filter(block => block.name === 'core/heading' && normalizedText(block.attributes.content) === 'Партнерам');
  expect(visibleHeadings.length === 1, 'Missing visible partner heading');
  partnerInner.innerBlocks = partnerInner.innerBlocks.filter(block => block !== visibleHeadings[0]);
  const restaurants = named([partners], 'Поставщикам товаров');
  const restaurantImage = restaurants.innerBlocks.find(block => block.name === 'core/image');
  const restaurantHeading = restaurants.innerBlocks.find(block => block.name === 'core/heading');
  const restaurantText = restaurants.innerBlocks.find(block => block.name === 'core/paragraph');
  setImage(restaurantImage, 'partner-suppliers', 'partner-fast-food-20260921.jpg', 'Посетитель заказывает еду в киоске самообслуживания ресторана быстрого питания');
  setText(restaurantHeading, 'Поставщикам товаров', 'Ресторанам быстрого питания');
  setText(restaurantText, 'Автоматизация клиентского обслуживания и продажи товаров.', 'Автоматизация клиентского обслуживания');
  restaurants.attributes.metadata.name = 'Ресторанам быстрого питания';
  const retail = named([partners], 'Торговым сетям');
  setText(retail.innerBlocks.find(block => block.name === 'core/paragraph'), 'Самообслуживание, заказ товаров и оплата услуг на ALLVEND.', 'Внедрение самообслуживания');
  const gateways = named([partners], 'Шлюзовикам');
  setImage(gateways.innerBlocks.find(block => block.name === 'core/image'), 'partner-gateways-no-xml-20260916', 'partner-gateways.jpg', 'XML-интеграция платёжного шлюза');
  const replacements = new Map([
    ['20', '20 лет +'],
    ['Опыт работы, лет', 'Работаем с 2006 года'],
    ['Партнёров группы', 'Поставщиков и партнеров'],
    ['Оборот через разработки, ₽', 'Оборот системы, Р'],
    ['Проведённых транзакций', 'Проведено транзакций'],
  ]);
  const counts = new Map([...replacements.keys()].map(key => [key, 0]));
  walk([partners], block => {
    if (block.name === 'core/paragraph') {
      const source = [...replacements.keys()].find(text => normalizedText(block.attributes.content) === normalizedText(text));
      if (!source) return;
      counts.set(source, counts.get(source) + 1);
      block.attributes.content = replacements.get(source);
    }
  });
  for (const [text, count] of counts) expect(count === 1, `Expected one statistic “${text}”, found ${count}`);

  const text = w.wp.blocks.serialize(blocks);
  const parsed = w.wp.blocks.parse(text);
  const parsedHero = parsed.find(block => block.attributes.metadata?.name === 'Три баннера');
  const parsedPartners = parsed.find(block => block.attributes.anchor === 'participants');
  expect(JSON.stringify(unchanged) === JSON.stringify(parsed.filter(block => block !== parsedHero && block !== parsedPartners).map(block => w.wp.blocks.serialize([block]))), 'Changes outside hero and partner sections');
  expect(parsedHero.attributes.style.dimensions.minHeight === '500px', 'Banner height did not persist');
  const allvendRight = parsedHero.innerBlocks[0].innerBlocks[2].innerBlocks[0].innerBlocks[1];
  expect(allvendRight.innerBlocks.filter(block => block.name === 'core/image').length === 1, 'ALLVEND slide must contain one image');
  let count = 0;
  walk(parsed, block => {
    count++;
    const allowed = block.name.startsWith('core/') || ['cb/carousel-v2','cb/slide-v2'].includes(block.name);
    if (!block.isValid || !allowed || ['core/html','core/freeform','core/code','core/shortcode'].includes(block.name)) throw new Error('Invalid or unexpected block: ' + block.name);
  });
  fs.mkdirSync(output, { recursive: true });
  const manifest = [{
    name: 'page', id: 67, type: 'page', beforeHash: hash(original), afterHash: hash(text), count, invalid: 0,
    plugin: 'carousel-block', pluginVersion: '2.1.5', operation: 'customer-revision-20260921', bannerMinHeight: 500,
    media: Object.fromEntries(requiredMedia.map(name => [name, media[name].id])),
  }];
  fs.writeFileSync(path.join(output, 'page.html'), text);
  fs.writeFileSync(path.join(output, 'manifest.json'), JSON.stringify(manifest, null, 2));
  console.log(JSON.stringify(manifest, null, 2));
  function hash(value) { return crypto.createHash('sha256').update(value).digest('hex'); }
}
