# Sensitive Data Exposure Lab (Cryptographic Failures)

Aplikasi PHP sederhana yang mendemonstrasikan tiga kanal umum kebocoran data sensitif di luar
masalah "enkripsi lemah" murni — bagian dari **A04: Cryptographic Failures**.

| Lab | Kategori | File |
|---|---|---|
| 1 | Data sensitif di URL, bocor lewat Referer | `lab1_url_leak.php` |
| 2 | Header `Cache-Control` tidak diset | `lab2_missing_cache_control.php` |
| 3 | Response tidak menerapkan masking yang konsisten | `lab3_unmasked_response.php` |

## Menjalankan

```bash
cd ..
docker compose up -d --build
```

Buka `http://localhost:8079/dataexposure/`, atau lewat Portal → **A04: Cryptographic Failures**
→ **Sensitive Data Exposure**. Tidak perlu login form — gunakan link "Ganti ke alice/bob" di
navbar untuk berpindah user demo.

## Panduan tiap lab

### Lab 1 — Data sensitif di URL
Nomor kartu ada di query string `?card=...`. Widget "analytics pihak ketiga" (disimulasikan
lokal) menangkap header `Referer` yang berisi URL lengkap tersebut — lihat bagian log di bawah
halaman lab setelah refresh.

### Lab 2 — Missing Cache-Control
1. "Ganti ke bob", buka `?mode=insecure`.
2. "Ganti ke alice", buka `?mode=insecure` lagi — alice melihat data **bob** dari shared cache.
3. "Clear cache", ulangi dengan `?mode=secure` — tiap user selalu melihat datanya sendiri.

### Lab 3 — Data tidak di-mask
Bandingkan "Halaman Profil" (kartu ter-mask `************1234`) dengan "Riwayat Transaksi"
(kartu tampil lengkap) — bug konsistensi masking antar endpoint.

## Mitigasi (untuk didiskusikan setelah lab)
- Jangan pernah menaruh data sensitif (kartu kredit, token, PII) di **URL** (query string) —
  URL tercatat di riwayat browser, log server/proxy/CDN, dan bocor lewat header `Referer` ke
  resource pihak ketiga. Gunakan body request (POST) atau header khusus.
- Set `Cache-Control: no-store` (dan idealnya `Pragma: no-cache` untuk kompatibilitas lama) di
  **setiap** response yang mengandung data sensitif per-pengguna, terutama di belakang shared
  cache/CDN/proxy.
- Terapkan masking/redaction di **satu lapisan terpusat** (mis. serializer/DTO khusus untuk data
  sensitif), jangan mengandalkan tiap endpoint mengingat untuk memanggilnya manual — itulah
  penyebab inkonsistensi seperti di Lab 3.
- Terapkan prinsip **data minimization**: jangan kirim field sensitif ke client sama sekali
  kalau UI tidak benar-benar membutuhkannya utuh.
