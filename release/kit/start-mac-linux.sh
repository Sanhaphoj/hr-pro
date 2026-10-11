#!/usr/bin/env sh
# HR PRO V.2 — เปิดระบบ (macOS / Linux)
# วิธีใช้: sh start-mac-linux.sh  แล้วเปิด http://127.0.0.1:8000  (กด Ctrl+C เพื่อหยุด)
cd "$(dirname "$0")" || exit 1

if [ ! -f .env ]; then
    echo "  ยังไม่ได้ติดตั้ง — รัน  sh install-mac-linux.sh  ก่อน"
    exit 1
fi

URL=http://127.0.0.1:8000
echo
echo "  HR PRO V.2 กำลังทำงานที่  $URL   (กด Ctrl+C เพื่อหยุด)"
echo
( sleep 3; { command -v open >/dev/null 2>&1 && open "$URL"; } || { command -v xdg-open >/dev/null 2>&1 && xdg-open "$URL"; } ) >/dev/null 2>&1 &
php artisan serve --host=127.0.0.1 --port=8000
