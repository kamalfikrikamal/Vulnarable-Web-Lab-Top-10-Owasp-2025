#!/usr/bin/env bash
# Menambahkan swap file sebagai jaring pengaman memory untuk VPS dengan RAM pas-pasan.
# Tidak menyentuh Docker/container - aman dijalankan tanpa restart apa pun.
# Idempotent - aman dijalankan berulang kali.
set -euo pipefail

SWAP_FILE="/swapfile"
SWAP_SIZE_MB="${1:-2048}"

if swapon --show | grep -q "^${SWAP_FILE}"; then
    echo "Swap sudah aktif di $SWAP_FILE, tidak ada perubahan."
    exit 0
fi

if [ ! -f "$SWAP_FILE" ]; then
    fallocate -l "${SWAP_SIZE_MB}M" "$SWAP_FILE" 2>/dev/null || dd if=/dev/zero of="$SWAP_FILE" bs=1M count="$SWAP_SIZE_MB"
    chmod 600 "$SWAP_FILE"
    mkswap "$SWAP_FILE"
fi

swapon "$SWAP_FILE"

if ! grep -qF "$SWAP_FILE" /etc/fstab; then
    echo "$SWAP_FILE none swap sw 0 0" >> /etc/fstab
fi

# Swappiness rendah: swap dipakai hanya saat RAM benar-benar habis (darurat),
# bukan dipakai rutin - supaya tidak mempengaruhi performa aplikasi yang sedang
# berjalan normal (Docker containers tidak disentuh sama sekali oleh script ini).
sysctl -w vm.swappiness=10 >/dev/null
if ! grep -q "^vm.swappiness" /etc/sysctl.conf 2>/dev/null; then
    echo "vm.swappiness=10" >> /etc/sysctl.conf
fi

echo "Swap ${SWAP_SIZE_MB}MB aktif di $SWAP_FILE (persist setelah reboot), vm.swappiness=10"
swapon --show
free -h
