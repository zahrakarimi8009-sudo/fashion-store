/* =========================================================
   فروشگاه نیلا | صفحه اصلی
   ------------------------------------------------------------
   - هیرو اسلایدر (خودکار + فلش‌ها + نقطه‌ها)
   - بخش پیشنهاد ویژه
   - بخش جدیدترین‌ها
   - بخش پرفروش‌ترین‌ها (با امتیاز)
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {

  /* ---------- هیرو اسلایدر ---------- */
  const slides = Array.from(document.querySelectorAll('.hs-slide'));
  const dots = Array.from(document.querySelectorAll('.hs-dots button'));
  const slider = document.getElementById('heroSlider');
  let idx = 0;
  let timer = null;

  if (slides.length) {
    const go = (i) => {
      idx = (i + slides.length) % slides.length;
      slides.forEach((s, n) => s.classList.toggle('active', n === idx));
      dots.forEach((d, n) => d.classList.toggle('active', n === idx));
    };
    const next = () => go(idx + 1);
    const prev = () => go(idx - 1);
    const start = () => { stop(); timer = setInterval(next, 5500); };
    const stop = () => { if (timer) { clearInterval(timer); timer = null; } };

    document.getElementById('hsNext').addEventListener('click', () => { next(); start(); });
    document.getElementById('hsPrev').addEventListener('click', () => { prev(); start(); });
    dots.forEach((d, n) => d.addEventListener('click', () => { go(n); start(); }));

    /* مکث هنگام هاور */
    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);

    /* سوایپ در موبایل */
    let touchX = null;
    slider.addEventListener('touchstart', (e) => { touchX = e.touches[0].clientX; }, { passive: true });
    slider.addEventListener('touchend', (e) => {
      if (touchX === null) return;
      const dx = e.changedTouches[0].clientX - touchX;
      if (Math.abs(dx) > 50) {
        /* در RTL، کشیدن به چپ = اسلاید بعدی */
        if (dx < 0) next(); else prev();
        start();
      }
      touchX = null;
    }, { passive: true });

    go(0);
    start();
  }

  nilaLoadProducts().then(() => {
    /* ---------- پیشنهاد ویژه (تخفیف‌دارها) ---------- */
    const offerGrid = document.getElementById('offerGrid');
    if (offerGrid) {
      const offers = PRODUCTS
        .filter((p) => p.salePrice)
        .sort((a, b) => discountOf(b) - discountOf(a))
        .slice(0, 4);
      renderProducts(offerGrid, offers);
    }

    /* ---------- جدیدترین‌ها ---------- */
    const newGrid = document.getElementById('newGrid');
    if (newGrid) {
      const fresh = PRODUCTS
        .slice()
        .sort((a, b) => (a.date < b.date ? 1 : -1))
        .slice(0, 4);
      renderProducts(newGrid, fresh);
    }

    /* ---------- پرفروش‌ترین‌ها (با امتیاز و تعداد فروش) ---------- */
    const bestGrid = document.getElementById('bestGrid');
    if (bestGrid) {
      const best = PRODUCTS
        .slice()
        .sort((a, b) => b.sales - a.sales)
        .slice(0, 4);
      renderProducts(bestGrid, best, { rating: true });
    }
  });

  /* ---------- خبرنامه (نمایش حالت موفقیت) ---------- */
  const nlForm = document.getElementById('nlForm');
  if (nlForm) {
    nlForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const email = nlForm.querySelector('input');
      if (!email.value.trim() || !email.checkValidity()) {
        toast('لطفاً یک ایمیل معتبر وارد کنید.', 'error');
        email.focus();
        return;
      }
      nlForm.style.display = 'none';
      const box = document.getElementById('nlSuccess');
      if (box) box.classList.add('show');
      toast('عضویت شما در خبرنامه نیلا ثبت شد.');
    });
  }
});
