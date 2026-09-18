/* =========================================================
   فروشگاه نیلا | رابط کاربری صفحه‌ی ورود (نسخه‌ی PHP)
   ------------------------------------------------------------
   - جابه‌جایی تپ‌های «ورود» و «ایجاد حساب»
   - دکمه‌ی گوگل (نمایشی تا مرحله‌ی بعد)
   - ارسال فرم‌ها توسط PHP انجام می‌شود (بدون جلوگیری)
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {

  const tabs = Array.from(document.querySelectorAll('.lg-tab'));
  const panes = Array.from(document.querySelectorAll('.lg-pane'));

  /* ---------- جابه‌جایی تپ‌ها ---------- */
  const activate = (name) => {
    tabs.forEach((t) => t.classList.toggle('active', t.dataset.pane === name));
    panes.forEach((p) => p.classList.toggle('active', p.dataset.pane === name));
  };
  tabs.forEach((t) => t.addEventListener('click', () => activate(t.dataset.pane)));

  /* ---------- دکمه‌ی گوگل (نمایشی) ---------- */
  document.querySelectorAll('.lg-google').forEach((b) => {
    b.addEventListener('click', () => toast('ورود با گوگل در مرحله‌ی بعدی فعال می‌شود.', 'info'));
  });
});
