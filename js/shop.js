/* =========================================================
   فروشگاه نیلا | صفحه فروشگاه
   ------------------------------------------------------------
   - فیلترهای آکاردئونی: دسته‌بندی (با تعداد)، محدوده‌ی قیمت
     (اسلایدر دو‌تکمه‌ای)، سایز، رنگ و سایر
   - مرتب‌سازی (پیش‌فرض / جدیدترین / ارزان‌ترین / گران‌ترین / پرطرفدارترین)
   - پشتیبانی از پارامترهای آدرس: ?category= / ?type=new / ?sale=1 / ?q=
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  nilaLoadProducts().then(() => {

    const grid = document.getElementById('shopGrid');
    const emptyBox = document.getElementById('shopEmpty');
    const countEl = document.getElementById('resultCount');
    const titleEl = document.getElementById('shopTitle');
    const params = new URLSearchParams(window.location.search);

    const P_MIN = 0;
    const P_MAX = 5000000;
    const P_STEP = 50000;

    /* ---- وضعیت فیلترها ---- */
    const state = {
      cat: params.get('category') || '',
      min: null,
      max: null,
      sale: params.get('sale') === '1',
      nw: params.get('type') === 'new',
      q: (params.get('q') || '').trim(),
      sizes: [],
      colors: [],
      sort: 'default'
    };

    const sortSelect = document.getElementById('sortSelect');
    const saleOnly = document.getElementById('saleOnly');
    const newOnly = document.getElementById('newOnly');
    const sizeBox = document.getElementById('sizeChips');
    const colorBox = document.getElementById('colorDots');

    /* اعمال وضعیت ارسالی از آدرس روی فرم */
    saleOnly.checked = state.sale;
    newOnly.checked = state.nw;

    /* ---- آکاردئون گروه‌های فیلتر ---- */
    document.querySelectorAll('.f-group-head').forEach((head) => {
      head.addEventListener('click', () => {
        const group = head.closest('.f-group');
        const open = group.classList.toggle('open');
        head.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });

    /* ---- دسته‌بندی (با تعداد هر دسته) ---- */
    const catList = document.getElementById('catList');
    if (catList) {
      const cats = [{ id: '', name: 'همه‌ی دسته‌ها' }]
        .concat(Object.keys(CATEGORIES).map((cid) => ({ id: cid, name: CATEGORIES[cid].name })));
      catList.innerHTML = cats.map((c) => {
        const count = c.id ? PRODUCTS.filter((p) => p.category === c.id).length : PRODUCTS.length;
        return `<label class="f-opt"><input type="radio" name="fcat" value="${c.id}"${c.id === state.cat ? ' checked' : ''}> ${c.name}<span class="f-count">${faNum(count)}</span></label>`;
      }).join('');
      catList.querySelectorAll('input[name="fcat"]').forEach((radio) => {
        radio.addEventListener('change', () => { state.cat = radio.value; apply(); });
      });
    }

    /* ---- اسلایدر دو‌تکمه‌ای محدوده‌ی قیمت ---- */
    const rMin = document.getElementById('rangeMin');
    const rMax = document.getElementById('rangeMax');
    const rangeFill = document.getElementById('rangeFill');
    const minLbl = document.getElementById('rangeMinLbl');
    const maxLbl = document.getElementById('rangeMaxLbl');
    let priceTimer = null;

    const paintRange = () => {
      let lo = Number(rMin.value);
      let hi = Number(rMax.value);
      const pct = (v) => ((v - P_MIN) / (P_MAX - P_MIN)) * 100;
      rangeFill.style.width = (pct(hi) - pct(lo)) + '%';
      rangeFill.style.insetInlineStart = pct(lo) + '%';
      minLbl.textContent = fmtPrice(lo);
      maxLbl.textContent = hi >= P_MAX ? fmtPrice(hi) + '+' : fmtPrice(hi);
      /* وقتی هر دو تکمحرک نزدیک یک سر هستند، تکمحرک «از» رو به رو بیاید */
      rMin.style.zIndex = lo > P_MAX - P_STEP * 2 ? 6 : 4;
      rMax.style.zIndex = 5;
      state.min = lo === P_MIN ? null : lo;
      state.max = hi >= P_MAX ? null : hi;
      clearTimeout(priceTimer);
      priceTimer = setTimeout(apply, 250);
    };
    if (rMin && rMax) {
      rMin.addEventListener('input', () => {
        /* تکمحرک «از» نمی‌تواند از «تا» عبور کند */
        if (Number(rMin.value) > Number(rMax.value)) rMin.value = rMax.value;
        paintRange();
      });
      rMax.addEventListener('input', () => {
        if (Number(rMax.value) < Number(rMin.value)) rMax.value = rMin.value;
        paintRange();
      });
      paintRange();
    }

    /* ---- چیپ‌های سایز (از داده‌ی محصولات) ---- */
    const allSizes = ['S', 'M', 'L', 'XL', 'یک‌سایز'];
    if (sizeBox) {
      sizeBox.innerHTML = allSizes
        .filter((s) => PRODUCTS.some((p) => p.sizes.includes(s)))
        .map((s) => `<button type="button" class="chip" data-size="${s}">${s}</button>`)
        .join('');
      sizeBox.querySelectorAll('.chip').forEach((chip) => {
        chip.addEventListener('click', () => {
          const s = chip.dataset.size;
          const i = state.sizes.indexOf(s);
          if (i > -1) state.sizes.splice(i, 1);
          else state.sizes.push(s);
          chip.classList.toggle('active');
          apply();
        });
      });
    }

    /* ---- دات‌های رنگ (از داده‌ی محصولات) ---- */
    if (colorBox) {
      const seen = new Map();
      PRODUCTS.forEach((p) => p.colors.forEach((c) => { if (!seen.has(c.name)) seen.set(c.name, c); }));
      colorBox.innerHTML = Array.from(seen.values())
        .map((c) => `<button type="button" class="color-dot" data-color="${c.name}" style="background:${c.hex}" title="${c.name}" aria-label="رنگ ${c.name}"></button>`)
        .join('');
      colorBox.querySelectorAll('.color-dot').forEach((dot) => {
        dot.addEventListener('click', () => {
          const c = dot.dataset.color;
          const i = state.colors.indexOf(c);
          if (i > -1) state.colors.splice(i, 1);
          else state.colors.push(c);
          dot.classList.toggle('active');
          apply();
        });
      });
    }

    /* ---- فیلتر + مرتب‌سازی + رندر ---- */
    function apply() {
      let list = PRODUCTS.slice();

      if (state.cat) list = list.filter((p) => p.category === state.cat);
      if (state.nw) list = list.filter((p) => p.isNew);
      if (state.sale) list = list.filter((p) => p.salePrice);
      if (state.min != null) list = list.filter((p) => effPrice(p) >= state.min);
      if (state.max != null) list = list.filter((p) => effPrice(p) <= state.max);
      if (state.sizes.length) list = list.filter((p) => p.sizes.some((s) => state.sizes.includes(s)));
      if (state.colors.length) list = list.filter((p) => p.colors.some((c) => state.colors.includes(c.name)));
      if (state.q) {
        list = list.filter((p) =>
          p.name.includes(state.q) ||
          (CATEGORIES[p.category] && CATEGORIES[p.category].name.includes(state.q))
        );
      }

      switch (state.sort) {
        case 'new':       list.sort((a, b) => (a.date < b.date ? 1 : -1)); break;
        case 'cheap':     list.sort((a, b) => effPrice(a) - effPrice(b)); break;
        case 'expensive': list.sort((a, b) => effPrice(b) - effPrice(a)); break;
        case 'popular':   list.sort((a, b) => b.sales - a.sales); break;
        default: break; /* پیش‌فرض: ترتیب تعریف‌شده در داده */
      }

      renderProducts(grid, list);
      const n = list.length;
      countEl.textContent = faNum(n) + ' محصول' + (state.q ? ' با عبارت «' + state.q + '»' : '');
      if (titleEl) {
        titleEl.textContent = state.cat
          ? CATEGORIES[state.cat].name
          : (state.q ? 'نتایج جستجو' : 'فروشگاه');
      }
      if (emptyBox) emptyBox.hidden = n > 0;
    }

    /* ---- رویدادها ---- */
    saleOnly.addEventListener('change', () => { state.sale = saleOnly.checked; apply(); });
    newOnly.addEventListener('change', () => { state.nw = newOnly.checked; apply(); });
    sortSelect.addEventListener('change', () => { state.sort = sortSelect.value; apply(); });

    /* پاک کردن همه‌ی فیلترها */
    const resetAll = () => {
      state.cat = ''; state.min = null; state.max = null;
      state.sale = false; state.nw = false; state.sort = 'default';
      state.sizes = []; state.colors = [];
      const allRadio = document.querySelector('input[name="fcat"][value=""]');
      if (allRadio) allRadio.checked = true;
      saleOnly.checked = false;
      newOnly.checked = false;
      sortSelect.value = 'default';
      if (rMin) rMin.value = String(P_MIN);
      if (rMax) rMax.value = String(P_MAX);
      if (rMin && rMax) paintRange();
      sizeBox && sizeBox.querySelectorAll('.chip').forEach((c) => c.classList.remove('active'));
      colorBox && colorBox.querySelectorAll('.color-dot').forEach((d) => d.classList.remove('active'));
      apply();
      toast('فیلترها پاک شدند.');
    };
    document.getElementById('resetFilters').addEventListener('click', resetAll);
    const emptyReset = document.getElementById('emptyReset');
    if (emptyReset) emptyReset.addEventListener('click', resetAll);

    /* دکمه‌ی فیلتر در موبایل (نمایش/پنهان‌سازی سایدبار) */
    const filterToggle = document.getElementById('filterToggle');
    const filtersPanel = document.getElementById('filtersPanel');
    if (filterToggle && filtersPanel) {
      filterToggle.addEventListener('click', () => filtersPanel.classList.toggle('open'));
    }

    apply();
  });
});
