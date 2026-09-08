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

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Harga dari hidden field dipercaya mentah-mentah | Order tercatat dengan total jauh di bawah harga asli |
| 2 | Kuantitas negatif tidak divalidasi | Wallet balance bertambah lewat "refund" palsu |
| 3 | Kupon sekali pakai tidak ditandai used | Diskon 20% ditumpuk berkali-kali, penghematan terus naik |
| 4 | Step pembayaran tidak ditegakkan server | Order tercatat PAID tanpa pernah melalui step pembayaran |
| 5 | Uniqueness cuma cek string username | Bonus referral difarming tanpa batas |

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
