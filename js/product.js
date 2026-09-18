/* =========================================================
   فروشگاه نیلا | صفحه جزئیات محصول
   ------------------------------------------------------------
   - گالری تصاویر (اصلی + بندانگشتی) + بزرگ‌نمایی روی تصویر
   - نمایش موجودی (موجود / کم‌موجود)
   - انتخاب رنگ، سایز و تعداد
   - افزودن به سبد خرید و علاقه‌مندی (روی تصویر + کنار تعداد)
   - تب‌های توضیحات کامل / مشخصات فنی / نظرات کاربران (با فرم ثبت نظر)
   - محصولات مشابه
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  nilaLoadProducts().then(() => {

    const params = new URLSearchParams(window.location.search);
    const id = parseInt(params.get('id'), 10);
    const p = getProduct(id) || PRODUCTS[0];

    let size = p.sizes.length === 1 ? p.sizes[0] : null;
    let color = p.colors[0].name;
    let qty = 1;

    /* ---- بوردکرامب و عنوان ---- */
    document.getElementById('pdBreadcrumb').innerHTML = `
      <a href="index.html">صفحه اصلی</a><span class="sep">/</span>
      <a href="shop.html">فروشگاه</a><span class="sep">/</span>
      <a href="shop.html?category=${p.category}">${CATEGORIES[p.category].name}</a><span class="sep">/</span>
      <span>${p.name}</span>`;
    document.getElementById('pdCat').textContent = CATEGORIES[p.category].name;
    document.getElementById('pdName').textContent = p.name;
    document.title = p.name + ' | فروشگاه نیلا';

    /* ---- امتیاز و تعداد فروش ---- */
    document.getElementById('pdRating').innerHTML = `
      <span class="stars">${starsHtml(p.rating)}</span>
      <span>${faNum(p.rating)} از ۵</span>
      <span class="count">(${faNum(p.sales)} فروش موفق)</span>`;

    /* ---- قیمت ---- */
    const off = discountOf(p);
    document.getElementById('pdPrice').innerHTML = `
      ${p.salePrice ? `<span class="old">${fmtPrice(p.price)}</span>` : ''}
      <span class="now">${faNum(effPrice(p))} <small>تومان</small></span>
      ${off ? `<span class="off">${faNum(off)}٪ تخفیف</span>` : ''}`;

    /* ---- موجودی ---- */
    const stockEl = document.getElementById('pdStock');
    if (stockEl) {
      if (p.stock != null && p.stock <= 5) {
        stockEl.classList.add('low');
        stockEl.textContent = 'فقط ' + faNum(p.stock) + ' عدد باقی مانده — عجله کن!';
      } else {
        stockEl.textContent = 'موجود در انبار';
      }
    }

    /* ---- متن‌ها ---- */
    document.getElementById('pdShort').textContent = p.short;
    document.getElementById('pdDesc').innerHTML =
      '<p>' + p.desc + '</p>' +
      '<p style="margin-top:1rem;"><strong>ارسال و بازگشت:</strong> سفارش‌ها ظرف ۲ تا ۵ روز کاری ارسال می‌شوند و برای خریدهای بالای ۲,۰۰,۰۰۰ تومان، ارسال رایگان است. تا ۷ روز پس از دریافت کالا، امکان بازگشت وجود دارد.</p>';

    /* ---- مشخصات ---- */
    document.getElementById('pdSpecs').innerHTML = p.specs
      .map(([k, v]) => `<tr><th>${k}</th><td>${v}</td></tr>`)
      .join('');

    /* ---- گالری ---- */
    const gallery = p.gallery && p.gallery.length ? p.gallery : [p.img];
    const pdMain = document.getElementById('pdMain');
    const zoomBox = document.getElementById('pdZoom');
    const mainImg = document.getElementById('pdMainImg');
    const thumbsBox = document.getElementById('pdThumbs');
    mainImg.src = gallery[0];
    mainImg.alt = p.name;
    thumbsBox.style.gridTemplateColumns = `repeat(${gallery.length}, 1fr)`;
    thumbsBox.innerHTML = gallery.map((src, i) => `
      <div class="pd-thumb${i === 0 ? ' active' : ''}" data-src="${src}">
        <img src="${src}" alt="${p.name} - تصویر ${i + 1}" loading="lazy">
      </div>`).join('');

    /* ---- بزرگ‌نمایی تصویر اصلی ---- */
    let zoomed = false;
    const setZoomOrigin = (e) => {
      const r = zoomBox.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 100;
      const y = ((e.clientY - r.top) / r.height) * 100;
      mainImg.style.transformOrigin = x + '% ' + y + '%';
    };
    const resetZoom = () => {
      zoomed = false;
      zoomBox.classList.remove('zoomed');
      pdMain.classList.remove('zoomed');
      mainImg.style.transformOrigin = 'center center';
    };
    zoomBox.addEventListener('mousemove', (e) => { if (zoomed) setZoomOrigin(e); });
    zoomBox.addEventListener('click', (e) => {
      zoomed = !zoomed;
      zoomBox.classList.toggle('zoomed', zoomed);
      pdMain.classList.toggle('zoomed', zoomed);
      if (zoomed) setZoomOrigin(e);
      else mainImg.style.transformOrigin = 'center center';
    });

    thumbsBox.querySelectorAll('.pd-thumb').forEach((t) => {
      t.addEventListener('click', () => {
        resetZoom();
        mainImg.src = t.dataset.src;
        thumbsBox.querySelectorAll('.pd-thumb').forEach((x) => x.classList.toggle('active', x === t));
      });
    });

    /* ---- رنگ ---- */
    const colorsBox = document.getElementById('pdColors');
    const colorName = document.getElementById('pdColorName');
    colorName.textContent = color;
    colorsBox.innerHTML = p.colors.map((c) => `
      <button class="color-dot${c.name === color ? ' active' : ''}"
              data-color="${c.name}" style="background:${c.hex}"
              title="${c.name}" aria-label="رنگ ${c.name}"></button>`).join('');
    colorsBox.querySelectorAll('.color-dot').forEach((dot) => {
      dot.addEventListener('click', () => {
        color = dot.dataset.color;
        colorName.textContent = color;
        colorsBox.querySelectorAll('.color-dot').forEach((d) => d.classList.toggle('active', d === dot));
      });
    });

    /* ---- سایز ---- */
    const sizesBox = document.getElementById('pdSizes');
    const sizeName = document.getElementById('pdSizeName');
    const paintSizes = () => { sizeName.textContent = size || '—'; };
    sizesBox.innerHTML = p.sizes.map((s) => `
      <button class="size-btn${s === size ? ' active' : ''}" data-size="${s}">${s}</button>`).join('');
    paintSizes();
    sizesBox.querySelectorAll('.size-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        size = btn.dataset.size;
        paintSizes();
        sizesBox.querySelectorAll('.size-btn').forEach((b) => b.classList.toggle('active', b === btn));
      });
    });

    /* ---- تعداد ---- */
    const qtyVal = document.getElementById('qtyVal');
    const paintQty = () => { qtyVal.textContent = faNum(qty); };
    document.getElementById('qtyMinus').addEventListener('click', () => {
      if (qty > 1) { qty--; paintQty(); }
    });
    document.getElementById('qtyPlus').addEventListener('click', () => {
      const cap = (p.stock != null && p.stock < 10) ? p.stock : 10;
      if (qty < cap) { qty++; paintQty(); }
    });

    /* ---- افزودن به سبد ---- */
    document.getElementById('addCartBtn').addEventListener('click', () => {
      if (!size) {
        toast('لطفاً ابتدا سایز موردنظر را انتخاب کنید.', 'error');
        return;
      }
      if (p.stock != null && qty > p.stock) {
        toast('بیش از ' + faNum(p.stock) + ' عدد از این محصول موجود نیست.', 'error');
        return;
      }
      addToCart(p.id, size, color, qty);
      toast('«' + p.name + '» به سبد خرید اضافه شد.');
    });

    /* ---- علاقه‌مندی (دکمه‌ی روی تصویر + دکمه‌ی کنار تعداد) ---- */
    const wishBtns = [document.getElementById('pdWish'), document.getElementById('pdImgWish')].filter(Boolean);
    const paintWish = () => {
      const on = getWish().includes(p.id);
      wishBtns.forEach((b) => b.classList.toggle('active', on));
    };
    paintWish();
    wishBtns.forEach((b) => {
      b.addEventListener('click', () => {
        const added = toggleWish(p.id);
        paintWish();
        toast(added ? '«' + p.name + '» به علاقه‌مندی‌ها اضافه شد' : '«' + p.name + '» از علاقه‌مندی‌ها حذف شد', added ? 'success' : 'info');
      });
    });

    /* ---- نظرات کاربران ---- */
    const reviewsBox = document.getElementById('reviewsBox');
    const revTabCount = document.getElementById('revTabCount');
    let reviews = (typeof REVIEWS !== 'undefined' ? REVIEWS : []).filter((r) => r.productId === p.id);
    const AV_COLORS = ['#ec4d84', '#9c5bd6', '#ff8f5e', '#2f9e6e', '#5b7bc4'];
    const avatarColor = (name) => AV_COLORS[(name.charCodeAt(0) || 0) % AV_COLORS.length];

    const renderReview = (r) => `
      <div class="review">
        <div class="review-head">
          <span class="review-avatar" style="background:${avatarColor(r.name)}">${r.name.slice(0, 1)}</span>
          <span class="review-name">${r.name}</span>
          ${r.verified ? '<span class="review-verified">' + ICONS.check + 'خریدار تأییدشده</span>' : ''}
          <span class="review-stars">${ICONS.star.repeat(r.stars)}</span>
          <span class="review-date">${r.date}</span>
        </div>
        <p class="review-text">${r.text}</p>
      </div>`;

    const renderSummary = () => {
      const sc = document.getElementById('revScore');
      const sub = document.getElementById('revScoreSub');
      if (!sc || !sub) return;
      if (!reviews.length) {
        sc.textContent = '—';
        sub.textContent = 'هنوز نظری ثبت نشده';
        return;
      }
      const avg = reviews.reduce((s, r) => s + (r.stars || 0), 0) / reviews.length;
      const verified = reviews.filter((r) => r.verified).length;
      sc.textContent = faNum(Math.round(avg * 10) / 10);
      sub.textContent = faNum(reviews.length) + ' نظر · ' + faNum(verified) + ' خریدار تأییدشده';
    };

    const renderReviews = () => {
      if (reviewsBox) {
        reviewsBox.innerHTML = reviews.length
          ? reviews.map(renderReview).join('')
          : '<p class="review-empty">هنوز نظری برای این محصول ثبت نشده. اولین نفری باش که تجربه‌اش را می‌نویسد!</p>';
      }
      if (revTabCount) revTabCount.textContent = reviews.length ? '(' + faNum(reviews.length) + ')' : '';
      renderSummary();
    };
    /* نظرات واقعی از دیتابیس؛ اگر PHP در دسترس نباشد، داده‌های نمایشی می‌مانند */
    const loadReviews = () => fetch('api/reviews.php?product_id=' + p.id, { cache: 'no-store' })
      .then((r) => (r && r.ok ? r.json() : Promise.reject(new Error('no-api'))))
      .then((list) => {
        if (Array.isArray(list)) {
          reviews = list.map((r) => ({
            productId: p.id,
            name: r.name,
            text: r.body,
            stars: r.rating,
            date: (r.date && typeof toJalali === 'function') ? toJalali(r.date) : 'تازه',
            verified: !!r.verified,
          }));
        }
      })
      .catch(() => { /* بدون PHP: داده‌های نمایشی */ });
    loadReviews().then(renderReviews);

    /* ورودی امتیاز (ستاره) */
    const rateInput = document.getElementById('rateInput');
    let rateValue = 0;
    const paintStars = (n) => {
      if (!rateInput) return;
      rateInput.querySelectorAll('.rate-btn').forEach((b) => b.classList.toggle('on', Number(b.dataset.star) <= n));
    };
    if (rateInput) {
      rateInput.innerHTML = [1, 2, 3, 4, 5].map((i) =>
        `<button type="button" class="rate-btn" data-star="${i}" aria-label="امتیاز ${i}">${ICONS.star}</button>`).join('');
      rateInput.querySelectorAll('.rate-btn').forEach((b) => {
        b.addEventListener('click', () => { rateValue = Number(b.dataset.star); paintStars(rateValue); });
        b.addEventListener('mouseenter', () => paintStars(Number(b.dataset.star)));
      });
      rateInput.addEventListener('mouseleave', () => paintStars(rateValue));
    }
    const revFormToggle = document.getElementById('revFormToggle');
    if (revFormToggle) {
      revFormToggle.addEventListener('click', () => {
        const f = document.getElementById('reviewForm');
        if (!f) return;
        f.hidden = !f.hidden;
        if (!f.hidden) {
          const nm = document.getElementById('revName');
          if (nm) nm.focus();
        }
      });
    }
    const curUser = (typeof getUser === 'function') ? getUser() : null;
    if (curUser && curUser.name) {
      const nm0 = document.getElementById('revName');
      if (nm0 && !nm0.value) nm0.value = curUser.name;
    }

    /* فرم ثبت نظر */
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
      reviewForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const nameEl = document.getElementById('revName');
        const textEl = document.getElementById('revText');
        const name = (nameEl.value || '').trim();
        const text = (textEl.value || '').trim();
        if (rateValue < 1) { toast('لطفاً امتیاز خود را با ستاره مشخص کنید.', 'error'); return; }
        if (!name) { toast('لطفاً نام خود را بنویسید.', 'error'); nameEl.focus(); return; }
        if (text.length < 5) { toast('متن نظر کمی کوتاه است؛ بیشتر بنویسید.', 'error'); textEl.focus(); return; }

        const doMockAdd = () => {
          reviews.unshift({ productId: p.id, name, text, stars: rateValue, date: 'همین حالا', verified: false });
          renderReviews();
          reviewForm.reset();
          rateValue = 0;
          paintStars(0);
          toast('نظر شما با موفقیت ثبت شد. ممنون از اعتمادتان!');
        };
        try {
          fetch('api/reviews.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: p.id, name, rating: rateValue, body: text }),
          })
            .then((r) => r.json())
            .then((j) => {
              if (j && j.ok) {
                reviewForm.reset();
                rateValue = 0;
                paintStars(0);
                loadReviews().then(renderReviews);
                toast('نظر شما با موفقیت ثبت شد. ممنون از اعتمادتان!');
              } else {
                const msgs = {
                  name_invalid: 'نام خود را کامل‌تر بنویسید (حداقل ۲ حرف).',
                  body_short: 'متن نظر کمی کوتاه است؛ بیشتر بنویسید.',
                  rating_invalid: 'امتیاز خود را با ستاره مشخص کنید.',
                  product_notfound: 'این محصول در دسترس نیست.',
                  db_unavailable: 'ثبت نظر ممکن نشد؛ دوباره تلاش کنید.',
                };
                toast((j && msgs[String(j.error)]) || 'ثبت نظر ممکن نشد؛ دوباره تلاش کنید.', 'error');
              }
            })
            .catch(doMockAdd);
        } catch (err) { doMockAdd(); }
      });
    }

    /* ---- تب‌ها ---- */
    initPanes(document.querySelector('.pd-tabs-sec'));

    /* ---- محصولات مشابه ---- */
    let related = PRODUCTS.filter((x) => x.category === p.category && x.id !== p.id);
    if (related.length < 4) {
      const extra = PRODUCTS.filter((x) => x.category !== p.category && x.id !== p.id);
      related = related.concat(extra).slice(0, 4);
    } else {
      related = related.slice(0, 4);
    }
    renderProducts(document.getElementById('relatedGrid'), related);
  });
});
