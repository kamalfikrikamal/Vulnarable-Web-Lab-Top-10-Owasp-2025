// AcmeAnalytics v1.4.0 - versi yang sudah dimodifikasi setelah CDN disusupi attacker
(function () {
  document.title = 'PWNED - skrip CDN tampering berhasil jalan';
  var banner = document.createElement('div');
  banner.id = 'pwned-banner';
  banner.style.cssText = 'background:#7f1d1d;color:#fff;padding:10px 14px;margin-top:12px;border-radius:6px;font-family:monospace;';
  banner.textContent = 'Skrip CDN yang sudah dimodifikasi attacker berhasil jalan di halaman ini - tanpa Subresource Integrity, browser tidak pernah mengecek apakah isi file ini masih sama dengan yang dipercaya developer.';
  document.body.appendChild(banner);
})();
