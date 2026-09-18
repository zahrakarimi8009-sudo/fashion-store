/* =========================================================
   فروشگاه نیلا | صفحه علاقه‌مندی‌ها
   ------------------------------------------------------------
   - نمایش محصولات ذخیره‌شده از ویش‌لیست
   - نوار «لیست پس‌انداز شده»: شمارنده + افزودن همه به سبد + پاک‌سازی کامل
   - حذف با کلیک روی قلب کارت (با رندر مجدد)
   - حالت خالی
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {

  const grid = document.getElementById('wishGrid');
  const emptyBox = document.getElementById('wishEmpty');
  const countEl = document.getElementById('wishCount');
  const bar = document.getElementById('wishBar');
  const barCount = document.getElementById('wishBarCount');
  const btnAddAll = document.getElementById('wishAddAll');
  const btnClear = document.getElementById('wishClearAll');
  if (!grid) return;

  /* ---------- پاک‌سازی کامل (دو مرحله‌ای، بدون مودال) ---------- */
  const CLEAR_HTML = '<svg class="ic" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg> پاک‌سازی کامل';
  let clearTimer = null;
  function disarmClear() {
    if (!btnClear) return;
    clearTimeout(clearTimer);
    clearTimer = null;
    btnClear.classList.remove('armed');
    btnClear.innerHTML = CLEAR_HTML;
  }
  if (btnClear) {
    btnClear.addEventListener('click', () => {
      if (!btnClear.classList.contains('armed')) {
        /* مرحله‌ی اول: تأیید بصری */
        btnClear.classList.add('armed');
        btnClear.textContent = 'مطمئن؟ برای پاک‌کردن یک بار دیگر بزن';
        clearTimer = setTimeout(disarmClear, 3500);
        return;
      }
      /* مرحله‌ی دوم: پاک‌سازی */
      disarmClear();
      saveWish([]);
      render();
      toast('لیست علاقه‌مندی‌ها پاک شد.', 'info');
    });
  }

  /* ---------- افزودن همه به سبد خرید ---------- */
  if (btnAddAll) {
    btnAddAll.addEventListener('click', () => {
      const ids = getWish();
      if (!ids.length) { toast('لیست علاقه‌مندی‌ها خالی است.', 'error'); return; }
      let added = 0;
      ids.forEach((id) => {
        const p = getProduct(id);
        if (!p) return;
        const size = p.sizes && p.sizes.length ? p.sizes[0] : 'M';
        const color = p.colors && p.colors.length ? p.colors[0].name : 'مشکی';
        addToCart(id, size, color, 1);
        added++;
      });
      if (added) toast(faNum(added) + ' کالا به سبد خرید اضافه شد.', 'success');
    });
  }

  /* ---------- نوار بالایی: نمایش/پنهان + شمارنده ---------- */
  function updateBar() {
    if (!bar) return;
    const n = getWish().length;
    bar.style.display = n ? '' : 'none';
    if (barCount) barCount.textContent = faNum(n);
    disarmClear();
  }

  /* ---------- رندر ---------- */
  function render() {
    const list = getWish().map(getProduct).filter(Boolean);
    renderProducts(grid, list);
    if (countEl) countEl.textContent = list.length ? '(' + faNum(list.length) + ' کالا)' : '';
    if (emptyBox) emptyBox.hidden = list.length > 0;
    updateBar();
    /* بعد از تغییر قلب روی کارت، لیست را تازه کنیم */
    grid.querySelectorAll('[data-wish]').forEach((b) => {
      b.addEventListener('click', () => setTimeout(render, 0));
    });
  }

  render();
});
