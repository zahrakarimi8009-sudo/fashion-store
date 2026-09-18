/* =========================================================
   فروشگاه نیلا | پنل کاربری
   ------------------------------------------------------------
   - پروفایل من / ویرایش اطلاعات / پیام‌ها / اعلان‌ها
   - تغییر رمز عبور / تنظیمات حساب / خروج از حساب
   - درِ ورود: بدون حساب کاربری به صفحه‌ی ورود هدایت می‌شود
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {

  const user = getUser();
  if (!user) {
    toast('برای دسترسی به پنل کاربری ابتدا وارد شوید.', 'info');
    nilaGo('login.php?next=account.html');
    return;
  }

  const $ = (id) => document.getElementById(id);
  const u = () => getUser();

  /* ---------- هویت: سایدبار + کارت پروفایل ---------- */
  const identity = () => {
    const usr = u();
    $('puAv').textContent = (usr.name || 'م').slice(0, 1);
    $('puName').textContent = usr.name;
    $('puPhone').textContent = usr.phone ? faNum(usr.phone) : '';
    $('pfAv').textContent = (usr.name || 'م').slice(0, 1);
    $('pfName').textContent = usr.name;
    $('pfPhone').textContent = usr.phone ? 'شماره‌ی موبایل: ' + faNum(usr.phone) : '';
    $('pfWish').textContent = faNum(getWish().length);
    const addr = $('pfAddr');
    if (addr) {
      addr.textContent = usr.addr
        ? usr.addr
        : 'هنوز آدرسی ثبت نکرده‌اید؛ از بخش «ویرایش اطلاعات» آدرس ارسال خود را اضافه کنید.';
    }
  };
  identity();

  /* ---------- جابه‌جایی بخش‌ها ---------- */
  const tabs = Array.from(document.querySelectorAll('.panel-nav a[data-tab]'));
  const panes = Array.from(document.querySelectorAll('.ptab'));
  const show = (name) => {
    tabs.forEach((t) => t.classList.toggle('active', t.dataset.tab === name));
    panes.forEach((p) => p.classList.toggle('active', p.dataset.tab === name));
  };
  let initial = 'profile';
  try { initial = new URLSearchParams(window.location.search).get('tab') || 'profile'; } catch (e) {}
  show(panes.some((p) => p.dataset.tab === initial) ? initial : 'profile');
  tabs.forEach((t) => t.addEventListener('click', (e) => { e.preventDefault(); show(t.dataset.tab); }));

  /* ---------- ویرایش اطلاعات ---------- */
  const edName = $('edName'), edPhone = $('edPhone'), edCity = $('edCity'), edZip = $('edZip'), edAddr = $('edAddr');
  const usr0 = u();
  if (usr0.name) edName.value = usr0.name;
  if (usr0.phone) edPhone.value = usr0.phone;
  if (usr0.city) edCity.value = usr0.city;
  if (usr0.zip) edZip.value = usr0.zip;
  if (usr0.addr) edAddr.value = usr0.addr;
  /* پروفایل واقعی از دیتابیس (در صورت وجود PHP) */
  fetch('api/account.php', { cache: 'no-store' })
    .then((r) => (r && r.ok ? r.json() : Promise.reject(new Error('no-api'))))
    .then((d) => {
      if (d && d.ok && d.profile) {
        const usr = u();
        usr.name = d.profile.name;
        usr.phone = d.profile.phone;
        usr.city = d.profile.city || usr.city;
        usr.zip = d.profile.zip || usr.zip;
        usr.addr = d.profile.address || usr.addr;
        saveUser(usr);
        identity();
        if (d.profile.name && !edName.value) edName.value = d.profile.name;
        if (d.profile.phone && !edPhone.value) edPhone.value = d.profile.phone;
        if (d.profile.city && !edCity.value) edCity.value = d.profile.city;
        if (d.profile.zip && !edZip.value) edZip.value = d.profile.zip;
        if (d.profile.address && !edAddr.value) edAddr.value = d.profile.address;
      }
    })
    .catch(() => { /* بدون PHP: داده‌های محلی */ });

  $('editForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const name = edName.value.trim();
    const phone = edPhone.value.replace(/\D/g, '');
    if (name.length < 3) { toast('نام و نام خانوادگی را کامل وارد کنید.', 'error'); return; }
    if (phone.length < 10) { toast('یک شماره‌ی موبایل معتبر وارد کنید.', 'error'); return; }
    const city = edCity.value.trim();
    const zip = edZip.value.trim();
    const addr = edAddr.value.trim();
    const applyLocal = (real) => {
      const usr = u();
      usr.name = name;
      usr.phone = phone;
      usr.city = city;
      usr.zip = zip;
      usr.addr = addr;
      saveUser(usr);
      identity();
      toast(real
        ? 'اطلاعات حساب شما در دیتابیس به‌روزرسانی شد.'
        : 'اطلاعات حساب شما با موفقیت به‌روزرسانی شد.', 'success');
    };
    try {
      fetch('api/account.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'profile', name, phone, city, zip, address: addr }),
      })
        .then((r) => r.json())
        .then((d) => {
          if (d && d.ok) { applyLocal(true); return; }
          const msgs = {
            phone_exists: 'این شماره‌ی موبایل به حساب دیگری تعلق دارد.',
            invalid: 'اطلاعات واردشده معتبر نیست.',
          };
          if (d && msgs[String(d.error)]) { toast(msgs[String(d.error)], 'error'); return; }
          applyLocal(false);
        })
        .catch(() => applyLocal(false));
    } catch (err) { applyLocal(false); }
  });

  /* ---------- سفارش‌های من (واقعی از دیتابیس + داده‌ی نمایشی) ---------- */
  const MOCK_ORDERS = [
    { code: 'NLA-1042', status: 'registered', total: 4195000, city: 'تهران', date: '2026-08-08',
      items: [{ name: 'مانتو پاییزه کرم «پاییز»', qty: 1, price: 4045000, img: 'images/p7.jpg' }] },
  ];
  const ORD_STATUS = {
    registered: ['ثبت شده', 'st-reg'],
    pending_payment: ['در انتظار پرداخت', 'st-pay'],
    shipped: ['در حال ارسال', 'st-ship'],
    delivered: ['تحویل شده', 'st-done'],
    cancelled: ['لغو شده', 'st-cancel'],
  };
  const ordList = $('ordList');
  const ordEmpty = $('ordEmpty');
  const renderOrders = (orders) => {
    if (!ordList) return;
    const pfO = $('pfOrders');
    if (pfO) pfO.textContent = faNum(orders.length);
    if (!orders.length) {
      ordList.innerHTML = '';
      if (ordEmpty) ordEmpty.hidden = false;
      return;
    }
    if (ordEmpty) ordEmpty.hidden = true;
    ordList.innerHTML = orders.map((o) => {
      const st = ORD_STATUS[o.status] || [o.status, 'st-reg'];
      return `
      <article class="ord-card">
        <header class="ord-head">
          <div class="ord-id">
            <b>${o.code}</b>
            <span>${(o.date && typeof toJalali === 'function') ? toJalali(o.date) : (o.date || '')}${o.city ? ' · ' + o.city : ''}</span>
          </div>
          <span class="ord-status ${st[1]}">${st[0]}</span>
        </header>
        <div class="ord-items">
          ${o.items.map((it) => `
            <div class="ord-item">
              ${it.img ? '<img src="' + it.img + '" alt="">' : ''}
              <div class="ord-item-info"><b>${it.name}</b><span>${faNum(it.qty)} عدد × ${fmtPrice(it.price)} تومان</span></div>
            </div>`).join('')}
        </div>
        <footer class="ord-foot"><span>مجموع سفارش</span><b>${fmtPrice(o.total)} تومان</b></footer>
      </article>`;
    }).join('');
  };
  fetch('api/orders.php', { cache: 'no-store' })
    .then((r) => (r && r.ok ? r.json() : Promise.reject(new Error('no-api'))))
    .then((d) => {
      if (d && Array.isArray(d.orders)) { renderOrders(d.orders); return; }
      throw new Error('bad-shape');
    })
    .catch(() => renderOrders(MOCK_ORDERS));

  /* ---------- پیام‌ها (واقعی از دیتابیس + داده‌ی نمایشی) ---------- */
  const msgList = document.querySelector('.msg-list');
  const fmtMsgDate = (d) => (d && d.includes('-') && typeof toJalali === 'function')
    ? toJalali(d) + ' · ' + faNum(String(d).slice(11, 16))
    : (d || '');
  const renderMsgs = (list) => {
    if (!msgList) return;
    msgList.innerHTML = list.map(() => `
      <div class="msg-item">
        <div class="msg-head"><b></b><span></span></div>
        <p></p>
      </div>`).join('');
    const items = msgList.querySelectorAll('.msg-item');
    list.forEach((m, i) => {
      const it = items[i];
      if (!it) return;
      it.querySelector('.msg-head b').textContent = m.mine ? 'شما' : m.from;
      it.querySelector('.msg-head span').textContent = fmtMsgDate(m.date);
      it.querySelector('p').textContent = m.text;
    });
  };
  const loadMsgs = () => fetch('api/messages.php', { cache: 'no-store' })
    .then((r) => (r && r.ok ? r.json() : Promise.reject(new Error('no-api'))))
    .then((d) => {
      if (d && d.ok && Array.isArray(d.messages)) {
        renderMsgs(d.messages);
        fetch('api/messages.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'read' }),
        }).catch(() => {});
      }
    })
    .catch(() => { /* بدون PHP: پیام‌های نمایشی می‌مانند */ });
  loadMsgs();

  $('msgForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const text = $('msgText').value.trim();
    if (!text) { toast('متن پیام را بنویسید.', 'error'); return; }
    const mockAppend = () => {
      const item = document.createElement('div');
      item.className = 'msg-item';
      item.innerHTML = '<div class="msg-head"><b>شما</b><span>همین حالا</span></div><p></p>';
      item.querySelector('p').textContent = text;
      if (msgList) msgList.appendChild(item);
      $('msgText').value = '';
      toast('پیام شما برای تیم پشتیبانی ارسال شد (نمایشی).', 'success');
    };
    try {
      fetch('api/messages.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ body: text }),
      })
        .then((r) => r.json())
        .then((d) => {
          if (d && d.ok) {
            $('msgText').value = '';
            loadMsgs();
            toast('پیام شما برای تیم پشتیبانی ارسال شد.', 'success');
          } else { throw new Error('err'); }
        })
        .catch(mockAppend);
    } catch (err) { mockAppend(); }
  });

  /* ---------- اعلان‌ها (واقعی از دیتابیس + داده‌ی نمایشی) ---------- */
  const ntList = document.querySelector('.nt-list');
  const NT_IC = '<svg class="ic" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>';
  const renderNts = (list) => {
    if (!ntList) return;
    if (!list.length) {
      ntList.innerHTML = '<p class="pcard-sub" style="text-align:center; padding:1.2rem 0;">اعلانی ندارید.</p>';
      return;
    }
    ntList.innerHTML = list.map(() => `
      <div class="nt-item">
        <span class="nt-ic">${NT_IC}</span>
        <div><b></b><span></span></div>
        <i class="nt-time"></i>
      </div>`).join('');
    ntList.querySelectorAll('.nt-item').forEach((el, i) => {
      const n = list[i];
      el.classList.toggle('unread', !n.read);
      el.querySelector('b').textContent = n.title;
      el.querySelector('div span').textContent = n.body;
      el.querySelector('.nt-time').textContent = (n.date && n.date.includes('-')) ? toJalali(n.date) : (n.date || '');
    });
  };
  const loadNts = () => fetch('api/notifications.php', { cache: 'no-store' })
    .then((r) => (r && r.ok ? r.json() : Promise.reject(new Error('no-api'))))
    .then((d) => { if (d && d.ok && Array.isArray(d.notifications)) renderNts(d.notifications); })
    .catch(() => { /* بدون PHP: اعلان‌های نمایشی می‌مانند */ });
  loadNts();
  $('ntReadAll').addEventListener('click', () => {
    const markLocal = () => {
      document.querySelectorAll('.nt-item.unread').forEach((n) => n.classList.remove('unread'));
      toast('همه‌ی اعلان‌ها به عنوان خوانده‌شده علامت خورد.', 'info');
    };
    try {
      fetch('api/notifications.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'read_all' }),
      })
        .then((r) => r.json())
        .then((d) => { (d && d.ok) ? markLocal() : markLocal(); })
        .catch(markLocal);
    } catch (e) { markLocal(); }
  });

  /* ---------- تغییر رمز عبور ---------- */
  $('passForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const oldP = $('pwOld').value;
    const newP = $('pwNew').value;
    const newP2 = $('pwNew2').value;
    if (oldP.length < 4) { toast('رمز فعلی را وارد کنید.', 'error'); return; }
    if (newP.length < 6) { toast('رمز جدید باید حداقل ۶ کاراکتر باشد.', 'error'); return; }
    if (newP !== newP2) { toast('تکرار رمز جدید با رمز جدید یکسان نیست.', 'error'); return; }
    const mockDone = () => {
      $('passForm').reset();
      toast('رمز عبور شما با موفقیت تغییر کرد (نمایشی).', 'success');
    };
    try {
      fetch('api/account.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'password', old: oldP, new: newP }),
      })
        .then((r) => r.json())
        .then((d) => {
          if (d && d.ok) {
            $('passForm').reset();
            toast('رمز عبور شما در دیتابیس تغییر کرد.', 'success');
            return;
          }
          const msgs = {
            old_wrong: 'رمز فعلی درست نیست.',
            pass_short: 'رمز جدید باید حداقل ۶ کاراکتر باشد.',
          };
          if (d && msgs[String(d.error)]) { toast(msgs[String(d.error)], 'error'); return; }
          mockDone();
        })
        .catch(mockDone);
    } catch (err) { mockDone(); }
  });

  /* ---------- تنظیمات حساب: ذخیره‌ی ترجیحات ---------- */
  const SETTINGS_KEY = 'nila_user_settings_v1';
  const tglEls = Array.from(document.querySelectorAll('.ptab[data-tab="settings"] .switch input'));
  let savedPrefs = {};
  try { savedPrefs = JSON.parse(localStorage.getItem(SETTINGS_KEY) || '{}'); } catch (e) {}
  tglEls.forEach((el, i) => {
    if (typeof savedPrefs[i] === 'boolean') el.checked = savedPrefs[i];
    el.addEventListener('change', () => {
      savedPrefs[i] = el.checked;
      localStorage.setItem(SETTINGS_KEY, JSON.stringify(savedPrefs));
      toast('تنظیمات شما ذخیره شد.', 'info');
    });
  });

  /* ---------- خروج از حساب ---------- */
  $('acctLogout').addEventListener('click', (e) => {
    e.preventDefault();
    clearUser();
    toast('از حساب کاربری خارج شدید.', 'info');
    setTimeout(() => nilaGo('logout.php'), 450);
  });
});
