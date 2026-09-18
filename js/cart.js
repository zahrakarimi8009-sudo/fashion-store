/* =========================================================
   فروشگاه نیلا | صفحه سبد خرید
   ------------------------------------------------------------
   - نمایش اقلام سبد (تصویر، نام، رنگ/سایز، قیمت، تعداد)
   - افزایش/کاهش تعداد و حذف اقلام
   - محاسبه جمع، تخفیف، هزینه ارسال و مبلغ قابل پرداخت
   - کد تخفیف نمایشی (NILA10 = ۱۰٪ تخفیف)
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {

  const FREE_SHIPPING_LIMIT = 2000000;
  const SHIPPING_COST = 150000;

  const wrap = document.getElementById('cartWrap');
  const empty = document.getElementById('cartEmpty');
  const itemsBox = document.getElementById('cartItems');
  const sumCount = document.getElementById('sumCount');
  const sumSubtotal = document.getElementById('sumSubtotal');
  const sumDiscount = document.getElementById('sumDiscount');
  const sumShipping = document.getElementById('sumShipping');
  const sumCoupon = document.getElementById('sumCoupon');
  const sumTotal = document.getElementById('sumTotal');
  const couponInput = document.getElementById('couponInput');
  const couponMsg = document.getElementById('couponMsg');

  let couponPct = 0;

  const qtyOf = (items) => items.reduce((s, i) => s + i.qty, 0);

  function render() {
    const items = getCart();
    if (!items.length) {
      wrap.hidden = true;
      empty.hidden = false;
      return;
    }
    wrap.hidden = false;
    empty.hidden = true;

    itemsBox.innerHTML = items.map((it) => {
      const p = getProduct(it.id);
      if (!p) return '';
      const off = p.salePrice ? '<span class="old">' + fmtPrice(p.price) + '</span>' : '';
      return `
      <div class="cart-row">
        <a class="cr-img" href="product.html?id=${p.id}"><img src="${p.img}" alt="${p.name}"></a>
        <div class="cr-info">
          <a class="cr-name" href="product.html?id=${p.id}">${p.name}</a>
          <p class="cr-meta">رنگ: ${it.color} &nbsp;|&nbsp; سایز: ${it.size}</p>
          <p class="cr-price">${off}<span>${fmtPrice(effPrice(p))} / هر عدد</span></p>
        </div>
        <div class="qty qty-sm">
          <button data-dec="${it.key}" aria-label="کاهش تعداد"><svg class="ic" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
          <span>${faNum(it.qty)}</span>
          <button data-inc="${it.key}" aria-label="افزایش تعداد"><svg class="ic" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
        </div>
        <div class="cr-total">${fmtPrice(effPrice(p) * it.qty)}</div>
        <button class="cr-remove" data-remove="${it.key}" aria-label="حذف محصول">
          <svg class="ic" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </button>
      </div>`;
    }).join('');

    /* محاسبه‌ها */
    let original = 0, final = 0, saveable = 0;
    items.forEach((it) => {
      const p = getProduct(it.id);
      if (!p) return;
      original += p.price * it.qty;
      final += effPrice(p) * it.qty;
      saveable += (p.price - effPrice(p)) * it.qty;
    });
    const shipping = final >= FREE_SHIPPING_LIMIT ? 0 : SHIPPING_COST;
    const couponSave = Math.round(final * couponPct / 100);
    const total = final - couponSave + shipping;

    sumCount.textContent = faNum(qtyOf(items)) + ' عدد';
    sumSubtotal.textContent = fmtPrice(original);
    sumDiscount.textContent = saveable ? '− ' + fmtPrice(saveable) : '—';
    sumShipping.textContent = shipping === 0 ? 'رایگان' : fmtPrice(shipping);
    if (sumCoupon) {
      sumCoupon.style.display = couponPct ? 'flex' : 'none';
      sumCoupon.querySelector('strong').textContent = '− ' + fmtPrice(couponSave);
    }
    sumTotal.textContent = fmtPrice(total);

    bindRows();
  }

  function bindRows() {
    itemsBox.querySelectorAll('[data-inc]').forEach((b) => b.addEventListener('click', () => {
      const items = getCart();
      const it = items.find((i) => i.key === b.dataset.inc);
      if (it && it.qty < 10) { it.qty++; saveCart(items); render(); }
    }));
    itemsBox.querySelectorAll('[data-dec]').forEach((b) => b.addEventListener('click', () => {
      const items = getCart();
      const it = items.find((i) => i.key === b.dataset.dec);
      if (it) {
        it.qty--;
        if (it.qty <= 0) items.splice(items.indexOf(it), 1);
        saveCart(items);
        render();
      }
    }));
    itemsBox.querySelectorAll('[data-remove]').forEach((b) => b.addEventListener('click', () => {
      const items = getCart().filter((i) => i.key !== b.dataset.remove);
      saveCart(items);
      toast('محصول از سبد خرید حذف شد.', 'info');
      render();
    }));
  }

  /* ---- کد تخفیف نمایشی ---- */
  document.getElementById('couponBtn').addEventListener('click', () => {
    const code = (couponInput.value || '').trim().toUpperCase();
    if (!code) return;
    if (code === 'NILA10') {
      couponPct = 10;
      couponMsg.className = 'coupon-msg ok';
      couponMsg.textContent = 'کد NILA10 با موفقیت اعمال شد (۱۰٪ تخفیف).';
    } else {
      couponPct = 0;
      couponMsg.className = 'coupon-msg err';
      couponMsg.textContent = 'کد تخفیف نامعتبر است. (کد نمونه: NILA10)';
    }
    render();
  });
  couponInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('couponBtn').click(); }
  });

  /* ---- تکمیل خرید -> صفحه‌ی فاکتور ---- */
  document.getElementById('checkoutBtn').addEventListener('click', () => {
    if (!cartCount()) { toast('سبد خرید شما خالی است.', 'error'); return; }
    nilaGo('checkout.html');
  });

  render();
});
