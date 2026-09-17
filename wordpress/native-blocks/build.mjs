/** One-time content export: uses WordPress's OWN block serializers, no site code. */
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
const require = createRequire(import.meta.url);
const { JSDOM, VirtualConsole } = require(process.env.SKYSEND_JSDOM || 'jsdom');
const dir = path.dirname(fileURLToPath(import.meta.url));
const origin = process.env.SKYSEND_ORIGIN || 'http://31.129.98.28';
const virtualConsole = new VirtualConsole();
virtualConsole.on('jsdomError', err => { if (err.type !== 'css parsing') console.error(err.message); });
virtualConsole.on('error', (...args) => console.error(...args));
const dom = new JSDOM('<!doctype html><html><body></body></html>', { url: origin, runScripts: 'outside-only', pretendToBeVisual: true, virtualConsole });
const w = dom.window;
w.matchMedia = () => ({ matches: false, addListener() {}, removeListener() {}, addEventListener() {}, removeEventListener() {} });
w.ResizeObserver = class { observe() {} disconnect() {} unobserve() {} };
w.requestIdleCallback = cb => setTimeout(() => cb({ timeRemaining: () => 50 }), 0);
w.cancelIdleCallback = clearTimeout;
w.fetch = fetch;
w.TextEncoder = TextEncoder;
w.TextDecoder = TextDecoder;
w.wp = {};
const scripts = ['vendor/react','vendor/react-dom','vendor/react-jsx-runtime','dom-ready','hooks','i18n','a11y','private-apis','url','api-fetch','autop','blob','block-serialization-default-parser','deprecated','dom','escape-html','element','is-shallow-equal','keycodes','priority-queue','undo-manager','compose','redux-routine','data','html-entities','rich-text','shortcode','warning','blocks','vendor/moment','date','primitives','theme','components','keyboard-shortcuts','preferences-persistence','preferences','commands','notices','style-engine','token-list','upload-media','block-editor','core-data','patterns','server-side-render','wordcount'];
const siteResponse = await fetch(origin);
if (!siteResponse.ok) throw new Error('WordPress origin: HTTP ' + siteResponse.status);
const coreVersion = (await siteResponse.text()).match(/<meta\s+name=["']generator["']\s+content=["']WordPress\s+([^"']+)["']/i)?.[1];
// Never reuse serializers from a different WordPress version or installation.
const cacheKey = `${origin.replace(/\W/g,'_')}-${coreVersion || Date.now()}`.replace(/[^a-zA-Z0-9._-]/g,'_');
const cache = path.join(process.env.TEMP || '/tmp', 'skysend-native-builder', 'core-' + cacheKey);
fs.mkdirSync(cache, { recursive: true });
await Promise.all([...scripts, 'block-library'].map(async name => {
  const file = path.join(cache, name.replaceAll('/', '_') + '.js');
  if (!fs.existsSync(file)) {
    const res = await fetch(`${origin}/wp-includes/js/dist/${name}.min.js`);
    if (!res.ok) throw new Error(`${name}: HTTP ${res.status}`);
    fs.writeFileSync(file, await res.text());
  }
}));
for (const name of [...scripts, 'block-library']) {
  try { w.eval(fs.readFileSync(path.join(cache, name.replaceAll('/', '_') + '.js'), 'utf8') + (name === 'vendor/react-jsx-runtime' ? '\nwindow.ReactJSXRuntime = ReactJSXRuntime;' : '')); }
  catch (err) { throw new Error(`Loading ${name}: ${err.stack}`); }
}
w.wp.blockLibrary.registerCoreBlocks();
for (const type of ['tabs','tab-list','tab-panels','tab-panel','icon']) {
  if (!w.wp.blocks.getBlockType('core/' + type)) throw new Error('The target WordPress must support native Tabs and Icon blocks (7.1+).');
}
console.log('Registered', w.wp.blocks.getBlockTypes().length, 'native blocks');
if (process.argv.includes('--probe')) { console.log(w.wp.blocks.serialize(w.wp.blocks.createBlock('core/tabs', {}, [w.wp.blocks.createBlock('core/tab-list', { tabs: [{label:'Первый'}, {label:'Второй'}] }), w.wp.blocks.createBlock('core/tab-panels', {}, [w.wp.blocks.createBlock('core/tab-panel', {label:'Первый'}, [w.wp.blocks.createBlock('core/paragraph', {content:'Тест'})]), w.wp.blocks.createBlock('core/tab-panel', {label:'Второй'})])] ))); dom.window.close(); process.exit(0); }
if (process.argv.includes('--repair')) {
  const index = process.argv.indexOf('--repair');
  const { repair } = await import('./repair.mjs');
  await repair(w, process.argv[index + 1], process.argv[index + 2]);
  dom.window.close();
  process.exit(0);
}
if (process.argv.includes('--providers-layout')) {
  const index = process.argv.indexOf('--providers-layout');
  const { providersLayout } = await import('./providers-layout.mjs');
  await providersLayout(w, process.argv[index + 1], process.argv[index + 2]);
  dom.window.close();
  process.exit(0);
}
const raw = JSON.parse(fs.readFileSync(path.join(dir, 'content.json'), 'utf8'));
const sourceImage = src => src.replace(/^assets\/images\//, '');
const fromSection = id => {const x=raw.sections.find(x=>x.id===id);return {...x,text:x.lead,url:x.action?.url,features:x.features || x.cards};};
const c = {
  hero:raw.hero.slides.map(x=>({...x,image:sourceImage(x.image.src),alt:x.image.alt,url:x.action.url})),
  partners:raw.partners.items.map(x=>({...x,image:sourceImage(x.image.src),text:x.offer})),
  stats:raw.stats,
  allvend:fromSection('allvend'),processing:fromSection('capabilities'),security:fromSection('security'),fastsys:fromSection('fastsys'),
  software:raw.software.map(x=>({...x,url:x.action.url})),
  providers:raw.providers.categories.map(x=>({...x,items:x.items.map(y=>({...y,image:sourceImage(y.image.src)}))})),
  footer:{phone:raw.footer.contacts.links[0].label,downloads:raw.footer.downloads,socials:raw.footer.social.map(x=>({...x,title:x.label}))},
};
const media = JSON.parse(fs.readFileSync(path.join(dir, 'media.json'), 'utf8'));
const blue = '#0b63f6', ink = '#071426', ice = '#edf4ff';
const b = (name, attrs = {}, children = []) => w.wp.blocks.createBlock('core/' + name, attrs, children);
const esc = s => String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
const p = (text, attrs = {}) => b('paragraph', { content: esc(text), ...attrs });
const h = (text, level = 2, attrs = {}) => b('heading', { content: esc(text), level, ...attrs });
const icon = (name, width = '40px', color = blue) => b('icon',{icon:'core/'+name,style:{dimensions:{width},color:{text:color}}});
const g = (children, attrs = {}) => b('group', { layout: {type:'default'}, ...attrs }, children);
const image = (file, alt, attrs = {}) => {
  if (!media[file]) throw new Error('Missing media: ' + file);
  return b('image', {id:media[file].id, url:media[file].url, alt, sizeSlug:'full', linkDestination:'none', ...attrs });
};
const button = (text, url) => b('buttons', {layout:{type:'flex',justifyContent:'left'}}, [b('button', {text, url, className:'is-style-outline', style:{color:{text:blue},border:{color:blue,width:'1px',radius:'5px'},spacing:{padding:{top:'10px',bottom:'10px',left:'20px',right:'20px'}},typography:{fontSize:'14px',fontWeight:'600'}}})]);
const more = url => button('Подробнее', url);
const cols = (children, widths, attrs = {}) => b('columns', {verticalAlignment:'center',style:{spacing:{blockGap:{top:'28px',left:'40px'}}}, ...attrs }, children.map((blocks,i) => b('column', {verticalAlignment:'center',...(widths?{width:widths[i]}:{})}, blocks)));
const grid = (children, min = '250px', attrs = {}) => g(children,{layout:{type:'grid',minimumColumnWidth:min},style:{spacing:{blockGap:'24px'}},...attrs});
const section = (id, children, background = '#ffffff') => g([g(children,{layout:{type:'constrained',contentSize:'1120px',wideSize:'1120px'},style:{spacing:{blockGap:'24px'}}})], {anchor:id, align:'full', metadata:{name:raw.sections.find(x=>x.id===id)?.title || id}, style:{color:{background},dimensions:{minHeight:'600px'},spacing:{padding:{top:'44px',bottom:'44px',left:'24px',right:'24px'},blockGap:'0px'}}});
const features = items => g(items.map(item=>g([h(item.title,3,{style:{typography:{fontSize:'18px'}}}),p(item.text,{style:{typography:{fontSize:'15px'}}})],{style:{spacing:{blockGap:'6px'}}})),{style:{spacing:{blockGap:'18px'}}});
const tabs = (id, panels, ariaLabel) => b('tabs',{anchor:id,activeTabIndex:0,style:{spacing:{blockGap:'24px'}}},[b('tab-list',{tabs:panels.map(x=>({label:esc(x.label)})),ariaLabel,style:{color:{text:blue,background:'#ffffff'},border:{color:blue,width:'1px',radius:'5px'},spacing:{padding:{top:'10px',bottom:'10px',left:'16px',right:'16px'},blockGap:'10px'},typography:{fontSize:'14px',fontWeight:'600'}}}), b('tab-panels',{},panels.map(x=>b('tab-panel',{label:x.label,layout:{type:'default'}},x.blocks)))]);
const hero = g([tabs('hero-proposals',c.hero.map((x,i)=>({label:['Провайдеры услуг','Финансовые условия','ПО ALLVEND'][i],blocks:[cols([[h(x.title,i===0?1:2,{fontSize:'xx-large'}),...(x.text?[p(x.text)]:[]),more(x.url)],[image(x.image,x.alt),...(i===2?[image('allvend-logo.png','ALLVEND',{width:'140px'})]:[])]],['40%','60%'])]})),'Главные предложения SkySend')],{align:'full',style:{color:{background:ice},dimensions:{minHeight:'510px'},spacing:{padding:{top:'34px',bottom:'40px',left:'24px',right:'24px'}}},layout:{type:'constrained',contentSize:'1120px',wideSize:'1120px'},metadata:{name:'Три баннера'}});
const partners = section('participants',[h('Партнерам'),grid(c.partners.map(x=>g([cols([[image(x.image,x.title,{width:'92px',height:'74px',scale:'cover'})],[h(x.title,3,{style:{typography:{fontSize:'17px',fontWeight:'700'}}}),p(x.text,{style:{typography:{fontSize:'14px'}}})]],['28%','72%'],{isStackedOnMobile:false,style:{spacing:{blockGap:{top:'12px',left:'16px'}}}})],{metadata:{name:x.title},style:{spacing:{padding:{top:'14px',bottom:'14px'},blockGap:'8px'}}})),'310px'),grid(c.stats.map(x=>g([p(x.value,{style:{color:{text:blue},typography:{fontSize:'30px',fontWeight:'800'}}}),p(x.label,{style:{typography:{fontSize:'13px'}}})],{style:{spacing:{blockGap:'4px'}}})),'140px',{style:{border:{top:{color:'#dbe5f1',width:'1px'}},spacing:{padding:{top:'20px'},blockGap:'20px'}}})]);
const allvend = section('allvend',[cols([[image('banner-allvend-20260914.webp','Устройства самообслуживания с ПО ALLVEND'),image('allvend-logo.png','ALLVEND',{width:'160px'})],[h(c.allvend.title),p(c.allvend.text),features(c.allvend.features),more(c.allvend.url)]],['50%','50%'])],ice);
const processing = section('capabilities',[cols([[g([icon('share','80px','#ffffff'),p('UNIX / FreeBSD',{style:{color:{text:'#ffffff'},typography:{fontSize:'36px',fontWeight:'700'}}}),p('Кластерная архитектура процессинга',{style:{color:{text:'#ffffff'}}})],{style:{color:{background:blue,text:'#ffffff'},spacing:{padding:{top:'50px',bottom:'50px',left:'30px',right:'30px'},blockGap:'16px'}}})],[h(c.processing.title),p(c.processing.text),features(c.processing.features),more(c.processing.url)]],['42%','58%'])]);
const security = section('security',[h(c.security.title),p(c.security.text),grid(c.security.features.map((x,i)=>g([icon(['shield','share','key'][i]),h(x.title,3,{style:{typography:{fontSize:'20px'},color:{text:ink}}}),p(x.text,{style:{typography:{fontSize:'16px'},color:{text:ink}}})],{style:{color:{background:'#ffffff',text:ink},spacing:{padding:{top:'30px',bottom:'30px',left:'24px',right:'24px'},blockGap:'12px'},dimensions:{minHeight:'230px'}}})),'290px'),more(c.security.url)],ink);
security.attributes.style.color.text = '#ffffff';
const fastsys = section('fastsys',[cols([[g([icon('settings','60px','#ffffff'),p('FastSYS 5',{style:{typography:{fontSize:'48px',fontWeight:'800'},color:{text:'#ffffff'}}}),p('Операционная система для устройств самообслуживания',{style:{color:{text:'#ffffff'}}})],{style:{color:{background:blue,text:'#ffffff'},dimensions:{minHeight:'290px'},spacing:{padding:{top:'48px',bottom:'48px',left:'32px',right:'32px'},blockGap:'20px'}}})],[h(c.fastsys.title),p(c.fastsys.text),features(c.fastsys.features),more(c.fastsys.url)]],['42%','58%'])],ice);
const software = section('software',[h('Полный набор клиентского софта'),p('Для устройств самообслуживания, компьютеров, смартфонов и интеграции собственной системы.'),grid(c.software.map((x,i)=>g([icon(['payment','desktop','mobile','share'][i]),h(x.title,3,{style:{typography:{fontSize:'20px'}}}),p(x.text,{style:{typography:{fontSize:'15px'}}}),more(x.url)],{metadata:{name:x.title},style:{color:{background:ice},spacing:{padding:{top:'24px',bottom:'24px',left:'20px',right:'20px'},blockGap:'16px'},dimensions:{minHeight:'280px'}}})),'240px')]);
const providers = section('providers',[h('Провайдеры услуг'),tabs('provider-categories',c.providers.map(cat=>({label:cat.title,blocks:[grid(cat.items.map(x=>g([image(x.image,x.name,{height:'64px',scale:'contain'}),p(x.name,{align:'center',style:{typography:{fontSize:'14px'}}})],{metadata:{name:x.name},style:{color:{background:'#ffffff'},spacing:{padding:{top:'18px',bottom:'18px',left:'14px',right:'14px'},blockGap:'12px'}}})),'210px')]})),'Категории провайдеров')],ice);
const nav = b('navigation',{overlayMenu:'mobile',style:{typography:{fontSize:'14px'},spacing:{blockGap:'16px'}},layout:{type:'flex',justifyContent:'right'}},[...raw.header.navigation.map(x=>b('navigation-link',{label:x.label,url:'/'+x.url,kind:'custom'})),b('navigation-link',{label:c.footer.phone,url:'tel:+78612011221',kind:'custom'})]);
const link = (label,url) => `<a href="${esc(url)}">${esc(label)}</a>`;
const header = g([g([image('skysend-logo.png','SkySend',{width:'78px',href:'/',linkDestination:'custom'}),nav,button('Войти | Регистрация','https://cluster.skysend.ru/')],{layout:{type:'flex',justifyContent:'space-between',flexWrap:'wrap'},style:{spacing:{blockGap:'12px'}}})],{align:'full',layout:{type:'constrained',contentSize:'1120px'},style:{spacing:{padding:{top:'8px',bottom:'8px',left:'24px',right:'24px'}}}});
const footer = g([cols([[image('skysend-logo.png','SkySend',{width:'110px',style:{color:{duotone:['#ffffff','#ffffff']}}})],[h('Контакты компании',3,{style:{typography:{fontSize:'16px'}}}),b('paragraph',{content:link(c.footer.phone,'tel:+78612011221'),style:{typography:{fontSize:'15px'}}}),b('paragraph',{content:link('info@isg.dev','mailto:info@isg.dev'),style:{typography:{fontSize:'15px'}}})],[h('Техническая поддержка',3,{style:{typography:{fontSize:'16px'}}}),b('paragraph',{content:link('@infsysgroup','https://t.me/infsysgroup'),style:{typography:{fontSize:'15px'}}}),b('paragraph',{content:link('support@isg.dev','mailto:support@isg.dev'),style:{typography:{fontSize:'15px'}}})],[h('Популярные загрузки',3,{style:{typography:{fontSize:'16px'}}}),...c.footer.downloads.map(x=>b('paragraph',{content:link(x.title,x.url),style:{typography:{fontSize:'13px'},spacing:{margin:{top:'6px',bottom:'6px'}}}}))]],['14%','23%','23%','40%'],{verticalAlignment:'top',style:{spacing:{blockGap:{top:'24px',left:'20px'}}}}),p('© 2006 - 2026 Группа компаний «Информ-Системы»',{style:{typography:{fontSize:'13px'}}}),b('paragraph',{content:c.footer.socials.map(x=>link(x.title,x.url)).join(', '),style:{typography:{fontSize:'13px'}}})],{anchor:'contacts',align:'full',layout:{type:'constrained',contentSize:'1120px'},style:{color:{background:ink,text:'#ffffff'},elements:{link:{color:{text:'#ffffff'}}},spacing:{padding:{top:'36px',bottom:'24px',left:'24px',right:'24px'},blockGap:'24px'}}});
const template = [b('template-part',{slug:'header',theme:'twentytwentyfive',tagName:'header'}),g([b('post-content',{align:'full',layout:{type:'default'}})],{tagName:'main',anchor:'main',align:'full',style:{spacing:{blockGap:'0px',margin:{top:'0px',bottom:'0px'}}}}),b('template-part',{slug:'footer',theme:'twentytwentyfive',tagName:'footer'})];
const output = {page:[hero,partners,allvend,processing,security,fastsys,software,providers],header:[header],footer:[footer],template};
const reports = {};
for (const [name,blocks] of Object.entries(output)) {
  const serialized = w.wp.blocks.serialize(blocks);
  const parsed = w.wp.blocks.parse(serialized);
  let count=0;const errors=[];
  const visit=x=>{count++;if(!x.name.startsWith('core/') || ['core/html','core/freeform','core/shortcode','core/code'].includes(x.name))errors.push(x.name);if(!x.isValid)errors.push(x.name+' invalid');x.innerBlocks.forEach(visit);};parsed.forEach(visit);
  if(errors.length)throw new Error(name+': '+errors.join(', '));
  fs.writeFileSync(path.join(dir,name+'.html'),serialized+'\n');
  reports[name]={count,invalid:errors.length};
}
const styles={version:3,isGlobalStylesUserThemeJSON:true,settings:{color:{palette:[{slug:'base',name:'Белый',color:'#ffffff'},{slug:'contrast',name:'Текст SkySend',color:ink},{slug:'accent-1',name:'Синий SkySend',color:blue},{slug:'accent-2',name:'Светлый фон',color:ice}]},layout:{contentSize:'1120px',wideSize:'1120px'}},styles:{color:{background:'#ffffff',text:ink},typography:{fontFamily:'var:preset|font-family|manrope',fontSize:'16px',lineHeight:'1.5'},spacing:{blockGap:'0px',padding:{top:'0px',bottom:'0px',left:'0px',right:'0px'}},elements:{heading:{color:{text:'inherit'},typography:{fontWeight:'700',lineHeight:'1.15'}},h2:{typography:{fontSize:'32px'}},h3:{typography:{fontSize:'20px'}},link:{color:{text:blue}},button:{border:{radius:'5px'},color:{background:blue,text:'#ffffff'},typography:{fontSize:'14px',fontWeight:'600'},spacing:{padding:{top:'10px',bottom:'10px',left:'20px',right:'20px'}}}},blocks:{'core/column':{spacing:{blockGap:'18px'}},'core/navigation':{typography:{fontSize:'14px'}}}}};
fs.writeFileSync(path.join(dir,'styles.json'),JSON.stringify(styles,null,2)+'\n');
fs.writeFileSync(path.join(dir,'validation.json'),JSON.stringify(reports,null,2)+'\n');
console.log(reports);
dom.window.close();
process.exit(0);
