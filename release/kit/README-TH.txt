HR PRO V.2 — ระบบบริหารงานบุคคล (แจกฟรี)
==========================================

>> เปิดไฟล์ START-HERE.html เพื่อดูคู่มือแบบมีภาพประกอบ <<

ทดลองใช้บนเครื่องตัวเอง (สรุปสั้น)
----------------------------------
1. ติดตั้ง PHP 8.2 ขึ้นไป (Windows/Mac แนะนำ Laravel Herd รุ่นฟรี: https://herd.laravel.com)
2. แตกไฟล์ ZIP นี้ออกมาเป็นโฟลเดอร์ก่อน (อย่ารันจากในไฟล์ ZIP)
3. ติดตั้ง  : ดับเบิลคลิก install-windows.bat      (Mac/Linux: sh install-mac-linux.sh)
4. เปิดระบบ : ดับเบิลคลิก start-windows.bat        (Mac/Linux: sh start-mac-linux.sh)
5. เข้า http://127.0.0.1:8000 แล้วคลิกบัญชีในกล่อง "บัญชีทดลอง" ใต้ฟอร์มเข้าสู่ระบบ

   ผู้ดูแลระบบ  admin@hrpro.local      รหัสผ่าน password
   ฝ่ายบุคคล    hr@hrpro.local         รหัสผ่าน password   (ถ้าโหลดข้อมูลตัวอย่าง)
   หัวหน้างาน   manager@hrpro.local    รหัสผ่าน password   (ถ้าโหลดข้อมูลตัวอย่าง)
   พนักงาน      employee@hrpro.local   รหัสผ่าน password   (ถ้าโหลดข้อมูลตัวอย่าง)

ใช้งานจริง (Production)
-----------------------
ตั้ง APP_ENV=production ใน .env แล้วรัน  php artisan migrate --seed --force
- ระบบจะสุ่มรหัสผ่านผู้ดูแลระบบให้ใหม่ และแสดงเพียงครั้งเดียว (หรือใช้ค่า ADMIN_PASSWORD ที่ตั้งไว้)
- เข้าสู่ระบบครั้งแรกจะถูกบังคับให้ตั้งรหัสผ่านใหม่
- หน้าเข้าสู่ระบบจะไม่แสดงบัญชีทดลอง
- ลืมรหัสผ่าน: php artisan hrpro:admin-password

รายละเอียดทั้งหมดอยู่ใน START-HERE.html หัวข้อ "เส้นทาง B"
