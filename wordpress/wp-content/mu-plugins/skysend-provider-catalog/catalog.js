(() => {
  'use strict';

  const root = document.querySelector('#provider-categories');
  const endpoint = window.SkySendProviderCatalog?.endpoint;
  if (!root || !endpoint) return;

  const panels = [...root.querySelectorAll('[role="tabpanel"]')];
  const shells = panels.map((panel) => {
    const shell = panel.querySelector('.sky-provider-catalog');
    const match = shell?.id?.match(/^provider-catalog-([a-z0-9_-]{1,64})$/);
    if (match) {
      shell.dataset.category = match[1];
    }
    return shell;
  });
  if (!shells.length || shells.some((shell) => !shell?.dataset.category || !shell.querySelector('.sky-provider-grid')) ||
      new Set(shells.map((shell) => shell.dataset.category)).size !== shells.length) return;

  function ensureControls(shell) {
    if (shell.querySelector('.sky-provider-pager')) return;
    const pager = document.createElement('div');
    pager.className = 'sky-provider-pager';
    const prev = document.createElement('button');
    prev.className = 'sky-provider-prev';
    prev.type = 'button';
    prev.disabled = true;
    prev.setAttribute('aria-label', 'Предыдущая страница');
    prev.textContent = '‹';
    const counter = document.createElement('span');
    counter.className = 'sky-provider-counter';
    counter.setAttribute('aria-live', 'polite');
    counter.textContent = 'Загрузка…';
    const next = document.createElement('button');
    next.className = 'sky-provider-next';
    next.type = 'button';
    next.disabled = true;
    next.setAttribute('aria-label', 'Следующая страница');
    next.textContent = '›';
    pager.append(prev, counter, next);
    const status = document.createElement('div');
    status.className = 'sky-provider-status';
    status.setAttribute('role', 'status');
    status.hidden = true;
    const statusText = document.createElement('span');
    statusText.className = 'sky-provider-status-text';
    const retry = document.createElement('button');
    retry.className = 'sky-provider-retry';
    retry.type = 'button';
    retry.hidden = true;
    retry.textContent = 'Повторить';
    status.append(statusText, retry);
    shell.append(pager, status);
  }

  const state = new WeakMap();
  let activeShell = null;
  let sectionVisible = false;

  for (const shell of shells) {
    ensureControls(shell);
    state.set(shell, { page: 1, requestedPage: 1, pages: 1, request: null, token: 0 });
    shell.querySelector('.sky-provider-prev')?.addEventListener('click', () => {
      const current = state.get(shell);
      if (shell === activeShell && current.page > 1) load(shell, current.page - 1);
    });
    shell.querySelector('.sky-provider-next')?.addEventListener('click', () => {
      const current = state.get(shell);
      if (shell === activeShell && current.page < current.pages) load(shell, current.page + 1);
    });
    shell.querySelector('.sky-provider-retry')?.addEventListener('click', () => {
      if (shell === activeShell) load(shell, state.get(shell).requestedPage);
    });
  }

  function selectedShell() {
    const panel = panels.find((item) => !item.hidden);
    return panel?.querySelector('.sky-provider-catalog[data-category]') || null;
  }

  function syncActivePanel() {
    if (!sectionVisible) return;
    const next = selectedShell();
    if (!next || next === activeShell) return;
    if (activeShell) {
      const previous = state.get(activeShell);
      previous.request?.abort();
      previous.token += 1;
      activeShell.querySelector('.sky-provider-grid').replaceChildren();
    }
    activeShell = next;
    load(next, state.get(next).page);
  }

  function showStatus(shell, message, retry) {
    const status = shell.querySelector('.sky-provider-status');
    status.querySelector('.sky-provider-status-text').textContent = message;
    status.querySelector('.sky-provider-retry').hidden = !retry;
    status.hidden = false;
  }

  function hideStatus(shell) {
    shell.querySelector('.sky-provider-status').hidden = true;
  }

  function localLogo(value) {
    if (typeof value !== 'string' || !/^\/wp-content\/uploads\/[A-Za-z0-9/_-]+\.(?:png|jpe?g|webp|svg)$/i.test(value)) return null;
    return new URL(value, location.origin).href;
  }

  function render(shell, items) {
    const grid = shell.querySelector('.sky-provider-grid');
    const fragment = document.createDocumentFragment();
    for (const item of items) {
      const card = document.createElement('div');
      card.className = 'wp-block-group sky-provider-card';
      card.dataset.providerId = item.id;
      const logo = localLogo(item.logo);
      if (logo) {
        const figure = document.createElement('figure');
        figure.className = 'wp-block-image sky-provider-card__image';
        const image = document.createElement('img');
        image.src = logo;
        image.alt = '';
        image.loading = 'lazy';
        image.decoding = 'async';
        figure.append(image);
        card.append(figure);
      } else {
        card.classList.add('sky-provider-no-logo');
      }
      const name = document.createElement('p');
      name.textContent = item.name;
      name.title = item.name;
      card.append(name);
      fragment.append(card);
    }
    grid.replaceChildren(fragment);
  }

  async function load(shell, page) {
    if (shell !== activeShell) return;
    const current = state.get(shell);
    current.request?.abort();
    current.token += 1;
    current.requestedPage = page;
    const token = current.token;
    const controller = new AbortController();
    current.request = controller;
    const grid = shell.querySelector('.sky-provider-grid');
    const prev = shell.querySelector('.sky-provider-prev');
    const next = shell.querySelector('.sky-provider-next');
    prev.disabled = true;
    next.disabled = true;
    grid.setAttribute('aria-busy', 'true');
    showStatus(shell, 'Загрузка провайдеров…', false);

    const url = new URL(endpoint, location.href);
    url.searchParams.set('category', shell.dataset.category);
    url.searchParams.set('page', String(page));
    try {
      const response = await fetch(url, { signal: controller.signal, credentials: 'same-origin', headers: { Accept: 'application/json' } });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const payload = await response.json();
      if (shell !== activeShell || current.token !== token) return;
      if (payload.category !== shell.dataset.category || payload.page !== page || !Array.isArray(payload.items) || payload.items.length > 9 || !Number.isInteger(payload.total) || !Number.isInteger(payload.pages) || payload.pages < 1) throw new Error('Invalid catalog response');

      render(shell, payload.items);
      current.page = page;
      current.pages = payload.pages;
      shell.querySelector('.sky-provider-counter').textContent = `Страница ${page} из ${payload.pages}`;
      prev.disabled = page <= 1;
      next.disabled = page >= payload.pages;
      hideStatus(shell);
    } catch (error) {
      if (error.name !== 'AbortError' && shell === activeShell && current.token === token) {
        showStatus(shell, 'Не удалось загрузить провайдеров.', true);
      }
    } finally {
      if (current.token === token) {
        current.request = null;
        grid.setAttribute('aria-busy', 'false');
      }
    }
  }

  const tabObserver = new MutationObserver(syncActivePanel);
  for (const panel of panels) tabObserver.observe(panel, { attributes: true, attributeFilter: ['hidden'] });

  const section = document.querySelector('#providers') || root;
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) return;
      sectionVisible = true;
      observer.disconnect();
      syncActivePanel();
    }, { rootMargin: '0px' });
    observer.observe(section);
  } else {
    sectionVisible = true;
    syncActivePanel();
  }
})();
