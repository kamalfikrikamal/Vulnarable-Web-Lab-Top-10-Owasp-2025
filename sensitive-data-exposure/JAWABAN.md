# Kunci Jawaban — Sensitive Data Exposure Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — Data sensitif di URL (`lab1_url_leak.php`)
**Kode:** kartu dikirim lewat `?card=...`; halaman memuat `<img src="analytics_beacon.php">`.
**Langkah:** buka lab, refresh sekali, lihat bagian log — muncul baris
`Referer: .../lab1_url_leak.php?card=4111111111111234`.
**Kenapa berhasil:** browser mengirim URL lengkap (termasuk query string) sebagai header
`Referer` ke setiap resource yang dimuat halaman, termasuk resource pihak ketiga yang tidak
berhak melihat data sensitif tersebut.
**Hasil:** nomor kartu bocor ke "pihak ketiga" hanya karena posisinya di URL, bukan di body
request.

---

## Lab 2 — Missing Cache-Control (`lab2_missing_cache_control.php`)
**Kode mode insecure:** tidak ada `header('Cache-Control: ...')`; response pertama disimpan ke
file cache bersama dan disajikan ke SIAPA PUN request berikutnya.
**Langkah:** ganti user ke bob → buka `?mode=insecure` (data bob ter-cache) → ganti ke alice →
buka `?mode=insecure` lagi → alice melihat data bob.
**Kenapa berhasil:** tanpa `Cache-Control: no-store`, cache apa pun di antara server dan
browser (proxy, CDN, atau lab ini yang mensimulasikannya secara langsung) berhak menyimpan dan
mengulang response untuk request berikutnya, tanpa tahu bahwa response itu seharusnya unik
per-user.
**Hasil:** data akun bob (saldo, nomor kartu) terlihat oleh alice. Dengan `?mode=secure`
(`Cache-Control: no-store`), setiap user selalu mendapat response fresh miliknya sendiri.

---

## Lab 3 — Data tidak di-mask (`lab3_unmasked_response.php`)
**Kode:** "Halaman Profil" memanggil `mask_card($user['card'])`; "Riwayat Transaksi" memanggil
`$user['card']` langsung, lupa membungkusnya dengan fungsi masking yang sama.
**Kenapa berhasil:** masking diterapkan manual per-endpoint, bukan dipaksakan di satu lapisan
serialisasi terpusat — sangat mudah lupa menerapkannya konsisten di endpoint baru.
**Hasil:** nomor kartu lengkap terlihat di riwayat transaksi meski halaman profil sudah benar.

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | Data sensitif di URL | Referer log menangkap nomor kartu lengkap |
| 2 | Cache-Control absen | Data bob terlihat oleh alice lewat shared cache |
| 3 | Masking tidak konsisten | Kartu lengkap tampil di satu endpoint, ter-mask di lainnya |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Jangan taruh data sensitif di URL/query string.
- `Cache-Control: no-store` untuk semua response berisi data sensitif per-user.
- Masking/redaction terpusat di satu lapisan, bukan manual per-endpoint.
