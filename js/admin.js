/* =========================================================
   فروشگاه نیلا | پنل مدیریت
   ------------------------------------------------------------
   - داشبورد آماری / سفارش‌ها / کاربران / مدیران و سطح دسترسی
   - پیام‌ها / محصولات (افزودن/ویرایش/حذف/فعال‌سازی) / تنظیمات سایت
   - همه‌ی داده‌ها از دیتابیس (api/admin.php)؛ اگر PHP در دسترس
     نباشد، داده‌های نمایشی جایگزین می‌شوند.
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {

  const $ = (id) => document.getElementById(id);

  const api = (payload) => fetch('api/admin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  }).then((r) => r.json());

  /* خروج از پنل (همیشه فعال است، حتی پیش از ورود) */
  const logoutBtn = $('adminLogout');
  if (logoutBtn) logoutBtn.addEventListener('click', () => {
    api({ action: 'logout' }).catch(() => {});
    localStorage.removeItem('nila_admin');
    location.reload();
  });

  /* ---------- دروازه‌ی ورود مدیران ---------- */
  const gate = $('adminGate');
  const bodyWrap = $('adminBody');
  let adminOk = false;
  try { adminOk = !!JSON.parse(localStorage.getItem('nila_admin') || 'null'); } catch (e) {}

  const showGateWithError = (msg) => {
    if (gate) gate.removeAttribute('hidden');
    if (bodyWrap) bodyWrap.setAttribute('hidden', '');
    const card = document.getElementById('agCard');
    if (card && !card.querySelector('.ag-err')) {
      const note = document.createElement('p');
      note.className = 'ag-err';
      note.style.cssText = 'margin-top:1rem;font-size:.78rem;line-height:1.8;color:#ffb3c9;';
      note.textContent = msg;
      card.appendChild(note);
    }
  };
  const showPanel = () => {
    if (gate) gate.setAttribute('hidden', '');
    if (bodyWrap) bodyWrap.removeAttribute('hidden');
    nilaLoadProducts().then(() => {
      try {
        initPanel();
      } catch (e) {
        console.error('[nila-admin] initPanel error:', e);
        showGateWithError('بارگذاری پنل با خطا مواجه شد: ' + (e && e.message ? e.message : 'نامشخص') +
          '. صفحه را یک بار با کلیدهای Ctrl + F5 رفرش کنید تا فایل‌های تازه بارگذاری شوند.');
      }
    });
  };
  const enterPanel = () => showPanel();

  if (gate && !adminOk) {
    if (bodyWrap) bodyWrap.setAttribute('hidden', '');
    $('adminLoginForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const em = $('agEmail').value.trim();
      const pw = $('agPass').value;
      const failCard = () => {
        const card = $('agCard');
        card.classList.remove('shake');
        void card.offsetWidth;
        card.classList.add('shake');
        toast('اطلاعات ورود مدیر نادرست است.', 'error');
      };
      const mockLogin = () => {
        if (em !== 'admin@nila.shop' || pw !== 'nila2026') { failCard(); return; }
        localStorage.setItem('nila_admin', JSON.stringify({ email: em, at: Date.now() }));
        toast('ورود موفق؛ خوش آمدید مدیر عزیز.', 'success');
        enterPanel();
      };
      try {
        api({ action: 'login', email: em, password: pw })
          .then((j) => {
            if (j && j.ok) {
              localStorage.setItem('nila_admin', JSON.stringify(j.admin));
              toast('ورود موفق؛ خوش آمدید ' + (j.admin.name || 'مدیر') + '.', 'success');
              enterPanel();
            } else { failCard(); }
          })
          .catch(mockLogin);
      } catch (err) { mockLogin(); }
    });
    return;
  }
  if (adminOk) {
    showPanel();
  }

  function initPanel() {
    /* پروفایل مدیرِ واردشده در سایدبار */
    let adm = null;
    try { adm = JSON.parse(localStorage.getItem('nila_admin') || 'null'); } catch (e) {}
    if (adm) {
      const av = document.getElementById('admAv');
      const nm = document.getElementById('admName');
      const em = document.getElementById('admEmail');
      if (av) av.textContent = (adm.name || 'م').slice(0, 1);
      if (nm) nm.textContent = adm.name || 'مدیر';
      if (em) em.textContent = adm.email || '';
    }
    const STATUS = {
      registered: 'ثبت شده',
      pending_payment: 'در انتظار پرداخت',
      shipped: 'در حال ارسال',
      delivered: 'تحویل شده',
      cancelled: 'لغو شده',
    };
    const STATUS_CLS = {
      registered: 'pink',
      pending_payment: 'gold',
      shipped: 'gold',
      delivered: 'green',
      cancelled: 'red',
    };
    const ROLE_NAMES = { customer: 'مشتری', support: 'پشتیبانی', manager: 'مدیر فروشگاه', admin: 'مدیر کل' };
    const fmtDate = (d) => (d && typeof d === 'string' && d.indexOf('-') > -1 && typeof toJalali === 'function') ? toJalali(d) : (d || '');
    const isReal = (o) => !!o && o.id != null;

    /* ---------- داده‌های نمایشی (جایگزینِ امن) ---------- */
    const ORDERS0 = [
      { no: 'NLA-1042', name: 'مریم احمدی', phone: '09123456789', total: 4195000, status: 'registered', date: '۱۴/۰/۱۸' },
      { no: 'NLA-1041', name: 'زهرا کریمی', phone: '09121112233', total: 2850000, status: 'shipped', date: '۱۴۰/۰۶/۱۶' },
      { no: 'NLA-1038', name: 'سارا محمدی', phone: '09129998877', total: 1650000, status: 'delivered', date: '۱۴۰/۰۶/۱۱' },
      { no: 'NLA-1035', name: 'نگار موسوی', phone: '09125554433', total: 7470000, status: 'delivered', date: '۱۴۰/۰۶/۰۸' },
      { no: 'NLA-1031', name: 'هدیه شریفی', phone: '09127776655', total: 990000, status: 'cancelled', date: '۱۴۰/۰۶/۰۲' },
    ];
    const USERS0 = [
      { name: 'مریم احمدی', phone: '09123456789', role: 'customer', date: '۱۴/۰۵/۰' },
      { name: 'زهرا کریمی', phone: '09121112233', role: 'customer', date: '۱۴/۰۵/۲' },
      { name: 'سارا محمدی', phone: '09129998877', role: 'customer', date: '۱۴/۰۴/۰' },
      { name: 'نگار موسوی', phone: '09125554433', role: 'customer', date: '۱۴/۰۴/۸' },
      { name: 'هدیه شریفی', phone: '09127776655', role: 'customer', date: '۱۴۰/۰۳/۲۵' },
    ];
    const ADMINS0 = [
      { name: 'علی رضایی', phone: '09120000001', role: 'admin', level: 'مدیر کل' },
      { name: 'فاطمه قاسمی', phone: '09120000002', role: 'manager', level: 'مدیر فروشگاه' },
      { name: 'مهدی توکلی', phone: '09120000003', role: 'support', level: 'پشتیبانی' },
    ];
    const ROLES0 = ['admin', 'manager', 'support', 'customer'];
    const MSGS0 = [
      { from: 'مریم احمدی', text: 'سلام، سفارشم کی ارسال می‌شود؟', date: 'امروز ۱۰:۲۰', unread: true },
      { from: 'زهرا کریمی', text: 'آیا امکان تعویض سایز وجود دارد؟', date: 'دیروز ۱۶:۴۵', unread: true },
      { from: 'سارا محمدی', text: 'ممنون از پیگیری؛ کد رهگیری گرفتم.', date: '۲ روز پیش', unread: false },
    ];

    let orders = ORDERS0.map((o) => Object.assign({}, o));
    let users = USERS0.map((u) => Object.assign({}, u));
    let admins = ADMINS0.map((a) => Object.assign({}, a));
    let roles = ROLES0.slice();
    const msgs = MSGS0.map((m) => Object.assign({}, m));
    let products = PRODUCTS.map((p) => ({ id: p.id, name: p.name, img: p.img, price: effPrice(p), stock: p.stock, active: true }));
    let editId = null;
    let replyTo = null;
    let realApi = false;
    const setStatus = (kind) => {
      const el = $('admStatus');
      if (!el) return;
      el.className = 'adm-status ' + kind;
      el.textContent = kind === 'real' ? 'متصل به دیتابیس' : 'حالت نمایشی — دیتابیس وصل نیست';
    };
    const markReal = () => { realApi = true; setStatus('real'); };
    const showMockBanner = () => {
      if (realApi) return;
      setStatus('mock');
      /* اگر خودِ PHP صفحه گفت دیتابیس نیست، نوار قرمز سرور را نشان داده؛ بنر JS تکراری نشود */
      if (window.__NILA_DB__ === false) return;
      const b = $('admModeBanner');
      if (!b) return;
      b.removeAttribute('hidden');
      b.className = 'adm-mode mock';
      b.innerHTML = '⚠ <b>حالت نمایشی:</b> اتصال به دیتابیس برقرار نشد و داده‌های این صفحه نمونه‌اند (تغییرات در دیتابیس ذخیره نمی‌شوند). برای فعال‌سازی: ' +
        '<b>۱)</b> فایل <span dir="ltr">nila_shop.sql</span> را در phpMyAdmin ایمپورت کن — <b>۲)</b> <a href="diag.php" style="color:#8a5a00;font-weight:800;">diag.php</a> را باز کن — ' +
        '<b>۳)</b> این صفحه را با <span dir="ltr">Ctrl+F5</span> رفرش کن.';
    };
    if (window.__NILA_DB__ === true) { setStatus('real'); }
    if (window.__NILA_DB__ === false) { setStatus('mock'); }

    /* ---------- جابه‌جایی بخش‌ها ---------- */
    const tabs = Array.from(document.querySelectorAll('.panel-nav a[data-tab]'));
    const panes = Array.from(document.querySelectorAll('.ptab'));
    const show = (name) => {
      tabs.forEach((t) => t.classList.toggle('active', t.dataset.tab === name));
      panes.forEach((p) => p.classList.toggle('active', p.dataset.tab === name));
    };
    let initial = 'dashboard';
    try { initial = new URLSearchParams(window.location.search).get('tab') || 'dashboard'; } catch (e) {}
    show(panes.some((p) => p.dataset.tab === initial) ? initial : 'dashboard');
    tabs.forEach((t) => t.addEventListener('click', (e) => { e.preventDefault(); show(t.dataset.tab); }));

    /* ---------- داشبورد ---------- */
    function renderDashboard() {
      const revenue = orders.filter((o) => o.status !== 'cancelled').reduce((s, o) => s + o.total, 0);
      $('stOrders').textContent = faNum(orders.length);
      $('stUsers').textContent = faNum(users.length + admins.length);
      $('stProducts').textContent = faNum(products.length);
      $('stRevenue').textContent = faNum(revenue);
      $('dashOrders').innerHTML = orders.slice(0, 4).map((o) => `
        <div class="mini-order">
          <b class="dir-ltr">${o.no}</b>
          <span class="mo-name">${o.name}</span>
          <b class="mo-total">${fmtPrice(o.total)}</b>
          <span class="badge ${STATUS_CLS[o.status] || 'pink'}">${STATUS[o.status] || o.status}</span>
        </div>`).join('');
    }

    /* ---------- سفارش‌ها ---------- */
    function renderOrders() {
      $('ordCount').textContent = faNum(orders.length) + ' سفارش';
      $('ordBody').innerHTML = orders.map((o, i) => `
        <tr>
          <td class="dir-ltr" style="font-weight:800;">${o.no}</td>
          <td>${o.name}<br><small style="color:var(--muted)" class="dir-ltr">${faNum(o.phone)}</small>${o.city ? '<br><small style="color:var(--muted)">' + o.city + '</small>' : ''}</td>
          <td style="white-space:nowrap;">${fmtPrice(o.total)}</td>
          <td>
            <select data-ord="${i}" class="sel-sm">
              ${Object.keys(STATUS).map((k) => '<option value="' + k + '"' + (k === o.status ? ' selected' : '') + '>' + STATUS[k] + '</option>').join('')}
            </select>
          </td>
          <td style="white-space:nowrap;">${fmtDate(o.date)}</td>
          <td><button class="btn btn-outline btn-xs" type="button" data-ord-del="${i}">حذف</button></td>
        </tr>`).join('');
    }
    $('ordBody').addEventListener('change', (e) => {
      const sel = e.target.closest('[data-ord]');
      if (!sel) return;
      const o = orders[Number(sel.dataset.ord)];
      o.status = sel.value;
      renderOrders();
      renderDashboard();
      if (isReal(o)) {
        api({ action: 'order_status', id: o.id, status: sel.value })
          .then((j) => toast(j && j.ok ? 'وضعیت سفارش ' + o.no + ' در دیتابیس به‌روزرسانی شد.' : 'وضعیت سفارش به‌روزرسانی شد.', 'success'))
          .catch(() => toast('وضعیت سفارش به‌روزرسانی شد.', 'success'));
      } else {
        toast('وضعیت سفارش به‌روزرسانی شد.', 'success');
      }
    });
    $('ordBody').addEventListener('click', (e) => {
      const delBtn = e.target.closest('[data-ord-del]');
      if (!delBtn) return;
      const o = orders[Number(delBtn.dataset.ordDel)];
      orders.splice(Number(delBtn.dataset.ordDel), 1);
      renderOrders();
      renderDashboard();
      if (isReal(o)) {
        api({ action: 'order_delete', id: o.id }).catch(() => {});
        toast('سفارش ' + o.no + ' حذف شد.', 'info');
      } else {
        toast('سفارش ' + o.no + ' حذف شد.', 'info');
      }
    });

    /* ---------- کاربران ---------- */
    function renderUsers() {
      $('usrCount').textContent = faNum(users.length) + ' کاربر';
      $('usrBody').innerHTML = users.map((u, i) => `
        <tr>
          <td style="font-weight:700;">${u.name}</td>
          <td class="dir-ltr">${faNum(u.phone)}</td>
          <td><span class="badge ${u.role === 'customer' ? 'gray' : 'pink'}">${ROLE_NAMES[u.role] || u.role}</span></td>
          <td style="white-space:nowrap;">${fmtDate(u.date)}</td>
          <td class="row-actions">
            <button class="btn btn-outline btn-xs" type="button" data-usr-view="${i}">مشاهده</button>
            <button class="btn btn-outline btn-xs" type="button" data-usr-del="${i}">حذف</button>
          </td>
        </tr>`).join('');
    }
    $('usrBody').addEventListener('click', (e) => {
      const view = e.target.closest('[data-usr-view]');
      const del = e.target.closest('[data-usr-del]');
      if (view) {
        const u = users[Number(view.dataset.usrView)];
        toast(u.name + ' — ' + faNum(u.phone) + ' — عضو از ' + fmtDate(u.date) + '.', 'info');
        return;
      }
      if (del) {
        const u = users[Number(del.dataset.usrDel)];
        users.splice(Number(del.dataset.usrDel), 1);
        renderUsers();
        renderDashboard();
        if (isReal(u)) api({ action: 'user_delete', id: u.id }).catch(() => {});
        toast('کاربر «' + u.name + '» حذف شد.', 'info');
      }
    });
    $('addUserForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const name = $('nuName').value.trim();
      const phone = $('nuPhone').value.replace(/\D/g, '');
      if (name.length < 3) { toast('نام کاربر را کامل وارد کنید.', 'error'); return; }
      if (phone.length < 10) { toast('شماره‌ی موبایل معتبر وارد کنید.', 'error'); return; }
      const role = $('nuRole').value;
      const mockAdd = () => {
        users.push({ name, phone, role, date: 'امروز' });
        renderUsers();
        renderDashboard();
        toast('کاربر «' + name + '» افزوده شد (رمز اولیه: 123456).', 'success');
      };
      api({ action: 'user_add', name, phone, role })
        .then((j) => {
          if (j && j.ok) {
            api({ action: 'users' }).then((d) => {
              if (d && d.ok && Array.isArray(d.users)) { users = d.users; renderUsers(); renderDashboard(); }
            }).catch(() => {});
            toast('کاربر «' + name + '» در دیتابیس ثبت شد (رمز اولیه: 123456).', 'success');
          } else if (j && j.error === 'phone_exists') {
            toast('این شماره‌ی موبایل قبلاً ثبت شده است.', 'error');
          } else { mockAdd(); }
        })
        .catch(mockAdd);
      e.target.reset();
    });

    /* ---------- مدیران و سطح دسترسی ---------- */
    function renderAdmins() {
      $('admCount').textContent = faNum(admins.length) + ' مدیر';
      $('admBody').innerHTML = admins.map((a, i) => `
        <tr>
          <td style="font-weight:700;">${a.name}</td>
          <td class="dir-ltr">${faNum(a.phone)}</td>
          <td><span class="badge ${a.role === 'admin' || a.level === 'مدیر کل' ? 'pink' : 'gold'}">${a.level || ROLE_NAMES[a.role] || a.role}</span></td>
          <td><button class="btn btn-outline btn-xs" type="button" data-adm-del="${i}">حذف</button></td>
        </tr>`).join('');
      $('roleList').innerHTML = roles.map((r) => '<span class="role-chip">' + (ROLE_NAMES[r] || r) + '</span>').join('');
    }
    $('admBody').addEventListener('click', (e) => {
      const del = e.target.closest('[data-adm-del]');
      if (!del) return;
      const a = admins[Number(del.dataset.admDel)];
      admins.splice(Number(del.dataset.admDel), 1);
      renderAdmins();
      renderDashboard();
      if (isReal(a)) api({ action: 'admin_delete', id: a.id }).catch(() => {});
      toast('مدیر «' + a.name + '» حذف شد.', 'info');
    });
    $('addAdminForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const name = $('naName').value.trim();
      const phone = $('naPhone').value.replace(/\D/g, '');
      const custom = $('naNewRole').value.trim();
      const role = custom || $('naRole').value;
      if (name.length < 3) { toast('نام مدیر را کامل وارد کنید.', 'error'); return; }
      if (phone.length < 10) { toast('شماره‌ی موبایل معتبر وارد کنید.', 'error'); return; }
      if (custom && !roles.includes(custom)) {
        roles.push(custom);
        ROLE_NAMES[custom] = custom;
      }
      const mockAdd = () => {
        admins.push({ name, phone, role, level: ROLE_NAMES[role] || role });
        renderAdmins();
        renderDashboard();
        toast('مدیر «' + name + '» با سطح «' + (ROLE_NAMES[role] || role) + '» افزوده شد.', 'success');
      };
      api({ action: 'admin_add', name, phone, level: ROLE_NAMES[role] || role })
        .then((j) => {
          if (j && j.ok) {
            api({ action: 'admins' }).then((d) => {
              if (d && d.ok && Array.isArray(d.admins)) { admins = d.admins; renderAdmins(); renderDashboard(); }
            }).catch(() => {});
            toast('مدیر «' + name + '» افزوده شد؛ ایمیل ورود: ' + (j.email || '') + ' / رمز: 123456', 'success');
          } else { mockAdd(); }
        })
        .catch(mockAdd);
      e.target.reset();
    });

    /* ---------- پیام‌ها ---------- */
    function renderMsgs() {
      $('admMsgs').innerHTML = msgs.map((m, i) => `
        <div class="msg-item ${m.unread ? 'unread' : ''}">
          ${m.unread ? '<span class="msg-dot"></span>' : ''}
          <div class="msg-head"><b>${m.from}</b><span>${fmtDate(m.date)}</span></div>
          <p>${m.text}</p>
          <div style="margin-top:.4rem;">
            ${m.unread ? '<button class="btn btn-outline btn-xs" type="button" data-msg-read="' + i + '">علامت خوانده‌شده</button>' : ''}
            ${m.user_id ? ' <button class="btn btn-outline btn-xs" type="button" data-msg-reply="' + i + '">پاسخ</button>' : ''}
          </div>
        </div>`).join('');
      const ph = $('admMsgText');
      ph.placeholder = replyTo ? 'پاسخ به «' + replyTo.name + '»...' : 'پاسخ پشتیبانی بنویسید...';
    }
    $('admMsgs').addEventListener('click', (e) => {
      const btn = e.target.closest('[data-msg-read]');
      const rep = e.target.closest('[data-msg-reply]');
      if (btn) {
        const m = msgs[Number(btn.dataset.msgRead)];
        m.unread = false;
        renderMsgs();
        if (m.id != null) api({ action: 'message_read', id: m.id }).catch(() => {});
        toast('پیام به عنوان خوانده‌شده علامت خورد.', 'info');
      }
      if (rep) {
        const m = msgs[Number(rep.dataset.msgReply)];
        if (m && m.user_id) {
          replyTo = { user_id: m.user_id, name: m.from };
          renderMsgs();
          $('admMsgText').focus();
        }
      }
    });
    $('admMsgForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const text = $('admMsgText').value.trim();
      if (!text) { toast('متن پاسخ را بنویسید.', 'error'); return; }
      const mockReply = () => {
        $('admMsgText').value = '';
        replyTo = null;
        renderMsgs();
        toast('پاسخ پشتیبانی ارسال شد (نمایشی).', 'success');
      };
      if (replyTo && replyTo.user_id) {
        api({ action: 'message_reply', user_id: replyTo.user_id, body: text })
          .then((j) => {
            if (j && j.ok) {
              const nm = replyTo ? replyTo.name : '';
              $('admMsgText').value = '';
              replyTo = null;
              renderMsgs();
              toast('پاسخ شما برای «' + nm + '» در دیتابیس ثبت شد.', 'success');
            } else { mockReply(); }
          })
          .catch(mockReply);
      } else {
        mockReply();
      }
    });

    /* ---------- محصولات ---------- */
    const prodPrice = (p) => (p.salePrice != null ? p.salePrice : p.price);
    function renderProducts() {
      $('prodCount').textContent = faNum(products.length) + ' محصول';
      $('prodBody').innerHTML = products.map((p, i) => `
        <tr>
          <td><span class="prod-thumb"><img src="${p.img}" alt=""></span></td>
          <td style="font-weight:700;">${p.name}${p.active === false ? ' <span class="badge gray" style="font-size:.6rem;">غیرفعال</span>' : ''}</td>
          <td style="white-space:nowrap;">${fmtPrice(prodPrice(p))}</td>
          <td>${faNum(p.stock)}</td>
          <td><span class="badge ${p.stock > 0 ? 'green' : 'red'}">${p.stock > 0 ? 'موجود' : 'ناموجود'}</span></td>
          <td class="row-actions">
            <button class="btn btn-outline btn-xs" type="button" data-prod-edit="${i}">ویرایش</button>
            <button class="btn btn-outline btn-xs" type="button" data-prod-toggle="${i}">${p.active === false ? 'فعال' : 'غیرفعال'}</button>
            <button class="btn btn-outline btn-xs" type="button" data-prod-del="${i}">حذف</button>
          </td>
        </tr>`).join('');
    }
    $('prodBody').addEventListener('click', (e) => {
      const edit = e.target.closest('[data-prod-edit]');
      const tog = e.target.closest('[data-prod-toggle]');
      const del = e.target.closest('[data-prod-del]');
      if (edit) {
        const p = products[Number(edit.dataset.prodEdit)];
        if (p.full) {
          startEditProduct(p);
        } else {
          toast('ویرایش محصول در حالت نمایشی (بدون PHP) ممکن نیست.', 'info');
        }
        return;
      }
      if (tog) {
        const p = products[Number(tog.dataset.prodToggle)];
        p.active = p.active === false;
        renderProducts();
        if (isReal(p)) api({ action: 'product_toggle', id: p.id }).catch(() => {});
        toast(p.active ? 'محصول «' + p.name + '» در فروشگاه فعال شد.' : 'محصول «' + p.name + '» از فروشگاه مخفی شد.', 'info');
        return;
      }
      if (del) {
        const p = products[Number(del.dataset.prodDel)];
        products.splice(Number(del.dataset.prodDel), 1);
        renderProducts();
        renderDashboard();
        if (isReal(p)) api({ action: 'product_delete', id: p.id }).catch(() => {});
        toast('محصول «' + p.name + '» حذف شد.', 'info');
      }
    });

    /* ---------- افزودن / ویرایش محصول با تمام ویژگی‌های کارت + پیش‌نمایش زنده ---------- */
    const COLOR_HEX = ['#ec4d84', '#9c5bd6', '#ff8f5e', '#3b4a6b', '#8e2f3c', '#2f6f4f', '#c9a227', '#5b5b6b'];
    let npImg1Url = null;
    let npImg2Url = null;
    const refreshImgPreview = (inputId, is1) => {
      const el = $(inputId);
      if (!el || !el.files) return;
      const old = is1 ? npImg1Url : npImg2Url;
      if (old) { URL.revokeObjectURL(old); }
      const f = el.files[0];
      const nu = f ? URL.createObjectURL(f) : null;
      if (is1) { npImg1Url = nu; } else { npImg2Url = nu; }
      renderPreview();
    };
    $('npImage').addEventListener('change', () => refreshImgPreview('npImage', true));
    $('npImage2').addEventListener('change', () => refreshImgPreview('npImage2', false));
    function buildPreviewProduct() {
      const cat = $('npCat').value;
      const catImg = CATEGORIES[cat] ? CATEGORIES[cat].img : 'images/cat-dress.jpg';
      const img1 = npImg1Url || catImg;
      const img2 = npImg2Url || catImg;
      const price = Number($('npPrice').value.replace(/\D/g, '')) || 0;
      const oldPrice = Number($('npOldPrice').value.replace(/\D/g, '')) || 0;
      const sizes = Array.from(document.querySelectorAll('.np-checks input:checked')).map((c) => c.value);
      const colors = $('npColors').value.split(/[,،]/).map((c) => c.trim()).filter(Boolean).map((n, i) => ({ name: n, hex: COLOR_HEX[i % COLOR_HEX.length] }));
      const badge = $('npBadge').value;
      const rating = Math.min(5, Math.max(0, Number($('npRating').value) || 0));
      return {
        id: 0,
        name: $('npName').value.trim() || 'محصول جدید نیلا',
        short: $('npShort').value.trim() || 'توضیح محصول...',
        desc: $('npShort').value.trim() || 'توضیح کامل محصول...',
        category: cat,
        price: oldPrice > price && price ? oldPrice : (price || 1000000),
        salePrice: oldPrice > price && price ? price : null,
        img: img1,
        img2: img2,
        gallery: [img1, img2],
        sizes: sizes.length ? sizes : ['M', 'L'],
        colors: colors.length ? colors : [{ name: 'صورتی', hex: '#ec4d84' }],
        isNew: badge === 'new',
        isPopular: badge === 'popular',
        rating: rating,
        sales: 12,
        code: 'NLA-' + (1050 + Math.floor(Math.random() * 900)),
        date: 'امروز',
        stock: Number($('npStock').value.replace(/\D/g, '')) || 0,
        specs: [['جنس پارچه', 'پارچه درجه یک'], ['شست‌وشو', 'دستی'], ['کشور تولید', 'ایران']],
      };
    }
    function renderPreview() {
      const box = $('npPreview');
      if (!box) return;
      box.innerHTML = productCard(buildPreviewProduct(), { rating: true });
      observeReveals(box);
    }
    const npForm = $('addProdForm');
    const npSubmit = $('npSubmit');
    ['input', 'change'].forEach((ev) => npForm.addEventListener(ev, renderPreview));

    const resetEditState = () => {
      editId = null;
      if (npSubmit) npSubmit.textContent = 'ثبت و افزودن محصول';
    };
    const startEditProduct = (p) => {
      const f = p.full;
      editId = p.id;
      $('npName').value = f.name;
      $('npShort').value = f.short || '';
      $('npCat').value = f.category || 'dress';
      const eff = f.salePrice != null ? f.salePrice : f.price;
      $('npPrice').value = eff;
      $('npOldPrice').value = f.salePrice != null ? f.price : '';
      $('npStock').value = f.stock != null ? f.stock : 10;
      $('npRating').value = f.rating != null ? f.rating : 4.6;
      $('npColors').value = (f.colors || []).map((c) => c.name).join('، ');
      $('npBadge').value = f.isNew ? 'new' : (f.isPopular ? 'popular' : '');
      const sz = f.sizes || [];
      document.querySelectorAll('.np-checks input').forEach((c) => { c.checked = sz.includes(c.value); });
      if (npSubmit) npSubmit.textContent = 'ذخیره‌ی تغییرات محصول';
      const hint = $('npImageHint');
      if (hint) hint.textContent = f.img ? 'تصویر فعلی: ' + f.img + ' — فایل جدید انتخاب کنی، جایگزین می‌شود' : '';
      renderPreview();
      npForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
      toast('فرم برای ویرایش «' + f.name + '» آماده شد.', 'info');
    };

    const buildPayload = () => {
      const sizes = Array.from(document.querySelectorAll('.np-checks input:checked')).map((c) => c.value);
      const colors = $('npColors').value.split(/[,،]/).map((c) => c.trim()).filter(Boolean).map((n, i) => ({ name: n, hex: COLOR_HEX[i % COLOR_HEX.length] }));
      const badge = $('npBadge').value;
      return {
        name: $('npName').value.trim(),
        short: $('npShort').value.trim(),
        desc: $('npShort').value.trim(),
        category: $('npCat').value,
        price: Number($('npPrice').value.replace(/\D/g, '')) || 0,
        old_price: Number($('npOldPrice').value.replace(/\D/g, '')) || 0,
        stock: Number($('npStock').value.replace(/\D/g, '')) || 0,
        sizes: sizes,
        colors: colors,
        is_new: badge === 'new' ? 1 : 0,
        is_popular: badge === 'popular' ? 1 : 0,
        rating: Math.min(5, Math.max(0, Number($('npRating').value) || 0)),
        image: '',
        image2: '',
        specs: [],
      };
    };

    const sendProduct = (payload) => {
      const file1 = $('npImage').files ? $('npImage').files[0] : null;
      const file2 = $('npImage2').files ? $('npImage2').files[0] : null;
      if (file1 || file2) {
        const fd = new FormData();
        Object.keys(payload).forEach((k) => {
          const v = payload[k];
          if (v === null || v === undefined) { return; }
          if (Array.isArray(v) || typeof v === 'object') { fd.append(k, JSON.stringify(v)); }
          else { fd.append(k, String(v)); }
        });
        if (file1) { fd.append('image', file1); }
        if (file2) { fd.append('image2', file2); }
        return fetch('api/admin.php', { method: 'POST', body: fd }).then((r) => r.json());
      }
      return api(payload);
    };
    npForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const name = $('npName').value.trim();
      const price = Number($('npPrice').value.replace(/\D/g, ''));
      const stock = Number($('npStock').value.replace(/\D/g, '')) || 0;
      if (name.length < 3) { toast('نام محصول را کامل وارد کنید.', 'error'); return; }
      if (!price || price <= 0) { toast('قیمت معتبر وارد کنید.', 'error'); return; }
      const payload = buildPayload();

      const mockAdd = () => {
        const full = buildPreviewProduct();
        full.id = Date.now();
        full.name = name;
        full.stock = stock;
        PRODUCTS.push(full);
        products.unshift({ id: full.id, name: full.name, img: full.img, price: effPrice(full), stock: stock, active: true });
        if (npImg1Url) { npImg1Url = null; }
        if (npImg2Url) { npImg2Url = null; }
        npForm.reset();
        renderProducts();
        renderDashboard();
        renderPreview();
        resetEditState();
        toast('محصول «' + name + '» افزوده شد و در کارت‌های فروشگاه نمایش داده می‌شود.', 'success');
      };

      if (editId != null) {
        sendProduct(Object.assign({ action: 'product_update', id: editId }, payload))
          .then((j) => {
            if (j && j.ok) {
              const idx = products.findIndex((x) => x.id === editId);
              if (idx > -1) {
                const isSale = payload.old_price > payload.price;
                products[idx].name = name;
                products[idx].price = isSale ? payload.old_price : payload.price;
                products[idx].salePrice = isSale ? payload.price : null;
                products[idx].stock = stock;
              }
              const pi = PRODUCTS.findIndex((x) => x.id === editId);
              if (pi > -1) {
                const isSale = payload.old_price > payload.price;
                PRODUCTS[pi].name = name;
                PRODUCTS[pi].price = isSale ? payload.old_price : payload.price;
                PRODUCTS[pi].salePrice = isSale ? payload.price : null;
              }
              if (npImg1Url) { URL.revokeObjectURL(npImg1Url); npImg1Url = null; }
              if (npImg2Url) { URL.revokeObjectURL(npImg2Url); npImg2Url = null; }
              npForm.reset();
              renderProducts();
              renderDashboard();
              renderPreview();
              resetEditState();
              toast('تغییرات محصول «' + name + '» در دیتابیس ذخیره شد.', 'success');
            } else { toast('ذخیره‌ی تغییرات ممکن نشد.', 'error'); }
          })
          .catch(() => toast('ذخیره‌ی تغییرات ممکن نشد؛ دوباره تلاش کنید.', 'error'));
        return;
      }

      sendProduct(Object.assign({ action: 'product_add' }, payload))
        .then((j) => {
          if (j && j.ok) {
            api({ action: 'products' }).then((d) => {
              if (d && d.ok && Array.isArray(d.products)) {
                products = d.products.map((x) => Object.assign({}, x, { full: x }));
                renderProducts();
                renderDashboard();
              }
            }).catch(() => {});
            if (npImg1Url) { URL.revokeObjectURL(npImg1Url); npImg1Url = null; }
            if (npImg2Url) { URL.revokeObjectURL(npImg2Url); npImg2Url = null; }
            npForm.reset();
            renderPreview();
            toast('محصول «' + name + '» (با تصویر) در دیتابیس ثبت شد و در صفحه‌ی اصلی نمایش داده می‌شود.', 'success');
          } else { mockAdd(); }
        })
        .catch(mockAdd);
    });

    /* ---------- تنظیمات سایت ---------- */
    const SS_FIELDS = {
      ssName: 'site_name',
      ssPhone: 'site_phone',
      ssEmail: 'support_email',
      ssAddress: 'site_address',
      ssShipping: 'shipping_cost',
      ssFreeShip: 'free_shipping_min',
    };
    $('siteSettingsForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const vals = {};
      Object.keys(SS_FIELDS).forEach((id) => {
        const el = $(id);
        if (el) vals[SS_FIELDS[id]] = el.value.trim();
      });
      api({ action: 'settings_save', settings: vals })
        .then((j) => {
          if (j && j.ok) {
            toast('تنظیمات سایت در دیتابیس ذخیره شد.', 'success');
          } else {
            toast('تنظیمات سایت ذخیره شد (نمایشی).', 'success');
          }
        })
        .catch(() => toast('تنظیمات سایت ذخیره شد (نمایشی).', 'success'));
    });

    $('resetDemo').addEventListener('click', () => {
      orders = ORDERS0.map((o) => Object.assign({}, o));
      users = USERS0.map((u) => Object.assign({}, u));
      admins = ADMINS0.map((a) => Object.assign({}, a));
      roles = ROLES0.slice();
      products = PRODUCTS.map((p) => ({ id: p.id, name: p.name, img: p.img, price: effPrice(p), stock: p.stock, active: true }));
      renderAll();
      toast('داده‌ها به حالت اولیه بازگشت.', 'info');
    });

    function renderAll() {
      renderDashboard();
      renderOrders();
      renderUsers();
      renderAdmins();
      renderMsgs();
      renderProducts();
    }

    renderAll();
    renderPreview();

    /* ---------- بارگذاری داده‌ی واقعی از دیتابیس ---------- */
    const loadAll = () => {
      api({ action: 'orders' }).then((j) => {
        if (j && j.ok && Array.isArray(j.orders)) { markReal(); orders = j.orders; renderOrders(); renderDashboard(); }
      }).catch(() => {});
      api({ action: 'users' }).then((j) => {
        if (j && j.ok && Array.isArray(j.users)) { markReal(); users = j.users; renderUsers(); renderDashboard(); }
      }).catch(() => {});
      api({ action: 'admins' }).then((j) => {
        if (j && j.ok && Array.isArray(j.admins)) { markReal(); admins = j.admins; renderAdmins(); renderDashboard(); }
      }).catch(() => {});
      api({ action: 'messages' }).then((j) => {
        if (j && j.ok && Array.isArray(j.messages)) {
          markReal();
          msgs.length = 0;
          j.messages.forEach((m) => msgs.push({ from: m.from, user_id: m.user_id, id: m.id, text: m.text, date: m.date, unread: !m.read }));
          renderMsgs();
        }
      }).catch(() => {});
      api({ action: 'products' }).then((j) => {
        if (j && j.ok && Array.isArray(j.products)) {
          markReal();
          products = j.products.map((x) => Object.assign({}, x, { full: x }));
          renderProducts();
          renderDashboard();
        }
      }).catch(() => {});
      api({ action: 'settings' }).then((j) => {
        if (j && j.ok && j.settings) {
          markReal();
          Object.keys(SS_FIELDS).forEach((id) => {
            const el = $(id);
            const v = j.settings[SS_FIELDS[id]];
            if (el && v != null && v !== '') el.value = v;
          });
        }
      }).catch(() => {});
      setTimeout(showMockBanner, 2500);
    };
    loadAll();
  }
});
