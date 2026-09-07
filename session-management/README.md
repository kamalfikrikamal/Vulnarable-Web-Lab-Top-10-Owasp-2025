# Broken Session Management Lab (Authentication Failures)

Aplikasi PHP sederhana dengan mekanisme token otentikasi buatan sendiri, mendemonstrasikan
empat kesalahan umum penanganan sesi — bagian dari **A07: Authentication Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Token tetap valid setelah logout | `lab1_token_survives_logout.php` |
| 2 | Session ID sekuensial (bisa diprediksi) | `lab2_predictable_session_id.php` |
| 3 | Session Fixation | `lab3_session_fixation.php` |
| 4 | Session token di URL | `lab4_token_in_url.php` |

Akun demo: `alice/alice123`.

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/sessionmgmt/`, atau lewat Portal → **A07: Authentication Failures**
→ **Broken Session Management**.

## Panduan tiap lab

### Lab 1 — Token survives logout
Login, catat token yang ditampilkan, logout, lalu tempelkan token lama itu ke form "Cek akses
dengan token" — tetap diterima.

### Lab 2 — Predictable session ID
"Simulasikan alice login", lalu "Login sebagai attacker" untuk melihat token milikmu sendiri
(mis. `SESS-1001`). Tebak token alice di sekitar nomor itu (`SESS-1000`).

### Lab 3 — Session fixation
Buka `?fixed_token=FIXED-abc123` (peran attacker memilih token), lalu login sebagai
`alice/alice123` (peran korban) — token pilihan attacker tadi otomatis terikat ke akun alice.
Buktikan lewat form "Cek akses dengan token" pakai `FIXED-abc123`.

### Lab 4 — Token in URL
Login, lihat token muncul di URL/address bar, salin salah satu baris dari "simulasi access log"
di bawah, buka di browser/incognito lain untuk membuktikan sesi bisa dibajak dari log server.

## Mitigasi (untuk didiskusikan setelah lab)
- **Invalidate token di server** saat logout (hapus dari session store), jangan hanya menghapus
  cookie di client (Lab 1).
- Gunakan token dari **CSPRNG** (`random_bytes()`) dengan entropi tinggi, jangan sekuensial
  (Lab 2).
- **Regenerasi token setiap kali terjadi perubahan level privilege** (terutama saat login) —
  jangan pernah melanjutkan memakai token yang sudah ada sebelumnya (`session_regenerate_id()`
  di PHP native session) (Lab 3).
- Token otentikasi harus dikirim lewat **cookie** (idealnya `HttpOnly`, `Secure`, `SameSite`),
  **tidak pernah** lewat URL — URL tercatat di access log, riwayat browser, dan bocor lewat
  Referer (Lab 4).
- Terapkan **idle timeout** & **absolute timeout** pada sesi, dan pertimbangkan mengikat token
  ke atribut tambahan (User-Agent, rentang IP) untuk mempersulit replay token yang dicuri.
