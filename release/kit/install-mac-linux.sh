#!/usr/bin/env sh
# HR PRO V.2 — ติดตั้งสำหรับทดลองใช้ (macOS / Linux)
# วิธีใช้: เปิด Terminal ในโฟลเดอร์นี้ แล้วพิมพ์  sh install-mac-linux.sh
cd "$(dirname "$0")" || exit 1

if ! command -v php >/dev/null 2>&1; then
    echo
    echo "  ไม่พบโปรแกรม PHP บนเครื่องนี้ — ดูขั้นตอนที่ 1 ในไฟล์ START-HERE.html"
    echo
    exit 1
fi

php install.php "$@"
