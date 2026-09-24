/**
 * Editable footer refresh for the second 24.09.2026 revision.
 *
 * All six downloads remain ordinary WordPress blocks: an Image for the file
 * type, a linked Paragraph for its name, and a Paragraph for its actual size.
 * Social icons use the native Social Icons block, not markup or a plugin.
 */

const downloads = [
  {
    title: 'Установочный образ ALLVEND', type: 'ZIP', size: '512 МБ',
    url: 'https://ftp.isg.dev/soft/allvend/fastsys5_allvend.iso.zip', icon: 'zip-20260924.png',
  },
  {
    title: 'Установка ALLVEND', type: 'PDF', size: '701 КБ',
    url: 'https://ftp.isg.dev/docs/instruction_installation_allvend.pdf', icon: 'pdf-20260924.png',
  },
  {
    title: 'Настройка и сервисный режим', type: 'PDF', size: '6,5 МБ',
    url: 'https://ftp.isg.dev/docs/instruction_setup_and_service_mode_allvend.pdf', icon: 'pdf-20260924.png',
  },
  {
    title: 'Подключение оборудования', type: 'PDF', size: '1,7 МБ',
    url: 'https://ftp.isg.dev/docs/instruction_setup_devices_kiosks.pdf', icon: 'pdf-20260924.png',
  },
  {
    title: 'Конфигурация ALLVEND', type: 'PDF', size: '2,0 МБ',
    url: 'https://ftp.isg.dev/docs/description_configuration_allvend.pdf', icon: 'pdf-20260924.png',
  },
  {
    title: 'Разработка и сопровождение ПО', type: 'PDF', size: '812 КБ',
    url: 'https://ftp.isg.dev/offer/allvend/presentation_support_software_development.pdf', icon: 'pdf-20260924.png',
  },
];

const socials = [
  { service: 'telegram', label: 'Telegram', url: 'https://t.me/infsysgroup' },
  { service: 'youtube', label: 'YouTube', url: 'https://www.youtube.com/c/infsysgroup' },
  { service: 'facebook', label: 'Facebook', url: 'https://www.facebook.com/infsysgroup' },
  { service: 'instagram', label: 'Instagram', url: 'https://www.instagram.com/infsysgroup' },
  { service: 'x', label: 'X (Twitter)', url: 'https://twitter.com/infsysgroup' },
];

const escapeHtml = value => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');

export function updateFooter(w, footerBlocks, { origin, mediaIds }) {
  if (!w?.wp?.blocks?.createBlock || !Array.isArray(footerBlocks)) throw new Error('Expected parsed WordPress footer blocks');
  if (!origin || !mediaIds) throw new Error('Footer revision requires origin and mediaIds');
  for (const file of ['pdf-20260924.png', 'zip-20260924.png']) {
    if (!Number.isInteger(mediaIds[file])) throw new Error(`Missing footer icon media ID: ${file}`);
  }
  const b = (name, attributes = {}, innerBlocks = []) => w.wp.blocks.createBlock(`core/${name}`, attributes, innerBlocks);
  const paragraph = (content, attributes = {}) => b('paragraph', { content, ...attributes });
  const findAll = (tree, predicate) => {
    const matches = [];
    const visit = blocks => blocks.forEach(block => {
      if (predicate(block)) matches.push(block);
      visit(block.innerBlocks);
    });
    visit(tree);
    return matches;
  };
  const exactlyOne = (tree, predicate, description) => {
    const matches = findAll(tree, predicate);
    if (matches.length !== 1) throw new Error(`Expected one ${description}; got ${matches.length}`);
    return matches[0];
  };

  const downloadsColumn = exactlyOne(footerBlocks, block => block.attributes.metadata?.name === 'Популярные загрузки', 'downloads column');
  const downloadsGrid = exactlyOne([downloadsColumn], block => block.name === 'core/group' && block.attributes.layout?.type === 'grid', 'downloads grid');
  if (downloadsGrid.innerBlocks.length !== downloads.length) throw new Error('Expected six existing footer downloads');
  for (const item of downloads) {
    if (!downloadsGrid.innerBlocks.some(block => String(block.attributes.content || '').includes(item.url))) {
      throw new Error(`Existing footer download changed: ${item.url}`);
    }
  }

  const mediaUrl = file => `${origin.replace(/\/$/, '')}/wp-content/uploads/2026/09/${file}`;
  downloadsGrid.attributes.layout = { type: 'grid', minimumColumnWidth: '180px' };
  downloadsGrid.attributes.style = {
    ...downloadsGrid.attributes.style,
    spacing: { ...(downloadsGrid.attributes.style?.spacing || {}), blockGap: '14px' },
  };
  downloadsGrid.innerBlocks = downloads.map(item => {
    const icon = b('image', {
      id: mediaIds[item.icon], url: mediaUrl(item.icon), alt: item.type,
      width: '26px', height: '26px', scale: 'contain', sizeSlug: 'full', linkDestination: 'none',
    });
    const text = b('group', {
      layout: { type: 'default' }, style: { spacing: { blockGap: '2px' } },
    }, [
      paragraph(`<a href="${escapeHtml(item.url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(item.title)}</a>`, {
        style: { typography: { fontSize: '13px', fontWeight: '600', lineHeight: '1.3' } },
      }),
      paragraph(`${item.type} · ${item.size}`, {
        style: { color: { text: '#aec5df' }, typography: { fontSize: '12px', lineHeight: '1.2' } },
      }),
    ]);
    return b('group', {
      metadata: { name: `Загрузка — ${item.title}` },
      layout: { type: 'flex', flexWrap: 'nowrap', verticalAlignment: 'top' },
      style: { spacing: { blockGap: '8px' } },
    }, [icon, text]);
  });

  const legalRow = exactlyOne(footerBlocks, block => block.attributes.metadata?.name === 'Копирайт и социальные сети', 'copyright/social row');
  const textSocials = exactlyOne([legalRow], block => block.name === 'core/paragraph' && String(block.attributes.content || '').includes('https://t.me/infsysgroup'), 'text social links');
  const oldSocialText = String(textSocials.attributes.content);
  for (const item of socials) if (!oldSocialText.includes(item.url)) throw new Error(`Existing social link changed: ${item.url}`);
  const socialIcons = b('social-links', {
    openInNewTab: true,
    customIconColor: '#ffffff', iconColorValue: '#ffffff',
    className: 'is-style-logos-only', size: 'has-small-icon-size',
    layout: { type: 'flex', justifyContent: 'right', flexWrap: 'nowrap' },
    style: { spacing: { blockGap: '10px' } },
  }, socials.map(item => b('social-link', {
    service: item.service, url: item.url, label: item.label, rel: 'noopener noreferrer',
  })));
  legalRow.innerBlocks.splice(legalRow.innerBlocks.indexOf(textSocials), 1, socialIcons);
  return footerBlocks;
}
