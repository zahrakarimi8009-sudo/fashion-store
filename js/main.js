/* =========================================================
   فروشگاه نیلا | اسکریپت مشترک
   ------------------------------------------------------------
   - ابزارهای عمومی (اعداد فارسی، قیمت، تخفیف)
   - سبد خرید و علاقه‌مندی‌ها (localStorage + پشتیبند حافظه‌ای)
   - رندر کارت محصول (تصویر دوم + قلب علاقه‌مندی + امتیاز)
   - رفتار هدر: منوی موبایل، جستجو، نشان‌ها، اسکرول
   ========================================================= */

/* ---------- ابزارهای عمومی ---------- */
const faNum = (n) => Number(n).toLocaleString('fa-IR');
const fmtPrice = (n) => faNum(n) + ' تومان';
const discountOf = (p) => (p.salePrice ? Math.round((1 - p.salePrice / p.price) * 100) : 0);
const effPrice = (p) => (p.salePrice || p.price);
const getProduct = (id) => PRODUCTS.find((p) => p.id === Number(id));

/* آیکون‌های SVG */
const ICONS = {
  check:  '<svg class="ic" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>',
  info:   '<svg class="ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
  alert:  '<svg class="ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
  bag:    '<svg class="ic" viewBox="0 0 24 24"><path d="M6 8h12l1.2 13H4.8L6 8z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/></svg>',
  heart:  '<svg class="ic" viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.2l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>',
  star:   '<svg class="ic ic-fill" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
  arrow:  '<svg class="ic" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="11 18 5 12 11 6"/></svg>'
};

/* ---------- ذخیره‌سازی لایه‌ای: localStorage ← sessionStorage ← کوکی ← حافظه ----------
   در مرورگر معمولی از localStorage استفاده می‌شود؛ اما در پیش‌نمایش درون‌برنامه‌ای
   (iframe بدون allow-same-origin) که دسترسی به localStorage را ندارد، از کوکی و سپس
   حافظه استفاده می‌شود تا سبد خرید و علاقه‌مندی‌ها هنگام جابه‌جایی بین صفحات گم نشوند. */
const _memory = {};
const _storageKind = (function () {
  const t = '__nila_test__';
  try { window.localStorage.setItem(t, '1'); window.localStorage.removeItem(t); return 'local'; } catch (e) {}
  try { window.sessionStorage.setItem(t, '1'); window.sessionStorage.removeItem(t); return 'session'; } catch (e) {}
  try {
    document.cookie = 'nila_ck_test=1; path=/';
    if (document.cookie.indexOf('nila_ck_test=1') > -1) {
      document.cookie = 'nila_ck_test=; path=/; max-age=0';
      return 'cookie';
    }
  } catch (e) {}
  return 'memory';
})();

function _b64enc(str) { return btoa(unescape(encodeURIComponent(str))); }
function _b64dec(str) { return decodeURIComponent(escape(atob(str))); }

function _rawRead(key) {
  if (_storageKind === 'local') return window.localStorage.getItem(key);
  if (_storageKind === 'session') return window.sessionStorage.getItem(key);
  if (_storageKind === 'cookie') {
    const m = document.cookie.split('; ').find((r) => r.indexOf(key + '=') === 0);
    return m ? m.slice(key.length + 1) : null;
  }
  return null;
}
function _rawWrite(key, raw) {
  if (_storageKind === 'local') window.localStorage.setItem(key, raw);
  else if (_storageKind === 'session') window.sessionStorage.setItem(key, raw);
  else if (_storageKind === 'cookie') document.cookie = key + '=' + raw + '; path=/; max-age=2592000; SameSite=Lax';
}

function store(key, value) {
  _memory[key] = value;
  try { _rawWrite(key, _b64enc(JSON.stringify(value))); } catch (e) {}
}
function load(key, fallback) {
  try {
    const raw = _rawRead(key);
    if (raw !== null) {
      try { return JSON.parse(_b64dec(raw)); } catch (e1) {}
      try { return JSON.parse(raw); } catch (e2) {} // سازگاری با نسخه‌های قدیمی (JSON خام)
    }
  } catch (e) {}
  return _memory[key] !== undefined ? _memory[key] : fallback;
}

/* ---------- عبور وضعیت بین صفحات (پشتیبندِ ذخیره‌سازی) ----------
   در محیط‌هایی که localStorage / sessionStorage / کوکی در دسترس نیستند
   (مانند پیش‌نمایش درون‌برنامه‌ای ایزوله)، وضعیت سبد خرید و علاقه‌مندی‌ها
   خودش را روی هر لینک به صفحه‌ی دیگر (در تکه‌ی #s=... از URL) سوار می‌کند
   و صفحه‌ی مقصد همان لحظه بار شدن، آن را دریافت و اعمال می‌کند. */
const STATE_T_KEY = 'nila_state_t';

function _touchState() { try { store(STATE_T_KEY, Date.now()); } catch (e) {} }

function _b64encUrl(str) {
  return _b64enc(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}
function _b64decUrl(str) {
  let t = str.replace(/-/g, '+').replace(/_/g, '/');
  while (t.length % 4) t += '=';
  return _b64dec(t);
}

function _stateHash() {
  try {
    const c = getCart(), w = getWish(), u = getUser();
    if (!c.length && !w.length && !u) return '';
    const payload = { t: load(STATE_T_KEY, 0) || 0, c, w, u: u || null };
    return _b64encUrl(JSON.stringify(payload));
  } catch (e) { return ''; }
}

function _readStateHash() {
  try {
    const m = /s=([A-Za-z0-9_-]+)/.exec((window.location && window.location.hash) || '');
    if (!m) return null;
    const obj = JSON.parse(_b64decUrl(m[1]));
    if (obj && typeof obj.t === 'number') return obj;
  } catch (e) {}
  return null;
}

function hydrateState() {
  const payload = _readStateHash();
  if (!payload) return;
  const localT = load(STATE_T_KEY, 0) || 0;
  if (payload.t > localT) {
    saveCart(payload.c || []);
    saveWish(payload.w || []);
    if (payload.u) saveUser(payload.u); else clearUser();
    _touchState();
  }
}

function syncStateLinks() {
  const h = _stateHash();
  let anchors = [];
  try { anchors = document.querySelectorAll('a[href]'); } catch (e) { return; }
  anchors.forEach((a) => {
    const href = a.getAttribute('href');
    if (!href || !/^[\w-]+\.html/.test(href)) return;
    const clean = href.split('#')[0];
    const target = clean + (h ? '#s=' + h : '');
    if (a.getAttribute('href') !== target) a.setAttribute('href', target);
  });
}

function nilaGo(url) {
  const h = _stateHash();
  window.location.href = url + (h ? '#s=' + h : '');
}

/* ---------- سبد خرید ---------- */
const CART_KEY = 'nila_cart_v1';
function getCart() { return load(CART_KEY, []); }
function saveCart(items) { store(CART_KEY, items); _touchState(); syncStateLinks(); updateCartBadge(); }

function addToCart(id, size, color, qty = 1) {
  const p = getProduct(id);
  if (!p) return false;
  const key = id + '|' + size + '|' + color;
  const items = getCart();
  const existing = items.find((i) => i.key === key);
  if (existing) existing.qty += qty;
  else items.push({ key, id, size, color, qty });
  saveCart(items);
  return true;
}

function cartCount() { return getCart().reduce((s, i) => s + i.qty, 0); }

function updateCartBadge() {
  const badge = document.getElementById('cartBadge');
  if (!badge) return;
  const count = cartCount();
  badge.textContent = faNum(count);
  badge.classList.toggle('show', count > 0);
  if (count > 0) { badge.classList.remove('bump'); void badge.offsetWidth; badge.classList.add('bump'); }
  renderCartDrawer();
}

/* ---------- علاقه‌مندی‌ها (ویش‌لیست) ---------- */
const WISH_KEY = 'nila_wishlist_v1';
function getWish() { return load(WISH_KEY, []); }
function saveWish(list) { store(WISH_KEY, list); _touchState(); syncStateLinks(); updateWishBadge(); }

function toggleWish(id) {
  const list = getWish();
  const i = list.indexOf(Number(id));
  let added;
  if (i > -1) { list.splice(i, 1); added = false; }
  else { list.push(Number(id)); added = true; }
  saveWish(list);
  return added;
}

function updateWishBadge() {
  const badge = document.getElementById('wishBadge');
  if (!badge) return;
  const count = getWish().length;
  badge.textContent = faNum(count);
  badge.classList.toggle('show', count > 0);
  if (count > 0) { badge.classList.remove('bump'); void badge.offsetWidth; badge.classList.add('bump'); }
}

/* ---------- حساب کاربری (نمایشی — برای مرحله‌ی PHP + MySQL آماده‌سازی شده) ---------- */
const USER_KEY = 'nila_user_v1';
function getUser() { const v = load(USER_KEY, null); return (v && typeof v === 'object') ? v : null; }
function saveUser(u) { store(USER_KEY, u); _touchState(); syncStateLinks(); }
function clearUser() { store(USER_KEY, null); _touchState(); syncStateLinks(); }

/* ---------- دراور سبد خرید (کاشه‌شونده از سمت چپ) ---------- */
function cartTotals(items) {
  let final = 0, saveable = 0;
  items.forEach((it) => {
    const p = getProduct(it.id);
    if (!p) return;
    final += effPrice(p) * it.qty;
    saveable += (p.price - effPrice(p)) * it.qty;
  });
  const shipping = (final > 0 && final < 2000000) ? 150000 : 0;
  return { final, saveable, shipping, total: final + shipping };
}

function renderCartDrawer() {
  const drawer = document.getElementById('cartDrawer');
  if (!drawer) return;
  const box = document.getElementById('cdItems');
  const foot = document.getElementById('cdFoot');
  const countEl = document.getElementById('cdCount');
  const items = getCart();
  const n = cartCount();
  if (countEl) countEl.textContent = n ? '(' + faNum(n) + ' کالا)' : '';
  if (!box) return;
  if (!items.length) {
    box.innerHTML = `<div class="cd-empty">
      <svg class="ic" viewBox="0 0 24 24"><path d="M6 8h12l1.2 13H4.8L6 8z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/></svg>
      <p>سبد خرید شما خالی است</p>
      <a class="btn btn-outline btn-sm" href="shop.html">شروع خرید</a>
    </div>`;
    if (foot) foot.style.display = 'none';
    return;
  }
  if (foot) foot.style.display = '';
  box.innerHTML = items.map((it) => {
    const p = getProduct(it.id);
    if (!p) return '';
    return `
    <div class="cd-item">
      <a class="cd-img" href="product.html?id=${p.id}"><img src="${p.img}" alt="${p.name}"></a>
      <div class="cd-info">
        <a class="cd-name" href="product.html?id=${p.id}">${p.name}</a>
        <span class="cd-meta">سایز: ${it.size} | رنگ: ${it.color}</span>
        <div class="cd-line">
          <div class="qty qty-sm">
            <button type="button" data-cd-dec="${it.key}" aria-label="کاهش تعداد"><svg class="ic" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
            <span>${faNum(it.qty)}</span>
            <button type="button" data-cd-inc="${it.key}" aria-label="افزایش تعداد"><svg class="ic" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
          </div>
          <b class="cd-price">${faNum(effPrice(p) * it.qty)} <small>تومان</small></b>
        </div>
      </div>
      <button class="cd-remove" type="button" data-cd-remove="${it.key}" aria-label="حذف از سبد">
        <svg class="ic" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
      </button>
    </div>`;
  }).join('');
  const t = cartTotals(items);
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  set('cdSubtotal', fmtPrice(t.final));
  set('cdShipping', t.shipping ? fmtPrice(t.shipping) : 'رایگان');
  set('cdTotal', fmtPrice(t.total));
  const saveRow = document.getElementById('cdSaveRow');
  if (saveRow) {
    saveRow.style.display = t.saveable ? 'flex' : 'none';
    set('cdSave', '− ' + fmtPrice(t.saveable));
  }
}

/* ---------- توست (اعلان کوتاه) ---------- */
function toast(msg, type = 'success') {
  let wrap = document.getElementById('toastWrap');
  if (!wrap) {
    wrap = document.createElement('div');
    wrap.id = 'toastWrap';
    wrap.className = 'toast-wrap';
    document.body.appendChild(wrap);
  }
  const el = document.createElement('div');
  const icon = type === 'success' ? ICONS.check : type === 'error' ? ICONS.alert : ICONS.info;
  el.className = 'toast toast-' + type;
  el.innerHTML = icon + '<span>' + msg + '</span>';
  wrap.appendChild(el);
  requestAnimationFrame(() => el.classList.add('show'));
  setTimeout(() => {
    el.classList.remove('show');
    setTimeout(() => el.remove(), 350);
  }, 2900);
}

/* ---------- رندر کارت محصول ---------- */
function starsHtml(rating) {
  return ICONS.star.repeat(Math.round(rating));
}

function productCard(p, opts = {}) {
  const off = discountOf(p);
  const wished = getWish().includes(p.id);
  const hasSecond = p.img2 && p.img2 !== p.img;
  return `
  <article class="product-card reveal">
    <div class="pc-media">
      <a href="product.html?id=${p.id}" aria-label="${p.name}">
        <img class="pc-img1" src="${p.img}" alt="${p.name}" loading="lazy">
        ${hasSecond ? `<img class="pc-img2" src="${p.img2}" alt="" aria-hidden="true" loading="lazy">` : ''}
      </a>
      <div class="pc-badges">
        ${p.isNew ? '<span class="badge badge-new">جدید</span>' : ''}
        ${off ? `<span class="badge badge-sale">${faNum(off)}٪</span>` : ''}
      </div>
      <button class="pc-wish${wished ? ' active' : ''}" data-wish="${p.id}" aria-label="افزودن به علاقه‌مندی‌ها" title="علاقه‌مندی">${ICONS.heart}</button>
      <div class="pc-actions">
        <button class="btn btn-primary" data-add="${p.id}">${ICONS.bag} افزودن به سبد خرید</button>
      </div>
    </div>
    <div class="pc-body">
      <span class="pc-cat">${CATEGORIES[p.category] ? CATEGORIES[p.category].name : ''}</span>
      <h3 class="pc-name"><a href="product.html?id=${p.id}">${p.name}</a></h3>
      ${opts.rating ? `<div class="pc-rate"><span class="stars">${starsHtml(p.rating)}</span><b>${faNum(p.rating)}</b><span>(${faNum(p.sales)} فروش)</span></div>` : ''}
      <div class="pc-price">
        ${p.salePrice ? `<span class="price-old">${fmtPrice(p.price)}</span>` : ''}
        <span class="price-now">${faNum(effPrice(p))} <small>تومان</small></span>
        ${off ? `<span class="badge badge-soft pc-off">تخفیف</span>` : ''}
      </div>
    </div>
  </article>`;
}

function renderProducts(container, list, opts = {}) {
  if (!container) return;
  container.innerHTML = list.map((p) => productCard(p, opts)).join('');
  bindCardActions(container);
  observeReveals(container);
}

/* دکمه‌های کارت: افزودن به سبد + علاقه‌مندی */
function bindCardActions(scope) {
  scope.querySelectorAll('[data-add]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const p = getProduct(btn.dataset.add);
      if (!p) return;
      addToCart(p.id, p.sizes[0], p.colors[0].name, 1);
      toast('«' + p.name + '» به سبد خرید اضافه شد');
    });
  });
  scope.querySelectorAll('[data-wish]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const p = getProduct(btn.dataset.wish);
      const added = toggleWish(p.id);
      btn.classList.toggle('active', added);
      toast(added ? '«' + p.name + '» به علاقه‌مندی‌ها اضافه شد' : '«' + p.name + '» از علاقه‌مندی‌ها حذف شد', added ? 'success' : 'info');
    });
  });
}

/* ---------- ظهور نرم المان‌ها هنگام اسکرول ---------- */
let _io = null;
if ('IntersectionObserver' in window) {
  _io = new IntersectionObserver(
    (entries) => entries.forEach((e) => {
      if (e.isIntersecting) {
        e.target.classList.add('in');
        _io.unobserve(e.target);
      }
    }),
    { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
  );
}
function observeReveals(scope = document) {
  scope.querySelectorAll('.reveal:not(.in)').forEach((el) => {
    if (_io) _io.observe(el);
    else el.classList.add('in');
  });
}

/* ---------- تب‌های هم‌پن ---------- */
function initPanes(scope) {
  scope.querySelectorAll('.tab-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const paneId = btn.dataset.pane;
      if (!paneId) return;
      const root = btn.closest('[data-tabs]') || scope;
      root.querySelectorAll('.tab-btn').forEach((b) => b.classList.toggle('active', b === btn));
      root.querySelectorAll('.tab-pane').forEach((pn) => pn.classList.toggle('active', pn.dataset.pane === paneId));
    });
  });
}

/* ---------- راه‌اندازی مشترک هدر و فوتر ---------- */
function initSite() {
  try { hydrateState(); syncStateLinks(); } catch (e) {}
  const header = document.getElementById('siteHeader');
  const nav = document.getElementById('mainNav');
  const overlay = document.getElementById('navOverlay');
  const navToggle = document.getElementById('navToggle');
  const searchToggle = document.getElementById('searchToggle');
  const searchPanel = document.getElementById('searchPanel');
  const searchForm = document.getElementById('searchForm');
  const accountBtn = document.getElementById('accountBtn');
  const wishBtn = document.getElementById('wishBtn');
  const toTop = document.getElementById('toTop');
  const newsletter = document.getElementById('newsletterForm');

  /* اسکرول: سایه‌ی هدر + دکمه‌ی بازگشت به بالا */
  const onScroll = () => {
    if (header) header.classList.toggle('scrolled', window.scrollY > 8);
    if (toTop) toTop.classList.toggle('show', window.scrollY > 600);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* منوی موبایل */
  const closeNav = () => {
    nav && nav.classList.remove('open');
    overlay && overlay.classList.remove('show');
    document.body.classList.remove('no-scroll');
  };
  if (navToggle && nav && overlay) {
    navToggle.addEventListener('click', () => {
      const open = nav.classList.toggle('open');
      overlay.classList.toggle('show', open);
      document.body.classList.toggle('no-scroll', open);
    });
    const navClose = document.getElementById('navClose');
    if (navClose) navClose.addEventListener('click', closeNav);
    overlay.addEventListener('click', closeNav);
    nav.querySelectorAll('.nav-list > li > a').forEach((a) => {
      a.addEventListener('click', (e) => {
        if (window.innerWidth <= 1024 && a.closest('.has-drop')) {
          e.preventDefault();
          a.closest('.has-drop').classList.toggle('open');
        } else if (window.innerWidth <= 1024) {
          closeNav();
        }
      });
    });
  }

  /* بستن منوی کشویی با کلیک بیرون از آن */
  document.addEventListener('click', (e) => {
    document.querySelectorAll('.has-drop.open').forEach((d) => {
      if (!d.contains(e.target)) d.classList.remove('open');
    });
  });

  /* پنل جستجو */
  if (searchToggle && searchPanel) {
    searchToggle.addEventListener('click', () => {
      const open = searchPanel.classList.toggle('open');
      if (open) {
        const input = searchPanel.querySelector('input');
        input && input.focus();
      }
    });
    document.addEventListener('click', (e) => {
      if (!searchPanel.contains(e.target) && !searchToggle.contains(e.target)) {
        searchPanel.classList.remove('open');
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') searchPanel.classList.remove('open');
    });
  }
  if (searchForm) {
    searchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const q = (searchForm.querySelector('input').value || '').trim();
      nilaGo(q ? 'shop.html?q=' + encodeURIComponent(q) : 'shop.html');
    });
  }

  /* حساب کاربری ← پنل کاربری (اگر وارد شده باشد)، وگرنه صفحه‌ی ورود */
  if (accountBtn) {
    accountBtn.addEventListener('click', () => { nilaGo(getUser() ? 'account.html' : 'login.php'); });
  }

  /* علاقه‌مندی‌ها ← صفحه‌ی علاقه‌مندی */
  if (wishBtn) {
    wishBtn.addEventListener('click', () => { nilaGo('wishlist.html'); });
  }

  /* دراور سبد خرید (از سمت چپ) */
  const cartBtn = document.getElementById('cartBtn');
  const cartDrawer = document.getElementById('cartDrawer');
  const cartOverlay = document.getElementById('cartOverlay');
  const closeDrawer = () => {
    cartDrawer && cartDrawer.classList.remove('open');
    cartOverlay && cartOverlay.classList.remove('show');
    document.body.classList.remove('no-scroll');
  };
  if (cartBtn && cartDrawer) {
    cartBtn.addEventListener('click', () => {
      renderCartDrawer();
      cartDrawer.classList.add('open');
      cartOverlay && cartOverlay.classList.add('show');
      document.body.classList.add('no-scroll');
    });
    const cClose = document.getElementById('cartClose');
    if (cClose) cClose.addEventListener('click', closeDrawer);
    cartOverlay && cartOverlay.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawer(); });
    const cOut = document.getElementById('cdCheckout');
    if (cOut) cOut.addEventListener('click', () => { nilaGo('checkout.html'); });
    const cItems = document.getElementById('cdItems');
    cItems && cItems.addEventListener('click', (e) => {
      const dec = e.target.closest('[data-cd-dec]');
      const inc = e.target.closest('[data-cd-inc]');
      const rem = e.target.closest('[data-cd-remove]');
      if (!dec && !inc && !rem) return;
      const items = getCart();
      if (dec) {
        const it = items.find((i) => i.key === dec.dataset.cdDec);
        if (it) { it.qty--; if (it.qty <= 0) items.splice(items.indexOf(it), 1); saveCart(items); }
      } else if (inc) {
        const it = items.find((i) => i.key === inc.dataset.cdInc);
        if (it && it.qty < 10) { it.qty++; saveCart(items); }
      } else {
        saveCart(items.filter((i) => i.key !== rem.dataset.cdRemove));
        toast('محصول از سبد خرید حذف شد.', 'info');
      }
    });
  }

  /* بازگشت به بالا */
  if (toTop) {
    toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* خبرنامه (نمونه) */
  if (newsletter) {
    newsletter.addEventListener('submit', (e) => {
      e.preventDefault();
      toast('عضویت شما در خبرنامه ثبت شد (نمونه نمایشی).');
      newsletter.reset();
    });
  }

  /* پارالاکس ملایم صفحه‌ی ورود (هم‌خانواده با انیمیشن خبرنامه) */
  const lgWrap = document.querySelector('.lg-wrap');
  if (lgWrap && window.matchMedia('(pointer:fine)').matches) {
    const blobs = Array.from(lgWrap.querySelectorAll('.lg-blob'));
    if (blobs.length) {
      let raf = 0;
      lgWrap.addEventListener('mousemove', (e) => {
        const r = lgWrap.getBoundingClientRect();
        const x = (e.clientX - r.left) / Math.max(r.width, 1) - .5;
        const y = (e.clientY - r.top) / Math.max(r.height, 1) - .5;
        if (raf) cancelAnimationFrame(raf);
        raf = requestAnimationFrame(() => {
          blobs.forEach((b, i) => {
            const f = (i + 1) * 14;
            b.style.transform = 'translate(' + (x * f).toFixed(1) + 'px,' + (y * f).toFixed(1) + 'px)';
          });
        });
      });
    }
  }

  updateCartBadge();
  updateWishBadge();
  observeReveals();
}

document.addEventListener('DOMContentLoaded', initSite);

/* ---------- تاریخ شمسی: میلادی (Y-m-d) → «۲۶ شهریور ۱۴۰» ----------
   الگوریتم رسمی jalaali-js (کتابخانه‌ی استاندارد تقویم جلالی) */
const _JALALI_BREAKS = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181,
  1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
const _jdiv = (a, b) => ~~(a / b);
const _jmod = (a, b) => a - ~~(a / b) * b;

function _jalCal(jy, withoutLeap) {
  const bl = _JALALI_BREAKS.length;
  const gy = jy + 621;
  let leapJ = -14;
  let jp = _JALALI_BREAKS[0];
  let jump = 0, leap = 0, march = 0, n = 0;
  for (let i = 1; i < bl; i += 1) {
    const jm = _JALALI_BREAKS[i];
    jump = jm - jp;
    if (jy < jm) break;
    leapJ = leapJ + _jdiv(jump, 33) * 8 + _jdiv(_jmod(jump, 33), 4);
    jp = jm;
  }
  n = jy - jp;
  leapJ = leapJ + _jdiv(n, 33) * 8 + _jdiv(_jmod(n, 33) + 3, 4);
  if (_jmod(jump, 33) === 4 && jump - n === 4) leapJ += 1;
  const leapG = _jdiv(gy, 4) - _jdiv((_jdiv(gy, 100) + 1) * 3, 4) - 150;
  march = 20 + leapJ - leapG;
  if (withoutLeap) return { gy: gy, march: march };
  if (jump - n < 6) n = n - jump + _jdiv(jump + 4, 33) * 33;
  leap = _jmod(_jmod(n + 1, 33) - 1, 4);
  if (leap === -1) leap = 4;
  return { leap: leap, gy: gy, march: march };
}

function _g2d(gy, gm, gd) {
  let d = _jdiv((gy + _jdiv(gm - 8, 6) + 100100) * 1461, 4)
    + _jdiv(153 * _jmod(gm + 9, 12) + 2, 5)
    + gd - 34840408;
  d = d - _jdiv(_jdiv(gy + 100100 + _jdiv(gm - 8, 6), 100) * 3, 4) + 752;
  return d;
}

function _d2g(jdn) {
  let j = 4 * jdn + 139361631;
  j = j + _jdiv(_jdiv(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
  const i = _jdiv(_jmod(j, 1461), 4) * 5 + 308;
  const gd = _jdiv(_jmod(i, 153), 5) + 1;
  const gm = _jmod(_jdiv(i, 153), 12) + 1;
  const gy = _jdiv(j, 1461) - 100100 + _jdiv(8 - gm, 6);
  return { gy: gy, gm: gm, gd: gd };
}

function _d2j(jdn) {
  const gy = _d2g(jdn).gy;
  let jy = gy - 621;
  const r = _jalCal(jy, false);
  const jdn1f = _g2d(gy, 3, r.march);
  let k = jdn - jdn1f;
  let jd, jm;
  if (k >= 0) {
    if (k <= 185) {
      jm = 1 + _jdiv(k, 31);
      jd = _jmod(k, 31) + 1;
      return { jy: jy, jm: jm, jd: jd };
    }
    k -= 186;
  } else {
    jy -= 1;
    k += 179;
    if (r.leap === 1) k += 1;
  }
  jm = 7 + _jdiv(k, 30);
  jd = _jmod(k, 30) + 1;
  return { jy: jy, jm: jm, jd: jd };
}

function toJalali(dateStr) {
  const s = String(dateStr || '').slice(0, 10).replace(/-/g, '/');
  const d = new Date(s);
  if (isNaN(d.getTime())) return '';
  const MONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  const r = _d2j(_g2d(d.getFullYear(), d.getMonth() + 1, d.getDate()));
  return faNum(r.jd) + ' ' + MONTHS[r.jm - 1] + ' ' + faNum(r.jy);
}
