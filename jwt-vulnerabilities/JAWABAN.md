# Kunci Jawaban — JWT Vulnerabilities Lab

> 📌 **Untuk trainer/pendamping.** Jangan dibagikan ke peserta sebelum sesi selesai.

---

## Lab 1 — alg=none (`lab1_alg_none.php`)
**Kode:** `if ($alg === 'none') { return $p['payload']; }` — tidak ada pengecekan apa pun.
**Payload final:**
```
eyJ0eXAiOiJKV1QiLCJhbGciOiJub25lIn0.eyJ1c2VybmFtZSI6ImFkbWluIiwicm9sZSI6ImFkbWluIn0.
```
(hasil dari `header.payload.` dengan header `{"typ":"JWT","alg":"none"}` dan payload
`{"username":"admin","role":"admin"}`, signature dikosongkan).
**Kenapa berhasil:** `alg: none` adalah bagian sah dari spesifikasi JWS (untuk kasus khusus di
mana integritas sudah dijamin lewat kanal lain), tapi tidak boleh pernah diterima untuk token
otentikasi. Server ini secara eksplisit menerimanya tanpa syarat tambahan apa pun.
**Hasil:** login sebagai admin tanpa pernah tahu secret HMAC yang dipakai server.

---

## Lab 2 — Weak secret (`lab2_weak_secret.php`)
**Secret asli:** `letmein123` (ada di wordlist kandidat yang disediakan).
**Langkah:** isi form "Forge Token" dengan `secret=letmein123`, `username=admin`, `role=admin`
→ salin hasilnya ke form "Verify & Login".
**Kenapa berhasil:** algoritma HS256 sendiri kuat, tapi kekuatan HMAC sepenuhnya bergantung pada
kerahasiaan & entropi secret-nya. Secret pendek/umum bisa di-brute-force offline dalam hitungan
detik-menit dengan `hashcat -m 16500` atau `jwt_tool -C`.
**Hasil:** token admin valid berhasil dipalsukan begitu secret ditemukan.

---

## Lab 3 — No signature check (`lab3_no_signature_check.php`)
**Kode:** `function verify_lab3($token) { ...; return $p['payload']; }` — tidak pernah
menghitung ulang HMAC maupun membandingkannya dengan signature di token.
**Payload final:** payload token asli diubah jadi `{"username":"admin","role":"admin"}`,
signature diisi teks sembarang apa pun (mis. `anything-goes-here-signature-is-never-checked`).
**Kenapa berhasil:** ini adalah kesalahan paling fundamental dalam implementasi JWT — mem-parsing
token tanpa pernah memverifikasi signature-nya sama sekali membuat seluruh mekanisme "tanda
tangan" JWT tidak berguna; payload bisa diubah bebas oleh siapa saja.
**Hasil:** login sebagai admin tanpa perlu tahu secret sama sekali maupun trik `alg` khusus.

---

## Lab 4 — kid path traversal (`lab4_kid_path_traversal.php`)
**Kode:** `$path = __DIR__ . '/keys/' . $kid; file_get_contents($path);` — `$kid` berasal dari
header token (belum diverifikasi) dan dipakai mentah untuk membangun path filesystem.
**Payload final:** header `{"typ":"JWT","alg":"HS256","kid":"../../../../../../dev/null"}`,
payload `{"username":"admin","role":"admin"}`, signature = `HMAC-SHA256(header.payload, "")`
(kunci string kosong, karena isi `/dev/null` selalu kosong).
**Kenapa berhasil:** `kid` seharusnya cuma indeks ke daftar kunci yang sudah dikenal server
(allowlist), bukan path filesystem yang dibangun langsung dari input tak tepercaya — pola yang
identik dengan Local File Inclusion, hanya saja titik masuknya lewat header JWT. Karena
`/dev/null` selalu ada dan selalu kosong di container Linux, attacker bisa memastikan isi
"kunci" yang akan dipakai server tanpa perlu membaca file rahasia apa pun.
**Hasil:** login sebagai admin dengan menandatangani token sendiri memakai kunci yang sudah
diketahui pasti (string kosong).

---

## Ringkasan hasil akhir

| Lab | Teknik | Bukti keberhasilan |
|---|---|---|
| 1 | `alg: none` | Payload dipercaya tanpa signature |
| 2 | Brute-force secret | Token admin dipalsukan dengan secret yang ditebak |
| 3 | Signature tak pernah dicek | Payload bebas diubah, signature diisi sembarang |
| 4 | `kid` path traversal ke `/dev/null` | Kunci HMAC diketahui pasti (string kosong) |

## Mitigasi (ringkas, lihat [README.md](README.md) untuk versi lengkap)
- Allowlist algoritma yang diterima; tolak `none` secara eksplisit.
- Secret HMAC panjang & acak dari CSPRNG, atau pakai RS256/ES256.
- Selalu verifikasi signature sebelum mempercayai payload; gunakan library JWT teruji.
- Validasi `kid` lewat allowlist, jangan langsung jadi bagian path filesystem.
