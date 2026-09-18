/* =========================================================
   فروشگاه نیلا | صفحه‌ی فاکتور (تکمیل سفارش)
   ------------------------------------------------------------
   - گام‌ها: سبد خرید → پرداخت و نهایی‌سازی → ثبت سفارش
   - ستون بزرگ: اقلام سفارش (تغییر تعداد/حذف) + نوار ارسال رایگان
     + کد تخفیف + فاکتور جمع‌بندی
   - ستون کوچک: اطلاعات ارسال + روش پرداخت + ثبت نهایی
   - درِ ورود: بدون حساب کاربری (نمایشی) به صفحه‌ی ورود هدایت می‌شود
   - ثبت سفارش نمایشی: شماره‌ی سفارش + خالی‌شدن سبد
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {

  const FREE_SHIPPING_LIMIT = 2000000;
  const SHIPPING_COST = 150000;
  const COUPON_KEY = 'nila_coupon_v1';

  const wrap = document.getElementById('ckWrap');
  const emptyBox = document.getElementById('ckEmpty');
  const done = document.getElementById('ckDone');
  const itemsBox = document.getElementById('ckItems');
  const form = document.getElementById('ckForm');
  const couponInput = document.getElementById('ckCouponInput');
  const couponBtn = document.getElementById('ckCouponBtn');
  const couponMsg = document.getElementById('ckCouponMsg');

  const loadCoupon = () => { const v = load(COUPON_KEY, 0); return v === 10 ? 10 : 0; };
  let couponPct = loadCoupon();

  function totals() {
    const items = getCart();
    let final = 0, saveable = 0, count = 0;
    items.forEach((it) => {
      const p = getProduct(it.id);
      if (!p) return;
      final += effPrice(p) * it.qty;
      saveable += (p.price - effPrice(p)) * it.qty;
      count += it.qty;
    });
    const couponSave = Math.round(final * couponPct / 100);
    const afterCoupon = final - couponSave;
    const shipping = (afterCoupon > 0 && afterCoupon < FREE_SHIPPING_LIMIT) ? SHIPPING_COST : 0;
    return { items, final, saveable, couponSave, shipping, total: afterCoupon + shipping, count };
  }

  function render() {
    const t = totals();
    if (!t.items.length) {
      if (wrap) wrap.hidden = true;
      if (emptyBox) emptyBox.hidden = false;
      return;
    }
    if (emptyBox) emptyBox.hidden = true;
    if (wrap) wrap.hidden = false;

    /* اقلام سفارش */
    itemsBox.innerHTML = t.items.map((it) => {
      const p = getProduct(it.id);
      if (!p) return '';
      return `
      <div class="ci-row">
        <a class="ci-thumb" href="product.html?id=${p.id}"><img src="${p.img}" alt="${p.name}"></a>
        <div class="ci-info">
          <a class="ci-nm" href="product.html?id=${p.id}">${p.name}</a>
          <div class="ci-meta">سایز: ${it.size} | رنگ: ${it.color}</div>
          <div class="ci-line">
            <div class="qty qty-sm">
              <button type="button" data-ck-dec="${it.key}" aria-label="کاهش تعداد"><svg class="ic" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
              <span>${faNum(it.qty)}</span>
              <button type="button" data-ck-inc="${it.key}" aria-label="افزایش تعداد"><svg class="ic" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
            </div>
            <b class="ci-pr">${faNum(effPrice(p) * it.qty)} <small>تومان</small></b>
          </div>
        </div>
        <button class="ci-remove" type="button" data-ck-remove="${it.key}" aria-label="حذف از سفارش">
          <svg class="ic" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </button>
      </div>`;
    }).join('');

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('ckCount', '(' + faNum(t.count) + ' کالا)');
    set('ckSubtotal', fmtPrice(t.final));
    set('ckShipping', t.shipping ? fmtPrice(t.shipping) : 'رایگان');
    set('ckTotal', fmtPrice(t.total));
    const sr = document.getElementById('ckSaveRow');
    if (sr) { sr.style.display = t.saveable ? 'flex' : 'none'; if (t.saveable) set('ckSave', '− ' + fmtPrice(t.saveable)); }
    const cr = document.getElementById('ckCouponRow');
    if (cr) { cr.style.display = t.couponSave ? 'flex' : 'none'; if (t.couponSave) set('ckCouponVal', '− ' + fmtPrice(t.couponSave)); }

    /* نوار پیشرفت ارسال رایگان */
    const prog = document.getElementById('ckShipProg');
    if (prog) {
      const base = t.final - t.couponSave;
      const pct = Math.min(100, Math.round((base / FREE_SHIPPING_LIMIT) * 100));
      document.getElementById('ckShipFill').style.width = pct + '%';
      if (base >= FREE_SHIPPING_LIMIT) {
        prog.classList.add('full');
        set('ckShipLabel', 'تبریک! هزینه‌ی ارسال سفارش شما رایگان است.');
      } else {
        prog.classList.remove('full');
        set('ckShipLabel', fmtPrice(FREE_SHIPPING_LIMIT - base) + ' تا ارسال رایگان فاصله دارید!');
      }
    }

    /* حساب کاربری: سلامتی / یادآوری ورود */
    const u = getUser();
    const greet = document.getElementById('ckGreet');
    const hint = document.getElementById('ckLoginHint');
    if (u) {
      greet.style.display = 'flex';
      hint.style.display = 'none';
      set('ckGreetAv', (u.name || 'م').slice(0, 1));
      set('ckGreetName', 'سلام، ' + u.name);
      const nm = document.getElementById('ckName');
      if (u.name && !nm.value) nm.value = u.name;
      const ph = document.getElementById('ckPhone');
      if (u.phone && !ph.value) ph.value = u.phone;
      const ct = document.getElementById('ckCity');
      if (u.city && !ct.value) ct.value = u.city;
      const zp = document.getElementById('ckZip');
      if (u.zip && !zp.value) zp.value = u.zip;
      const ad = document.getElementById('ckAddress');
      if (u.addr && !ad.value) ad.value = u.addr;
    } else {
      greet.style.display = 'none';
      hint.style.display = 'flex';
    }
  }

  /* تغییر تعداد / حذف قلم */
  itemsBox.addEventListener('click', (e) => {
    const dec = e.target.closest('[data-ck-dec]');
    const inc = e.target.closest('[data-ck-inc]');
    const rem = e.target.closest('[data-ck-remove]');
    if (!dec && !inc && !rem) return;
    const items = getCart();
    if (dec) {
      const it = items.find((i) => i.key === dec.dataset.ckDec);
      if (it) { it.qty--; if (it.qty <= 0) items.splice(items.indexOf(it), 1); saveCart(items); render(); }
    } else if (inc) {
      const it = items.find((i) => i.key === inc.dataset.ckInc);
      if (it && it.qty < 10) { it.qty++; saveCart(items); render(); }
    } else {
      saveCart(items.filter((i) => i.key !== rem.dataset.ckRemove));
      toast('قلم از سفارش حذف شد.', 'info');
      render();
    }
  });

  /* کد تخفیف */
  const applyCoupon = () => {
    const code = (couponInput.value || '').trim().toUpperCase();
    if (!code) {
      couponMsg.className = 'ck-coupon-msg err';
      couponMsg.textContent = 'ابتدا کد تخفیف را وارد کنید.';
      return;
    }
    if (code === 'NILA10') {
      couponPct = 10;
      store(COUPON_KEY, 10);
      couponMsg.className = 'ck-coupon-msg ok';
      couponMsg.textContent = 'کد NILA10 با موفقیت اعمال شد؛ ۱۰٪ تخفیف به سفارش شما اضافه شد.';
    } else {
      couponPct = 0;
      store(COUPON_KEY, 0);
      couponMsg.className = 'ck-coupon-msg err';
      couponMsg.textContent = 'متأسفانه این کد معتبر نیست. کد نمایشی ما NILA10 است.';
    }
    render();
  };
  couponBtn.addEventListener('click', applyCoupon);
  couponInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); } });

  /* ثبت نهایی سفارش */
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const u = getUser();
    if (!u) {
      toast('برای ثبت نهایی سفارش، ابتدا وارد شوید یا حساب کاربری بسازید.', 'info');
      nilaGo('login.php?next=checkout.html');
      return;
    }
    const name = document.getElementById('ckName').value.trim();
    const phone = document.getElementById('ckPhone').value.replace(/\D/g, '');
    const addr = document.getElementById('ckAddress').value.trim();
    if (name.length < 3) { toast('لطفاً نام و نام خانوادگی گیرنده را وارد کنید.', 'error'); return; }
    if (phone.length < 10) { toast('لطفاً یک شماره‌ی موبایل معتبر وارد کنید.', 'error'); return; }
    if (addr.length < 5) { toast('لطفاً آدرس کامل ارسال را وارد کنید.', 'error'); return; }

    const t = totals();
    const payEl = form.querySelector('input[name="ckPay"]:checked');
    const payload = {
      name, phone,
      city: document.getElementById('ckCity').value.trim(),
      zip: document.getElementById('ckZip').value.trim(),
      address: addr,
      note: document.getElementById('ckNote').value.trim(),
      payment: payEl ? payEl.value : 'bank',
      coupon_code: couponPct ? 'NILA10' : '',
      items: t.items.map((it) => ({ id: it.id, qty: it.qty, size: it.size, color: it.color })),
    };
    const finishDone = (orderNo, paidText) => {
      document.getElementById('ckOrderNo').textContent = orderNo;
      document.getElementById('ckPaid').textContent = paidText;
      saveCart([]);
      store(COUPON_KEY, 0);
      if (wrap) wrap.hidden = true;
      if (emptyBox) emptyBox.hidden = true;
      if (done) done.hidden = false;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    const mockFinish = () => {
      const orderNo = 'NLA-' + String(Math.floor(1000 + Math.random() * 9000));
      finishDone(orderNo, fmtPrice(t.total));
      toast('سفارش شما با موفقیت ثبت شد (حالت نمایشی).');
    };
    try {
      fetch('api/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      })
        .then((r) => r.json())
        .then((j) => {
          if (j && j.ok) {
            finishDone(j.order_code, fmtPrice(j.total));
            toast('سفارش شما با موفقیت ثبت شد؛ پیگیری‌اش را از «پنل کاربری ← سفارش‌های من» ببینید.');
          } else {
            const msgs = {
              stock_low: 'متأسفانه موجودی کافی برای این سفارش نیست؛ تعداد را بازبینی کنید.',
              item_invalid: 'یکی از اقلام سفارش موجود نیست؛ سبد خرید را به‌روزرسانی کنید.',
              items_empty: 'سبد خرید خالی است.',
              name_invalid: 'نام گیرنده را کامل وارد کنید.',
              phone_invalid: 'شماره‌ی موبایل معتبر نیست (قالب: 09xxxxxxxxx).',
              address_invalid: 'آدرس ارسال را کامل‌تر بنویسید.',
              db_unavailable: 'پایگاه داده در دسترس نیست؛ دوباره تلاش کنید.',
            };
            toast((j && msgs[String(j.error)]) || 'ثبت سفارش ممکن نشد؛ دوباره تلاش کنید.', 'error');
          }
        })
        .catch(mockFinish);
    } catch (err) { mockFinish(); }
  });

  render();
});
