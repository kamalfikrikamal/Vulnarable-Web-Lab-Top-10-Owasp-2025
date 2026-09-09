# Insecure Design Lab (A06: Insecure Design)

Aplikasi PHP sederhana yang mendemonstrasikan **kerentanan logika bisnis (business logic
vulnerabilities)**: alur checkout, kupon, multi-step workflow, dan program referral yang secara
teknis "berjalan normal" tapi tidak pernah dirancang untuk menahan penyalahgunaan — bagian dari
**A06: Insecure Design**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Price tampering lewat hidden field | `lab1_price_tampering.php` |
| 2 | Kuantitas negatif jadi "refund" | `lab2_negative_quantity.php` |
| 3 | Kupon diskon bisa ditumpuk (stacking/reuse) | `lab3_coupon_stacking.php` |
| 4 | Lompat step pembayaran pada checkout bertahap | `lab4_skip_checkout_step.php` |
| 5 | Bonus referral tanpa batas | `lab5_unlimited_referral_abuse.php` |
| 6 | 2FA bypass lewat forced browsing | `lab6_2fa_forced_browsing.php` |
| 7 | Ganti password tanpa re-autentikasi | `lab7_password_change_no_reauth.php` |
| 8 | Trusted device bypass 2FA | `lab8_trusted_device_bypass.php` |
| 9 | HTTP Parameter Pollution pada kupon | `lab9_coupon_parameter_pollution.php` |
| 10 | Price spoofing lewat header region | `lab10_region_price_spoofing.php` |
| 11 | Over-refund lewat kuantitas return | `lab11_over_refund.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/insecuredesign/`, atau lewat Portal → **A06: Insecure Design**.

## Panduan tiap lab

### Lab 1 — Price tampering lewat hidden field
Form checkout mengirim harga produk lewat `<input type="hidden" name="price" value="150000">`.
Server menghitung `total = $_POST['price'] * $_POST['quantity']` apa adanya, tanpa melihat ulang
harga asli dari katalog. Buka DevTools/Burp, ubah nilai `price` jadi `100` sebelum submit, lalu
lihat pesanan tercatat di riwayat dengan total yang jauh lebih murah dari harga asli.

### Lab 2 — Kuantitas negatif
Kali ini harga selalu dilihat ulang dari katalog server (aman dari tampering), tapi field
`quantity` tidak pernah divalidasi harus bernilai positif. Submit `quantity=-5` untuk produk
seharga Rp 150.000 → `total = -750.000`, dan sistem memperlakukan total negatif sebagai "refund
otomatis" yang menambah `wallet_balance` kamu sebesar Rp 750.000.

### Lab 3 — Kupon diskon bisa ditumpuk
Kupon `DISKON20` (diskon 20%) terlihat sekali pakai, tapi server tidak pernah menandainya "sudah
dipakai" setelah diterapkan. Submit form checkout dengan kode yang sama berkali-kali — tiap
submit dianggap request baru yang sah, dan "total penghematan dari kupon ini" terus bertambah
tanpa batas.

### Lab 4 — Lompat step pembayaran
Checkout 3 step: `?step=1` (review cart) → `?step=2` (pembayaran) → `?step=3` (konfirmasi &
selesai). UI cuma menuntun lewat tautan; server tidak pernah mengecek session/db flag bahwa step 2
benar-benar dieksekusi sebelum step 3 dijalankan. Ketik langsung `?step=3` di address bar tanpa
pernah membuka step 2 — pesanan tetap tercatat **PAID/COMPLETED**.

### Lab 5 — Bonus referral tanpa batas
Program referral memberi bonus tetap Rp 50.000 ke `alice` tiap ada pendaftaran baru dengan kode
referralnya. Satu-satunya pengecekan "orang baru" adalah string `username` persis sama — tidak
ada verifikasi email, tidak ada batas per-IP/per-session, tidak ada cap total. Daftar berulang
dengan `user1`, `user2`, `user3`, ... memakai kode yang sama, dan lihat saldo wallet alice serta
jumlah pendaftar terus naik.

### Lab 6 — 2FA bypass lewat forced browsing
Login dengan `alice` / `Password123` di step 1 — server mengarahkan ke step OTP. Alih-alih
mengisi OTP, ubah URL langsung jadi `?step=dashboard` — dashboard tetap terbuka lengkap dengan
peringatan bahwa OTP tidak pernah diverifikasi.

### Lab 7 — Ganti password tanpa re-autentikasi
Isi form "Ganti Password" hanya dengan password baru (tidak ada field password lama sama
sekali). Lihat log perubahan password di bawah form — kolom "Password lama diminta?" selalu
"TIDAK PERNAH".

### Lab 8 — Trusted device bypass 2FA
Tanpa pernah login sebelumnya, set cookie `trusted_device=alice` lewat DevTools/curl, lalu login
dengan `alice` / `Password123` — OTP dilewati sepenuhnya dan langsung masuk ke dashboard.
Bandingkan dengan login tanpa cookie ini (step OTP normal muncul).

### Lab 9 — HTTP Parameter Pollution pada kupon
Isi kotak "Kode kupon 1" dan "Kode kupon 2" dengan kode yang SAMA (`HEMAT10`), submit satu kali.
Diskon 10% diterapkan dua kali dalam satu request (Rp 450.000 → Rp 364.500, bukan Rp 405.000).

### Lab 10 — Price spoofing lewat header region
```bash
curl -X POST -H "X-Region: US" http://localhost:8079/insecuredesign/lab10_region_price_spoofing.php
```
Harga yang seharusnya Rp 1.500.000 (region ID) berubah jadi Rp 99 (region US) hanya dengan
mengubah header request.

### Lab 11 — Over-refund lewat kuantitas return
Order #9001 cuma berisi 1x Sepatu Sneakers. Ajukan return dengan `return_qty=50` — refund
sebesar 50x harga produk tetap dikreditkan ke wallet, jauh melebihi yang pernah dibeli.

## Mitigasi (untuk didiskusikan setelah lab)
- **Jangan pernah percaya harga/nilai uang yang dikirim klien.** Selalu hitung ulang harga dari
  sumber kebenaran di server (katalog/database), tidak peduli apa yang dikirim lewat form/hidden
  field/API request.
- Validasi field kuantitas/jumlah berada dalam rentang yang masuk akal (mis. `quantity > 0` dan
  ada batas atas wajar) — jangan asumsikan aritmatika akan selalu menghasilkan nilai positif.
- Aturan bisnis "sekali pakai" (kupon, kode promo, token undangan) harus ditegakkan dengan state
  nyata di server: tandai sebagai "used", ikat ke transaksi/order id tertentu, atau pakai
  idempotency key — bukan sekadar "terapkan matematikanya kalau kode string-nya cocok".
- Alur multi-step (checkout, onboarding, approval) harus ditegakkan lewat **state machine di
  server**, bukan cuma dipandu lewat tautan/UI. Tiap step harus memverifikasi step sebelumnya
  benar-benar selesai (via flag session/db), bukan mengasumsikan urutan akses dari UI.
- Mekanisme reward/referral/promo perlu batas anti-abuse: rate limiting, cap per user/device/IP,
  verifikasi email/nomor HP, dan deteksi pola fraud (velocity check) — bukan cuma cek keunikan
  string yang trivial dilewati.
- Secara umum: **threat-model alur bisnisnya sendiri saat desain**, bukan cuma mengaudit kode yang
  mengimplementasikannya setelah jadi. Tanyakan "apa yang terjadi kalau step ini dilewati / nilai
  ini dibuat negatif / aksi ini diulang N kali?" sejak tahap desain.
- **Login bertahap (2FA/MFA) harus ditegakkan konsisten di SETIAP endpoint** yang mengandalkan
  "sudah login sepenuhnya" — cek semua flag yang relevan (password DAN 2FA), bukan cuma salah
  satunya, di setiap halaman/API, bukan cuma di alur navigasi normalnya.
- **Aksi sensitif (ganti password, ganti email, hapus akun) butuh step-up authentication** —
  minta password saat ini atau re-verifikasi 2FA sebelum mengeksekusinya, jangan cuma
  mengandalkan "sesi masih aktif" sebagai bukti identitas.
- **Token "remember me"/"trusted device" harus acak (CSPRNG), diikat ke identitas
  device/session tertentu di server, dan cuma diterbitkan SETELAH verifikasi lengkap** — bukan
  nilai yang bisa ditebak/disalin seperti username polos.
- **Field yang sah menerima banyak nilai (array parameter) harus di-deduplikasi** sebelum
  logika bisnis diterapkan per nilai — jangan asumsikan tiap kemunculan pasti nilai yang berbeda.
- **Jangan percaya header/parameter yang dikirim klien untuk keputusan bisnis** (harga per
  region, lokasi, dst.) — pakai sumber yang tidak bisa dimanipulasi langsung oleh pengirim
  request.
- **Refund/return harus divalidasi terhadap riwayat transaksi asli** — kuantitas yang diminta
  tidak boleh melebihi kuantitas yang benar-benar dibeli dan belum pernah di-refund sebelumnya.
