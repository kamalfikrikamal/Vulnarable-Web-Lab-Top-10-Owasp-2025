# Kunci Jawaban — Insecure Design Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Price tampering lewat hidden field (`lab1_price_tampering.php`)
**Kode:** `$total = (float)$_POST['price'] * (int)$_POST['quantity'];` — harga diambil langsung
dari input klien, tidak pernah dicocokkan ulang dengan katalog produk (`Kaos Polos Import`,
harga asli Rp 150.000).
**Payload:** submit form dengan `price=100` (bukan `150000`) dan `quantity=5`.
**Kenapa berhasil:** harga cuma "ditampilkan" ke server lewat hidden field, tapi server memercayai
nilai itu apa adanya alih-alih melihat ulang harga sebenarnya dari sumber kebenaran (katalog di
server).
**Hasil:** pesanan tercatat di `data/db.json` dengan total Rp 500 untuk 5 barang yang seharusnya
seharga Rp 750.000 (catatan otomatis menandai "harga tidak sesuai katalog").

---

## Lab 2 — Kuantitas negatif (`lab2_negative_quantity.php`)
**Kode:** harga dilihat ulang dari katalog server (`find_product()`), tapi
`$qty = (int)$_POST['quantity']` tidak pernah divalidasi `> 0`. `total = $price * $qty`; jika
`$total < 0`, `$db['wallet_balance'] += abs($total)`.
**Payload:** `product_id=1` (Rp 150.000), `quantity=-5`.
**Kenapa berhasil:** logika bisnis diam-diam mengasumsikan kuantitas selalu positif, sehingga
"total belanja" bisa jadi negatif — dan kode memperlakukan nilai negatif itu sebagai kejadian sah
("refund otomatis") alih-alih menolaknya sebagai input tidak valid.
**Hasil:** `wallet_balance` bertambah Rp 750.000 tanpa transaksi refund/pembayaran yang sah sama
sekali.

---

## Lab 3 — Kupon diskon bisa ditumpuk (`lab3_coupon_stacking.php`)
**Kode:** kupon `DISKON20` dicari lewat `strcasecmp()`, diskon dihitung dan ditambahkan ke
`coupon['total_saved']` / `times_used` — tapi tidak ada flag `used_by`/one-time yang memblokir
pemakaian berikutnya.
**Payload:** submit form checkout (`quantity=1`, `coupon_code=DISKON20`) berkali-kali secara
berturut-turut.
**Kenapa berhasil:** server memperlakukan tiap submit sebagai request checkout baru yang berdiri
sendiri dan sah — tidak ada state yang mengikat "kupon ini sudah dipakai untuk order tertentu"
sehingga mencegah pemakaian ulang.
**Hasil:** "total penghematan dari kupon ini" naik terus tiap submit (Rp 30.000 → Rp 60.000 →
dst.) — diskon 20% yang seharusnya sekali pakai jadi bisa ditumpuk tanpa batas.

---

## Lab 4 — Lompat step pembayaran (`lab4_skip_checkout_step.php`)
**Kode:** halaman `?step=3` langsung menambahkan order berstatus `PAID` ke `data/db.json` begitu
diakses — tidak ada pengecekan flag session/db yang membuktikan `?step=2` (pembayaran) pernah
dieksekusi.
**Payload:** akses langsung `lab4_skip_checkout_step.php?step=3` tanpa pernah membuka `?step=2`.
**Kenapa berhasil:** alur 3-step hanya "dipandu" lewat tautan UI (step 1 → 2 → 3), bukan
ditegakkan lewat state machine di server. Server tidak tahu (dan tidak peduli) apakah pengunjung
datang dari step 2 yang sah atau langsung mengetik URL.
**Hasil:** pesanan tercatat **PAID/COMPLETED** di riwayat pesanan padahal step pembayaran tidak
pernah benar-benar terjadi.

---

## Lab 5 — Bonus referral tanpa batas (`lab5_unlimited_referral_abuse.php`)
**Kode:** pengecekan "pendaftar baru" hanya `foreach ($signups as $s) if ($s['username'] ===
$username)` — string match persis pada username, tanpa verifikasi email/IP/device, tanpa cap
jumlah total.
**Payload:** submit form registrasi berulang dengan `ref=ALICE-REF` dan username berbeda tiap
kali: `user1`, `user2`, `user3`, ...
**Kenapa berhasil:** program referral dirancang dengan asumsi tiap pendaftaran mewakili orang yang
benar-benar baru dan berbeda, tapi tidak ada mekanisme apa pun (verifikasi email, rate limit,
fraud detection) yang benar-benar memaksakan asumsi itu — cukup ganti string username.
**Hasil:** saldo wallet `alice` bertambah Rp 50.000 tiap pendaftaran baru dan jumlah "total akun
yang mendaftar lewat kode referral ini" naik tanpa batas (dicoba: `user1` → 1 akun, `user2` → 2
akun, dst.), sementara mencoba `username` yang sama persis ditolak — membuktikan satu-satunya
pertahanan cuma string matching yang trivial dilewati.

---

## Lab 6 — 2FA bypass lewat forced browsing (`lab6_2fa_forced_browsing.php`)
**Kode:** halaman dashboard (`step=dashboard`) cuma mengecek `$_SESSION['la6_authenticated']`
sebelum menampilkan konten — tidak pernah mengecek `$_SESSION['la6_otp_verified']` sama sekali,
padahal login baru dianggap "lengkap" kalau keduanya `true`.
**Payload:** login (`alice`/`Password123`) untuk mendapat `la6_authenticated=true`, lalu akses
langsung `?step=dashboard` tanpa pernah submit OTP.
**Kenapa berhasil:** server menyimpan dua flag terpisah untuk merepresentasikan tiap faktor,
tapi endpoint yang mengandalkannya cuma mengecek satu — urutan step di alur login cuma "dipandu"
lewat tautan UI, tidak benar-benar ditegakkan di titik akses yang sebenarnya.
**Hasil:** dashboard akun (saldo, kartu tersimpan, alamat) terbuka penuh tanpa OTP pernah
diverifikasi sama sekali.

---

## Lab 7 — Ganti password tanpa re-autentikasi (`lab7_password_change_no_reauth.php`)
**Kode:** `$db['auth_account']['password'] = $_POST['new_password'];` — tidak ada field/
pengecekan password lama, tidak ada step-up auth (re-masukkan password/re-verifikasi 2FA)
sebelum mengeksekusi perubahan.
**Payload:** submit form dengan `new_password` apa saja.
**Kenapa berhasil:** aplikasi menganggap "sesi aktif = pemilik akun yang sedang memakainya" —
asumsi yang keliru kalau sesi bisa dicuri/dibajak sebentar (komputer publik, cookie bocor).
**Hasil:** log perubahan password mencatat password baru berhasil terpasang, kolom "password
lama diminta?" selalu "TIDAK PERNAH" — siapa pun dengan sesi aktif bisa mengunci pemilik asli.

---

## Lab 8 — Trusted device bypass 2FA (`lab8_trusted_device_bypass.php`)
**Kode:** `if (($_COOKIE['trusted_device'] ?? '') === $acc['username']) { $_SESSION['la8_otp_verified'] = true; ... }`
— nilai cookie yang dianggap "device sudah dipercaya" adalah username POLOS, bukan token acak
yang di-generate & diikat ke device tertentu setelah OTP benar-benar diverifikasi.
**Payload:** set cookie `trusted_device=alice` (lewat DevTools/`curl -b`) SEBELUM pernah login
sama sekali, lalu login dengan password yang benar.
**Kenapa berhasil:** "bukti perangkat terpercaya" cuma berupa nilai yang gampang ditebak (identik
dengan username) dan tidak pernah benar-benar diverifikasi pernah lulus OTP di server manapun —
memasang cookie ini secara manual di device manapun sama efektifnya dengan device yang benar-benar
"dipercaya" lewat proses normal.
**Hasil:** login langsung berhasil ke dashboard tanpa step OTP muncul sama sekali — dibandingkan
tanpa cookie ini, di mana step OTP tetap muncul seperti seharusnya (dashboard di lab ini memang
sudah benar mengecek kedua flag, membuktikan bug-nya murni di kekuatan token trusted-device-nya).

---

## Lab 9 — HTTP Parameter Pollution pada kupon (`lab9_coupon_parameter_pollution.php`)
**Kode:** `foreach ($_POST['coupon'] as $code) { if (cocok) { $total -= $total * $percent; } }`
— loop menerapkan diskon untuk SETIAP kemunculan kode yang cocok di array, tanpa mengecek apakah
kode itu sudah diterapkan sebelumnya di iterasi yang sama.
**Payload:** dua field `coupon[]` diisi kode yang identik (`HEMAT10`, `HEMAT10`).
**Kenapa berhasil:** field array kupon memang didesain sah untuk menerima BEBERAPA kode BERBEDA
sekaligus (mis. gift card + promo) — tapi tidak pernah didesain untuk kasus kode YANG SAMA
muncul lebih dari sekali, dan tidak ada deduplikasi yang menutup celah itu.
**Hasil:** diskon 10% diterapkan dua kali secara berurutan dalam satu request (Rp 450.000 → Rp
405.000 → Rp 364.500), bukan cuma sekali seperti yang seharusnya untuk satu kode kupon.

---

## Lab 10 — Price spoofing lewat header region (`lab10_region_price_spoofing.php`)
**Kode:** `$region = $_SERVER['HTTP_X_REGION'] ?? $_GET['region'] ?? 'ID'; $price = $product['price_by_region'][$region];`
— region diambil murni dari header/parameter yang dikirim client.
**Payload:** `curl -X POST -H "X-Region: US" .../lab10_region_price_spoofing.php`
**Kenapa berhasil:** tidak ada sumber tepercaya (IP geolocation di server, alamat billing
terverifikasi) yang dipakai untuk menentukan region — aplikasi percaya begitu saja pada header
yang sepenuhnya dikendalikan pengirim request.
**Hasil:** harga produk digital berubah dari Rp 1.500.000 (region ID, default) menjadi Rp 99
(region US) hanya dengan mengubah satu header request.

---

## Lab 11 — Over-refund lewat kuantitas return (`lab11_over_refund.php`)
**Kode:** `$refund_amount = $order['price'] * (int)$_POST['return_qty'];` — tidak ada
perbandingan dengan `$order['quantity']` (kuantitas asli yang dibeli) maupun akumulasi
`total_refunded_qty` yang sudah pernah di-refund sebelumnya.
**Payload:** `return_qty=50` untuk order #9001 yang cuma berisi 1x Sepatu Sneakers.
**Kenapa berhasil:** alur refund menghitung nominal murni dari angka yang diminta client, tanpa
pernah memvalidasinya terhadap riwayat transaksi asli sebagai batas atas yang sah.
**Hasil:** refund Rp 22.500.000 (50 x Rp 450.000) dikreditkan ke wallet untuk order yang
nilainya cuma Rp 450.000 — dan submit berulang terus menambah refund lebih jauh lagi karena
tidak ada kuota yang berkurang.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Harga dari hidden field dipercaya mentah-mentah | Order tercatat dengan total jauh di bawah harga asli |
| 2 | Kuantitas negatif tidak divalidasi | Wallet balance bertambah lewat "refund" palsu |
| 3 | Kupon sekali pakai tidak ditandai used | Diskon 20% ditumpuk berkali-kali, penghematan terus naik |
| 4 | Step pembayaran tidak ditegakkan server | Order tercatat PAID tanpa pernah melalui step pembayaran |
| 5 | Uniqueness cuma cek string username | Bonus referral difarming tanpa batas |
| 6 | Dashboard cuma cek satu dari dua flag 2FA | Dashboard terbuka tanpa OTP pernah diverifikasi |
| 7 | Ganti password tanpa step-up auth | Password berganti tanpa pernah diminta password lama |
| 8 | Token trusted-device gampang ditebak/disalin | Login lengkap tanpa OTP lewat cookie yang ditebak |
| 9 | Array parameter kupon tidak di-deduplikasi | Diskon 10% diterapkan 2x dalam satu request |
| 10 | Region ditentukan dari header client | Harga Rp 1.5jt jadi Rp 99 lewat header X-Region |
| 11 | Refund tidak divalidasi terhadap pembelian asli | Refund 50x lipat dari kuantitas yang pernah dibeli |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Jangan pernah percaya harga/nilai uang dari klien — selalu hitung ulang dari sumber kebenaran
  di server.
- Validasi kuantitas/jumlah berada dalam rentang yang masuk akal (positif, ada batas atas).
- Tegakkan aturan "sekali pakai" (kupon, kode promo) dengan state nyata: flag "used", idempotency
  key, ikat ke transaksi tertentu.
- Tegakkan alur multi-step lewat state machine di server, bukan cuma tautan UI.
- Tambahkan batas anti-abuse (rate limiting, cap per user/device/IP, verifikasi) ke setiap
  mekanisme reward/referral/promo.
- Threat-model alur bisnis itu sendiri sejak tahap desain, bukan cuma mengaudit kode setelah jadi.
- Tegakkan 2FA/MFA secara konsisten di SETIAP endpoint, cek semua flag yang relevan.
- Aksi sensitif (ganti password, dst.) butuh step-up authentication.
- Token "remember device" harus acak & diikat ke device tertentu di server, cuma diterbitkan
  setelah verifikasi lengkap.
- Deduplikasi field array sebelum logika bisnis diterapkan per nilai.
- Jangan percaya header/parameter client untuk keputusan bisnis (harga, region, dst.).
- Validasi refund/return terhadap riwayat transaksi asli, bukan angka yang diminta begitu saja.
