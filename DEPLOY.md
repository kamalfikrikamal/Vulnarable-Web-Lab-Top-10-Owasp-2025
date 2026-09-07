# Panduan Deploy ke DigitalOcean

Panduan ini untuk meng-host lab ini di satu DigitalOcean Droplet untuk **±26 peserta**,
dengan droplet dibiarkan **menyala terus** untuk dipakai berkelanjutan.

> ⚠️ **Ingat**: `command-injection/` adalah RCE (remote code execution) sungguhan yang sengaja
> dibuat tanpa proteksi. Login gate (Basic Auth) di depan semua lab **wajib** diaktifkan dengan
> kredensial yang sudah diganti dari default sebelum droplet ini online — lihat langkah 5.

## 1. Buat Droplet

- **Spesifikasi**: Basic Droplet, Regular tier, **2 vCPU / 4 GB RAM / 80 GB SSD** (±$24/bulan,
  cek harga terkini di halaman pricing DigitalOcean).
- **OS**: Ubuntu 24.04 LTS.
- Simpan IP publik droplet setelah dibuat, mis. `203.0.113.10`.

## 2. Amankan akses SSH

```bash
ssh root@203.0.113.10
```

- Pastikan login pakai SSH key, bukan password (biasanya sudah default kalau kamu upload SSH
  key saat membuat droplet di DO).
- Nonaktifkan login root via password di `/etc/ssh/sshd_config`:
  ```
  PasswordAuthentication no
  PermitRootLogin prohibit-password
  ```
  lalu `systemctl restart sshd`.

## 3. DigitalOcean Cloud Firewall

Buat Cloud Firewall (menu **Networking → Firewalls** di DO) dan attach ke droplet:

| Arah | Port | Sumber/Tujuan |
|---|---|---|
| Inbound | 22 (SSH) | IP kamu sendiri saja (bukan `0.0.0.0/0`) |
| Inbound | 8079 | `0.0.0.0/0` (aman karena sudah ada login gate — semua lab ada di satu port ini, dibedakan lewat path) |
| Outbound | All TCP/UDP | `0.0.0.0/0` (dibutuhkan untuk `apt`, `docker pull`, dan supaya lab ping/curl tetap berfungsi) |

## 4. Install Docker

```bash
curl -fsSL https://get.docker.com | sh
apt-get install -y docker-compose-plugin
```

## 5. Clone repo & generate kredensial login

```bash
mkdir -p /opt/vuln-web-lab
cd /opt/vuln-web-lab
# copy/clone seluruh isi repo ini ke folder tersebut

apt-get install -y apache2-utils
htpasswd -c gateway/.htpasswd training
# masukkan password yang KUAT saat diminta - ini kredensial yang akan dibagikan ke 26 peserta
```

> Ganti `training` dengan username lain kalau mau. File `.htpasswd` yang sudah ada di repo
> (kalau ada) berisi kredensial **contoh/default** (`training` / `TrainingDemo123!`) yang
> **wajib** diganti dengan langkah di atas sebelum droplet ini diakses publik.

## 6. Blokir akses container ke metadata endpoint

```bash
chmod +x deploy/block-metadata.sh
./deploy/block-metadata.sh

cp deploy/block-metadata.service /etc/systemd/system/
# edit ExecStart di file tersebut kalau path repo bukan /opt/vuln-web-lab
systemctl daemon-reload
systemctl enable --now block-metadata.service
```

Ini mencegah lab command injection dipakai untuk mengakses
`http://169.254.169.254/` (metadata cloud DigitalOcean) lewat SSRF.

## 6b. (Opsional, disarankan untuk droplet RAM kecil) Tambah swap file

Kalau pakai droplet dengan RAM pas-pasan (mis. 1 vCPU/2GB, apalagi kalau dipakai bersama lab
lain di droplet yang sama), tambahkan swap sebagai jaring pengaman supaya lonjakan memory
bikin sistem melambat sesaat, bukan langsung mematikan container (OOM kill):

```bash
chmod +x deploy/setup-swap.sh
./deploy/setup-swap.sh 2048   # ukuran swap dalam MB, default 2048 (2GB) kalau tidak diisi
```

Script ini idempotent (aman dijalankan berulang), tidak menyentuh Docker/container sama
sekali, dan tidak perlu restart apa pun — bisa dijalankan kapan saja tanpa mengganggu lab yang
sedang berjalan. Swap dipasang persist lewat `/etc/fstab` dan `vm.swappiness` diset rendah (10)
supaya swap hanya dipakai saat darurat, bukan rutin.

## 7. Jalankan stack

```bash
cd /opt/vuln-web-lab
docker compose up -d --build
docker compose ps    # pastikan semua service "running"/"healthy"
```

## 8. Bagikan ke peserta

- URL portal: `http://203.0.113.10:8079`
- Username & password: sesuai yang dibuat di langkah 5 (sama untuk semua peserta).

Semua lab ada di **satu port** (8079), dibedakan lewat path (`/sqli/`, `/xss/`, `/cmdi/`,
`/lfi/`, `/upload/`).
Karena itu peserta cukup **login sekali** di prompt Basic Auth pertama — browser otomatis
memakai kredensial yang sama saat berpindah ke lab mana pun tanpa diminta login ulang.

## 9. (Opsional, gratis) Monitoring & alert

Di dashboard DigitalOcean droplet ini, aktifkan **Monitoring** lalu buat alert policy:
- CPU usage > 80% selama beberapa menit berturut-turut
- Outbound bandwidth melonjak signifikan dari biasanya

Ini jadi sinyal dini kalau kredensial bocor dan lab disalahgunakan pihak luar, tanpa perlu
menambah resource limit di `docker-compose.yml`.

## 10. Runbook kalau dicurigai disalahgunakan

**Kunci akses cepat** (ganti password, tidak perlu downtime lab lain):
```bash
htpasswd -c gateway/.htpasswd training   # generate password baru
docker compose up -d --build gateway
```

**Reset total ke state bersih** (semua data lab memang didesain ephemeral - `sqli-db` tidak
pakai named volume untuk data MySQL, jadi restart = kembali ke seed data awal):
```bash
docker compose down -v
docker compose up -d --build
```

## Setelah training selesai untuk sesi itu

Karena droplet dibiarkan menyala terus untuk dipakai berkelanjutan, cukup pastikan langkah 6
(block metadata) tetap aktif (`systemctl status block-metadata.service`) dan kredensial di
`.htpasswd` tidak dibagikan di luar peserta yang sah. Kalau kredensial pernah dibagikan lebih
luas dari yang seharusnya, ulangi langkah "Kunci akses cepat" di atas.
