# HR PRO — ระบบบริหารทรัพยากรบุคคลสำหรับองค์กรขนาดใหญ่

ระบบ HRIS (Human Resource Information System) สำหรับองค์กรขนาด 40–200 คน ออกแบบในสไตล์
**Corporate มืออาชีพ** สร้างด้วย **PHP Laravel 11 + MySQL** เรนเดอร์ฝั่งเซิร์ฟเวอร์ด้วย Blade
และ Design System ที่เขียนเองทั้งหมด (ไม่ต้อง build ขั้นตอน Node)

> 🚀 **ทดลองใช้งานฟรี (Live Demo):** <https://hr-pro-jqqx.onrender.com>
> *(โฮสต์ฟรีบน Render — คำขอแรกอาจช้า ~30–60 วินาทีหากเซิร์ฟเวอร์เพิ่งตื่นจากโหมดพัก)*
>
> **บัญชีสำหรับทดลองใช้งาน:**
> - ผู้ดูแลระบบ (Super Admin): `admin@hrpro.local` / `password`
> - ฝ่ายบุคคล (HR Manager): `hr@hrpro.local` / `password`
> - หัวหน้างาน (Manager): `manager@hrpro.local` / `password`
> - พนักงาน (Employee): `employee@hrpro.local` / `password`

---

## 🖼️ ภาพรวมการทำงานของระบบ (System Mockup)

ภาพจำลองหน้าจอหลัก (เข้าสู่ระบบ / แดชบอร์ด / อนุมัติการลา) พร้อมลำดับการทำงานของวงจรการลาและแผนผังโมดูลทั้งหมด:

![HR PRO — ภาพรวมการทำงานของระบบ](docs/system-mockup.svg)

---

## ✨ ฟีเจอร์หลัก (Modules)

| โมดูล | รายละเอียด |
|---|---|
| **Authentication & RBAC** | เข้าสู่ระบบด้วย session cookie, throttle 5 ครั้ง/นาที, สิทธิ์แบบ Role-Based (4 บทบาท + สิทธิ์ย่อย 30+ รายการ), ระงับบัญชีอัตโนมัติ |
| **Dashboard** | KPI การ์ดสรุป, จำนวนพนักงานแยกตามแผนก, คำขอลาล่าสุด, ประกาศ, พนักงานเข้าใหม่ |
| **Employees** | ทะเบียนประวัติพนักงาน (CRUD), ค้นหา/กรองตามแผนก-สถานะ, แบ่งหน้า, หน้าโปรไฟล์พร้อมยอดวันลา |
| **Departments & Positions** | โครงสร้างองค์กร แผนก/ตำแหน่งงาน พร้อมหัวหน้าแผนกและลำดับขั้น |
| **Leave Management** | ประเภทการลา, ยื่นคำขอลา (ตัดวันทำงานอัตโนมัติ), สายอนุมัติ, ยอดวันลาคงเหลือ (entitled/used/pending) |
| **Attendance** | ลงเวลาเข้า–ออกงาน, ตรวจจับการมาสายตามเวลาทำงาน, ประวัติการลงเวลา |
| **Announcements** | ประกาศข่าวสารองค์กร พร้อมหมวดหมู่/ปักหมุด/สถานะเผยแพร่ |
| **Settings (Admin)** | จัดการผู้ใช้งานระบบ และบทบาท/สิทธิ์ (กำหนดสิทธิ์ราย permission) |
| **Reports & Audit** | รายงานสถิติกำลังพล/การลา/การลงเวลา และบันทึกการใช้งานระบบ (Audit Log) |
| **Notifications** | การแจ้งเตือนภายในระบบ (เช่น ผลการอนุมัติลา) |

ดูการจัดสเตจ MVP / Phase 2 แบบละเอียดได้ในส่วน *Feature Map* ด้านล่าง

---

## 🏗️ สถาปัตยกรรม & เหตุผลเลือก Stack

- **Laravel 11 + MySQL** — ตามที่ระบุ ไม่มีการแทนที่; ecosystem ครบ (Eloquent, Validation, Auth, Queue) ทีมไทยคุ้นเคย โฮสต์ได้ทุกที่ที่มี PHP 8.2+
- **Server-rendered Blade + Design System เขียนเอง** — ไม่มีขั้นตอน build (no Node/Vite required) ทำให้ติดตั้งและรันได้ทันที, bundle เล็ก, LCP เร็ว
- **Session cookie auth** (ไม่ใช่ JWT) — เหมาะกับเว็บแอปภายในองค์กรที่เรนเดอร์ฝั่งเซิร์ฟเวอร์ ปลอดภัยด้วย CSRF + HttpOnly cookie

### โครงสร้างโฟลเดอร์

```
HR PRO/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Dashboard, Employee, Department, Position,
│   │   │   ├── Auth/           #   LeaveRequest, LeaveApproval, Attendance,
│   │   │   └── Settings/       #   Announcement, Report, AuditLog, User, Role …
│   │   ├── Middleware/         # EnsureUserHasPermission, EnsureUserIsActive
│   │   └── Requests/           # Form Request validation (Thai messages)
│   ├── Models/                 # 13 Eloquent models
│   ├── Providers/              # AppServiceProvider, AuthServiceProvider (RBAC gate)
│   ├── Services/               # LeaveService, AttendanceService, DashboardService,
│   │                           #   NotificationService, AuditLogger  ← business logic
│   └── Support/helpers.php     # initials(), thb(), avatar_color()
├── bootstrap/ • config/ • public/{css,js}/   # framework + design system
├── database/
│   ├── migrations/             # 16 ordered migrations
│   ├── factories/ • seeders/   # demo data (RolePermissionSeeder, DemoDataSeeder)
├── resources/views/
│   ├── components/             # anonymous Blade UI kit (input, select, card, …)
│   ├── layouts/ • partials/    # app shell (sidebar/topbar) + guest layout
│   └── <module>/               # dashboard, employees, departments, … views
├── routes/web.php              # all routes (single source of truth)
└── tests/{Unit,Feature}/       # PHPUnit
```

### Data Model (สรุป)

| Entity | ฟิลด์สำคัญ | Constraints / Index | ความสัมพันธ์ |
|---|---|---|---|
| `users` | name, email*, password, is_active, last_login_at | unique(email), softDeletes | roles (M:N), employee (1:1), notifications |
| `roles` / `permissions` | slug*, name, is_system / name*, group | unique(slug), unique(name) | permission_role, role_user (pivot) |
| `employees` | employee_code*, first/last_name, email*, status, hire_date, base_salary | unique(code,email,user_id), idx(status,dept,position), softDeletes | department, position, manager(self), user |
| `departments` | name, code*, manager_id, parent_id | unique(code), softDeletes | employees, positions, parent/children |
| `positions` | title, code*, department_id, level | unique(code), softDeletes | department, employees |
| `leave_types` | name, code*, days_per_year, requires_approval, is_paid | unique(code) | leave_requests, balances |
| `leave_requests` | employee_id, leave_type_id, start/end_date, total_days, status, approver_id | idx(status, employee+status, start_date), softDeletes | employee, leaveType, approver |
| `leave_balances` | employee_id, leave_type_id, year, entitled/used/pending_days | unique(employee+type+year) | employee, leaveType |
| `attendances` | employee_id, work_date, clock_in/out, worked_minutes, status | unique(employee+work_date), idx(work_date) | employee |
| `announcements` | title, body, category, is_published, pinned, author_id | idx(published_at), softDeletes | author |
| `audit_logs` | user_id, action, auditable_type/id, description, ip | idx(action, auditable) | user |
| `notifications` | user_id, type, title, message, read_at | idx(user+read_at) | user |

*Soft-delete strategy*: ข้อมูลหลัก (users, employees, departments, positions, leave_requests, announcements)
ใช้ `softDeletes()` เพื่อกู้คืนได้และรักษา referential history; ตารางอ้างอิง (balances, attendances, pivots) ลบจริง

### API / Error envelope

แอปเป็นแบบเรนเดอร์ฝั่งเซิร์ฟเวอร์ (Blade) ใช้ RESTful resource routes:
ทรัพยากรเป็น plural kebab-case (`/employees`, `/leave-requests`), HTTP verbs มาตรฐาน,
สถานะ 200/201/302/403/404/422/500 ผ่าน Laravel เมื่อร้องขอแบบ JSON/XHR ระบบจะตอบ envelope รูปแบบเดียวกัน:

```json
{ "error": { "code": "VALIDATION_ERROR", "message": "…", "details": { } } }
```

(กำหนดใน `bootstrap/app.php` → `withExceptions`) ทุก route ภายใต้ `auth` + `active` middleware
เป็น **protected**; เฉพาะ `/login` เป็น **public**

---

## 🚀 การติดตั้ง & รัน (5 ขั้นตอน)

ต้องมี **PHP ≥ 8.2**, **Composer**, **MySQL ≥ 8** (หรือ MariaDB ≥ 10.6)

```bash
# 1) ติดตั้ง dependencies และเตรียมไฟล์ตั้งค่า
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate

# 2) สร้างฐานข้อมูลใน MySQL แล้วแก้ค่า DB_* ใน .env
#    CREATE DATABASE hr_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 3) สร้างตารางและใส่ข้อมูลตัวอย่าง (บัญชีทดลอง + พนักงาน 47 คน)
php artisan migrate --seed

# 4) ลิงก์ที่เก็บไฟล์แนบ (รูปพนักงาน / ไฟล์แนบใบลา)
php artisan storage:link

# 5) เปิดเซิร์ฟเวอร์
php artisan serve
```

เปิดเบราว์เซอร์ที่ **http://localhost:8000** แล้วเข้าสู่ระบบ:

| บทบาท | อีเมล | รหัสผ่าน |
|---|---|---|
| ผู้ดูแลระบบ (Super Admin) | `admin@hrpro.local` | `password` |
| ฝ่ายบุคคล (HR Manager) | `hr@hrpro.local` | `password` |
| หัวหน้างาน (Manager) | `manager@hrpro.local` | `password` |
| พนักงาน (Employee) | `employee@hrpro.local` | `password` |

---

## 🧪 การทดสอบ

ใช้ **PHPUnit** กับฐานข้อมูล SQLite in-memory (ตั้งค่าใน `phpunit.xml` — ไม่กระทบ MySQL จริง)

```bash
php artisan test
```

- **Unit** — `LeaveServiceTest` (คำนวณวันทำงาน, ตัด/คืนยอดวันลา, ตรวจการลาทับซ้อน, ลาเกินสิทธิ์, auto-approve),
  `AttendanceServiceTest` (มาตรงเวลา/มาสาย, ลงเวลาซ้ำ, คำนวณชั่วโมงทำงาน)
- **Feature** — `AuthTest` (เข้าสู่ระบบ/ออกจากระบบ), `LeaveRequestFlowTest` (e2e: เข้าสู่ระบบ → ยื่นใบลา → อนุมัติ → ยอดวันลาอัปเดต),
  `EmployeeManagementTest` (CRUD พนักงานพร้อมตรวจสิทธิ์)

---

## ⚙️ ประสิทธิภาพ (Performance budget)

- ทุก list ใช้ **pagination** (`config('hrpro.per_page')` = 15) — ไม่โหลดเกิน 50 แถว/หน้า
- Eager-loading ความสัมพันธ์เพื่อเลี่ยง N+1
- ดัชนีฐานข้อมูลครอบคลุมคอลัมน์ที่ใช้ค้นหา/กรองบ่อย
- ไม่มี JS framework — เพจหลัก JS < 5 KB, CSS เดียว ~1 file → LCP ต่ำ, p95 API < 300 ms ในงานทั่วไป

---

## ☁️ Deploy ฟรีจาก GitHub (Render) — เปิดเว็บได้ตลอด ไม่ต้องเปิดเครื่องเอง

> หมายเหตุ: GitHub Pages รันได้แค่ static (HTML/CSS/JS) — รัน PHP/Laravel ไม่ได้
> เราจึงเก็บโค้ดบน GitHub แล้วให้ **Render** (ฟรี ไม่ต้องใช้บัตรเครดิต) ดึงไป build + รันให้
> ระบบใช้ **Docker + SQLite** จึงไม่ต้องตั้งฐานข้อมูลแยก และ deploy อัตโนมัติทุกครั้งที่ push

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/Sanhaphoj/hr-pro)

**ขั้นตอน (ครั้งเดียว ~5 นาที):**
1. ไปที่ <https://render.com> → Sign up ด้วยบัญชี **GitHub** (ฟรี)
2. กด **New +** → **Blueprint** → เลือก repo `Sanhaphoj/hr-pro` (Render จะอ่าน [`render.yaml`](render.yaml) ให้อัตโนมัติ)
3. กด **Apply** → รอ build เสร็จ (~3–5 นาที) จะได้ URL เช่น `https://hr-pro.onrender.com`
4. (ถ้าต้องการ) ตั้ง `APP_URL` ใน Render = URL ที่ได้ เพื่อให้ลิงก์สมบูรณ์
5. เปิด URL → เข้าสู่ระบบด้วยบัญชีทดลอง (`admin@hrpro.local` / `password`)

ไฟล์ที่เกี่ยวข้อง: [`Dockerfile`](Dockerfile) · [`docker/entrypoint.sh`](docker/entrypoint.sh) · [`render.yaml`](render.yaml)
ข้อจำกัด Free tier: เซิร์ฟเวอร์จะ "หลับ" หลังไม่มีคนใช้ ~15 นาที และตื่นใหม่ ~30–60 วินาทีในคำขอแรก; ข้อมูลตัวอย่างจะถูก seed ใหม่เมื่อ redeploy (เหมาะกับเดโม)
*(ภาพ Docker นี้ใช้ได้กับ Koyeb / Fly.io / Railway เช่นกัน — ทุกที่ที่รัน container ได้)*

### Deploy แบบทั่วไป (เซิร์ฟเวอร์ของตนเอง / production จริง)

- **เป้าหมาย**: เซิร์ฟเวอร์ที่มี PHP 8.2+ (Nginx/Apache + PHP-FPM) หรือ Laravel Forge/Cloud
- **Build/optimize**: `composer install --no-dev --optimize-autoloader && php artisan config:cache route:cache view:cache`
- **Migration**: `php artisan migrate --force`
- **ENV ที่ต้องตั้ง (ตามลำดับ)**: `APP_KEY` → `APP_ENV=production` `APP_DEBUG=false` → `DB_*` → `SESSION_SECURE_COOKIE=true` (หลัง HTTPS) → `MAIL_*` (ถ้าใช้อีเมล)
- เปลี่ยน document root ไปที่โฟลเดอร์ `public/` (สำหรับ production จริงแนะนำ Nginx + PHP-FPM แทน `php artisan serve`)

---

## 🗺️ Feature Map (MVP / Phase 2)

- **[MVP]** Auth & RBAC, Employees, Departments, Positions, Leave (types/requests/approvals/balances),
  Attendance, Announcements, Dashboard, Settings (users/roles), Reports พื้นฐาน, Audit Log, Notifications
- **[Phase 2]** Payroll/เงินเดือน, Recruitment/สรรหา, Performance review, Training, Document management,
  Org-chart แบบกราฟิก, ส่งออก Excel/PDF, แจ้งเตือนทางอีเมล/LINE, Multi-language toggle, Self-service onboarding
