FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git \
    && rm -rf /var/lib/apt/lists/*

COPY src/ /var/www/html/
COPY apache/backup_listing.conf /etc/apache2/conf-available/backup_listing.conf
COPY apache/vcs_demo_indexes.conf /etc/apache2/conf-available/vcs_demo_indexes.conf
RUN a2enconf backup_listing vcs_demo_indexes

# Lab 13: image dasar (Debian) sudah meng-hardening TraceEnable ke Off lewat
# security.conf bawaan - dibalik lagi ke On di sini untuk mensimulasikan
# server yang belum/tidak di-hardening (atau sysadmin yang sengaja
# menyalakannya kembali untuk "debugging" dan lupa mematikannya lagi).
RUN sed -i 's/^TraceEnable Off/TraceEnable On/' /etc/apache2/conf-available/security.conf

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data

# Lab 7: subsite kecil yang di-deploy dengan menyalin seluruh folder kerja
# (termasuk .git) apa adanya ke webroot - skenario deploy paling umum yang
# menyebabkan .git ter-expose ke publik. Riwayat commit-nya sengaja dibuat
# supaya ada secret yang pernah di-commit lalu "dihapus" - masih utuh di
# history walau tidak ada lagi di working tree/HEAD saat ini.
RUN mkdir -p /var/www/html/vcs-demo && cd /var/www/html/vcs-demo \
    && git init -q -b main \
    && git config user.email "dev@corp.test" \
    && git config user.name "Dev Corp" \
    && printf '<!doctype html><html><body><h1>Corp Landing (demo)</h1><p>Subsite statis kecil untuk landing page marketing.</p></body></html>' > index.html \
    && git add index.html \
    && git commit -q -m "Initial commit: landing page" \
    && printf 'DB_HOST=db-prod.internal\nDB_USER=corp_app\nDB_PASSWORD=Gr4nma_Git_Hist0ry_Le4k_2024!\n' > config-secret.txt \
    && git add config-secret.txt \
    && git commit -q -m "WIP: nyimpen config db buat testing" \
    && git rm -q config-secret.txt \
    && git commit -q -m "Hapus file config, harusnya emang gak pernah di-commit" \
    && printf '<!doctype html><html><body><h1>Corp Landing (demo)</h1><p>Subsite statis kecil untuk landing page marketing. Belum final, jangan share dulu.</p></body></html>' > index.html \
    && git add index.html \
    && git commit -q -m "Update copy landing page" \
    && chown -R www-data:www-data /var/www/html/vcs-demo

EXPOSE 80
