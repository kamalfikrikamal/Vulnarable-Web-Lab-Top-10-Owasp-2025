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
