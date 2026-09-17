/** One-time conversion using the installed plugin's own Gutenberg serializers. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

export async function carousel(w, origin, input, output) {
  if (!input || !output) throw new Error('Usage: build.mjs --carousel current-page.xml output-directory');
  const pluginRoot = `${origin}/wp-content/plugins/carousel-block/build`;
  for (const folder of ['carousel', 'slide']) {
    const metaResponse = await fetch(`${pluginRoot}/${folder}/block.json`);
    if (!metaResponse.ok) throw new Error('Missing installed plugin metadata');
    const metadata = await metaResponse.json();
    w.wp.blocks.unstable__bootstrapServerSideBlockDefinitions({ [metadata.name]: metadata });
    const scriptResponse = await fetch(`${pluginRoot}/${folder}/index.js`);
    if (!scriptResponse.ok) throw new Error('Missing installed plugin serializer');
    w.eval(await scriptResponse.text());
    if (!w.wp.blocks.getBlockType(metadata.name)) throw new Error('Plugin block registration failed');
  }
  const xml = new w.DOMParser().parseFromString(fs.readFileSync(input, 'utf8'), 'text/xml');
  if (xml.querySelector('parsererror')) throw new Error('Invalid WXR');
  const wpns = 'http://wordpress.org/export/1.2/';
  const contentns = 'http://purl.org/rss/1.0/modules/content/';
  const item = [...xml.querySelectorAll('item')].find(x => x.getElementsByTagNameNS(wpns, 'post_id')[0]?.textContent === '67');
  if (!item || item.getElementsByTagNameNS(wpns, 'post_type')[0]?.textContent !== 'page') throw new Error('Missing page 67');
  const original = item.getElementsByTagNameNS(contentns, 'encoded')[0]?.textContent;
  const blocks = w.wp.blocks.parse(original);
  const hero = blocks.find(block => block.attributes.metadata?.name === 'Три баннера');
  if (!hero || hero.innerBlocks.length !== 1 || hero.innerBlocks[0].name !== 'core/tabs') throw new Error('Unexpected hero');
  const tabs = hero.innerBlocks[0];
  const panels = tabs.innerBlocks.find(block => block.name === 'core/tab-panels');
  if (!panels || panels.innerBlocks.length !== 3) throw new Error('Expected three native slides');
  const outside = blocks.filter(block => block !== hero).map(block => w.wp.blocks.serialize([block]));
  const contents = panels.innerBlocks.map(panel => w.wp.blocks.serialize(panel.innerBlocks));
  const slider = w.wp.blocks.createBlock('cb/carousel-v2', {
    anchor: tabs.attributes.anchor || 'hero-proposals',
    slidesPerView: 1, slidesPerGroup: 1, spaceBetween: 0, speed: 500,
    navigation: false, pagination: true, loop: true,
    autoplay: true, autoplaySpeed: 6000, pauseOnMouseEnter: true,
    disableOnInteraction: false,
    breakpoints: [{ width: 768, slidesPerView: 1, slidesPerGroup: 1 }],
    metadata: { name: 'Баннеры — автопрокрутка каждые 6 секунд' },
  }, panels.innerBlocks.map(panel => w.wp.blocks.createBlock('cb/slide-v2', {
    metadata: { name: panel.attributes.label },
  }, panel.innerBlocks)));
  if (JSON.stringify(contents) !== JSON.stringify(slider.innerBlocks.map(slide => w.wp.blocks.serialize(slide.innerBlocks)))) throw new Error('Slide content changed');
  hero.innerBlocks = [slider];
  const text = w.wp.blocks.serialize(blocks);
  const parsed = w.wp.blocks.parse(text);
  const parsedHero = parsed.find(block => block.attributes.metadata?.name === 'Три баннера');
  if (JSON.stringify(outside) !== JSON.stringify(parsed.filter(block => block !== parsedHero).map(block => w.wp.blocks.serialize([block])))) throw new Error('Changes outside banners');
  if (JSON.stringify(contents) !== JSON.stringify(parsedHero.innerBlocks[0].innerBlocks.map(slide => w.wp.blocks.serialize(slide.innerBlocks)))) throw new Error('Slide round-trip content changed');
  let count = 0;
  const walk = tree => tree.forEach(block => {
    count++;
    if (!block.isValid || (!block.name.startsWith('core/') && !['cb/carousel-v2','cb/slide-v2'].includes(block.name)) || ['core/html','core/freeform','core/code','core/shortcode'].includes(block.name)) throw new Error('Invalid or unexpected block: ' + block.name);
    walk(block.innerBlocks);
  });
  walk(parsed);
  fs.mkdirSync(output, { recursive: true });
  const manifest = [{ name: 'page', id: 67, type: 'page', beforeHash: hash(original), afterHash: hash(text), count, invalid: 0, plugin: 'carousel-block', pluginVersion: '2.1.5', slides: 3, autoplaySpeed: 6000 }];
  fs.writeFileSync(path.join(output, 'page.html'), text);
  fs.writeFileSync(path.join(output, 'manifest.json'), JSON.stringify(manifest, null, 2));
  console.log(JSON.stringify(manifest, null, 2));
  function hash(text) { return crypto.createHash('sha256').update(text).digest('hex'); }
}
