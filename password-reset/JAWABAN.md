# Kunci Jawaban — Password Reset Flaws Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Token leak via tracking pixel (`lab1_token_leak_email.php`)
**Kode:** email berisi `<img src="mail_tracker_beacon.php?leaked_token=$token">` di samping link
reset resmi yang memakai token sama.
**Langkah:** request reset untuk admin, refresh untuk melihat log tracker, salin token, submit
di form Confirm Reset.
**Kenapa berhasil:** gambar apa pun di HTML email dimuat otomatis oleh sebagian besar klien
email/webmail preview — termasuk gambar dari domain analytics pihak ketiga yang URL-nya
kebetulan (atau sengaja, demi "tracking open-rate") menyertakan token rahasia yang sama dengan
link reset.
**Hasil:** password admin berhasil direset oleh pihak yang hanya mengakses log tracker,
bukan email itu sendiri.

---

## Lab 2 — Token reuse (`lab2_token_reuse.php`)
**Kode:** validasi hanya `if (!empty($db['reset_tokens'][$token]))` — tidak ada penghapusan
atau flag `used` setelah reset berhasil.
**Langkah:** request reset, gunakan token yang sama 2-3 kali di Confirm Reset.
**Kenapa berhasil:** token yang seharusnya sekali pakai tetap berfungsi selamanya karena tidak
ada mekanisme invalidasi.
**Hasil:** password admin bisa direset berulang kali dengan token yang sama.

---

## Lab 3 — Brute-forceable code (`lab3_brute_forceable_code.php`)
**Kode:** kode 4 digit (`random_int(0,9999)`), form Confirm Reset tanpa counter/lockout apa pun.
**Langkah:** request reset, klik "Brute Force Kode".
**Kenapa berhasil:** ruang kemungkinan sangat kecil (10.000) dan tidak ada apa pun yang
memperlambat percobaan berturut-turut.
**Hasil:** kode ditemukan dalam <1 detik lewat loop 0000–9999.

---

## Lab 4 — Host header poisoning (`lab4_host_header_poisoning.php`)
**Kode:** `$host = $_SERVER['HTTP_HOST']; $reset_link = "http://$host/...`
**Payload:**
```bash
curl -X POST -H "Host: attacker-evil.test" -d "username=admin&request_reset=1" \
  http://target/pwreset/lab4_host_header_poisoning.php
```
**Kenapa berhasil:** header `Host` dikirim oleh klien dan bisa diisi bebas — server keliru
memperlakukannya sebagai domain aplikasi yang tepercaya saat membangun link absolut untuk
dikirim lewat kanal eksternal (email).
**Hasil:** link reset yang "dikirim" ke admin menunjuk ke `attacker-evil.test`, bukan domain
aplikasi sah — kalau link itu diklik (atau di-preview otomatis oleh pemindai keamanan email),
token jatuh ke tangan attacker.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Tracking pixel membawa token | Token bocor lewat log tracker pihak ketiga |
| 2 | Token tak di-invalidate | Reset berulang dengan token yang sama |
| 3 | Kode pendek tanpa rate limit | Kode ditemukan lewat brute force <1 detik |
| 4 | Host header dipercaya mentah | Link reset mengarah ke domain attacker |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Hindari resource pihak ketiga yang membawa token rahasia di URL-nya.
- Invalidate token/kode segera setelah dipakai.
- Kode pendek wajib dikombinasikan rate limiting ketat, atau pakai token panjang CSPRNG.
- Jangan bangun URL absolut dari header Host; gunakan domain tetap yang dikonfigurasi server.
