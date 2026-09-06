#!/usr/bin/env bash
# Memblokir container Docker mengakses cloud metadata endpoint (169.254.169.254).
# Mencegah lab command injection dipakai untuk SSRF ke metadata DigitalOcean.
# Idempotent - aman dijalankan berulang kali (mis. lewat systemd tiap boot).
set -euo pipefail

METADATA_IP="169.254.169.254"

if ! iptables -C DOCKER-USER -d "$METADATA_IP" -j DROP 2>/dev/null; then
    iptables -I DOCKER-USER -d "$METADATA_IP" -j DROP
    echo "Rule ditambahkan: blokir akses container ke $METADATA_IP"
else
    echo "Rule sudah ada, tidak ada perubahan."
fi
