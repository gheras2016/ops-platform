# OPS Platform — التوثيق التقني للنظام

> منصّة إدارة عمليات الصيانة (CMMS) متعددة الشركات — مرجع تقني شامل للتطوير والصيانة المستقبلية.

آخر تحديث: 2026-06-17

---

## جدول المحتويات
1. [نظرة عامة والحزمة التقنية](#1-نظرة-عامة-والحزمة-التقنية)
2. [هيكل النظام (Architecture)](#2-هيكل-النظام-architecture)
3. [الوحدات (Modules)](#3-الوحدات-modules)
4. [تدفق العمل (Workflow)](#4-تدفق-العمل-workflow)
5. [نظام الصلاحيات والأدوار](#5-نظام-الصلاحيات-والأدوار)
6. [قاعدة البيانات (مبسّطة)](#6-قاعدة-البيانات-مبسطة)
7. [تعدد الشركات (Multi-Tenant)](#7-تعدد-الشركات-multi-tenant)
8. [الربط بين الويب والـ API](#8-الربط-بين-الويب-والـ-api)
9. [خريطة الملفات الأساسية](#9-خريطة-الملفات-الأساسية)
10. [التشغيل والأوامر](#10-التشغيل-والأوامر)
11. [حسابات تجريبية](#11-حسابات-تجريبية)

---

## 1. نظرة عامة والحزمة التقنية

نظام ويب لإدارة بلاغات وأوامر الصيانة داخل المنشآت، مبني ليخدم **عدّة شركات على نظام واحد** مع عزل كامل للبيانات. الواجهة عربية (RTL).

| المكوّن | التقنية |
|--------|---------|
| الإطار | Laravel 10 (PHP 8.1+) |
| قاعدة البيانات | MySQL |
| الواجهة | Blade (Server-Side Rendering) + CSS مخصّص (متغيرات CSS) + Font Awesome + خط Tajawal |
| الصلاحيات | `spatie/laravel-permission` v6 |
| تقارير PDF (عربي) | `mpdf/mpdf` v8 |
| تصدير Excel/CSV | `maatwebsite/excel` v3 |
| الـ API / التوكنات | `laravel/sanctum` v3 (مهيّأ، يُستخدم مستقبلاً) |
| الإشعارات | Laravel Database Notifications |

أرقام مرجعية: **27 موديل**، **19 كنترولر**، **5 ملفات هجرة (migrations)**.

---

## 2. هيكل النظام (Architecture)

نمط **MVC طبقي** مع طبقة خدمات (Services) تحتوي منطق الأعمال:

```
┌─────────────────────────────────────────────────────────┐
│                    المتصفّح (RTL)                         │
└───────────────────────────┬─────────────────────────────┘
                            │ HTTP
┌───────────────────────────▼─────────────────────────────┐
│  Routes (web.php)  ──►  Middleware (auth, active, can)   │
└───────────────────────────┬─────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────┐
│  Controllers  ──►  Form Requests (تحقّق)  ──►  Policies  │
└───────────────────────────┬─────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────┐
│  Services (منطق الأعمال — مصدر الحقيقة الوحيد)            │
│  • TicketWorkflowService    • ProcurementService         │
│  • PartRequestWorkflowService                            │
└───────────────────────────┬─────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────┐
│  Models (Eloquent) + BelongsToCompany (عزل تلقائي)       │
└───────────────────────────┬─────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────┐
│                     MySQL (قاعدة مشتركة)                  │
└─────────────────────────────────────────────────────────┘
```

**المبادئ الأساسية:**
- **الكنترولر نحيف**: يتحقّق من الصلاحية (Policy)، ويفوّض منطق العمل للـ Service.
- **الـ Service هو مصدر الحقيقة** لكل انتقالات الحالة — لا يغيّر الكنترولر حالة التذكرة مباشرة.
- **العزل في طبقة البيانات** عبر Global Scope (انظر القسم 7).
- **كل انتقال يُسجَّل** في سجل زمني (Timeline) قابل للتدقيق.

---

## 3. الوحدات (Modules)

| الوحدة | الوصف | الكنترولر الرئيسي |
|--------|-------|------------------|
| **التذاكر / البلاغات** | جوهر النظام: دورة حياة البلاغ من الفتح للإغلاق | `TicketController`, `TicketActionController` |
| **الأقسام** | أقسام الصيانة، رؤساؤها، التسلسل الهرمي | `DepartmentController` |
| **المستخدمون** | إدارة المستخدمين والأدوار داخل الشركة | `UserController` |
| **المواقع** | شجرة المواقع (مبنى ← دور ← غرفة) | `LocationController` |
| **المخزون** | الأصناف والفئات وحركات المخزون | `ItemController`, `CategoryController`, `StockTransactionController` |
| **قطع الغيار (Spare Parts)** | كتالوج قطع الغيار وتصنيفاتها | `SparePartController`, `SpareCategoryController` |
| **طلبات صرف الإسبير** | طلب الفني لقطع غيار من المخزون | `PartRequestController` |
| **طلبات الشراء** | سلسلة اعتماد الشراء (إدارة ← مالية ← تنفيذ) | `PurchaseRequestController` |
| **التقارير والتحليلات** | مؤشرات الأداء + تصدير Excel/CSV/PDF | `ReportController` |
| **الشركات (المنصة)** | إدارة الشركات (للسوبر أدمن) | `CompanyController` |
| **الهوية البصرية** | ألوان وشعار كل شركة | `SettingsController` |
| **الإشعارات** | إشعارات داخل النظام | `NotificationController` |
| **لوحة التحكم** | مؤشرات حسب الدور | `DashboardController` |

---

## 4. تدفق العمل (Workflow)

### دورة حياة التذكرة (آلة حالات)

```
open ─► assigned ─► accepted ─► in_progress ─┬─► resolved ─► closed
                                  ▲    │       │            (اعتماد الرئيس)
                          resume  │    ▼ pause └─► (رفض) ─► in_progress
                                  └─ paused
              أي حالة ─► cancelled
```

| الحالة | المعنى | من ينفّذ |
|--------|--------|----------|
| `open` | بلاغ جديد | المبلّغ ينشئه |
| `assigned` | أُسند لفني (يُحدَّد الموعد والأولوية هنا) | رئيس القسم |
| `accepted` | الفني قبل المهمة | الفني |
| `in_progress` | العمل جارٍ (نسبة إنجاز 0–100) | الفني |
| `paused` | متوقّف مؤقتاً مع **سبب مُسجَّل** | الفني |
| `resolved` | تم الإنجاز، بانتظار الاعتماد | الفني |
| `closed` | معتمد ومغلق | رئيس القسم |
| `cancelled` | ملغى | المبلّغ/الأدمن |

**ملاحظات مهمة:**
- **الموعد المتوقع للإنجاز (`due_at`)** يحدّده رئيس القسم عند الإسناد — لا المبلّغ.
- كل انتقال يكتب صفّاً في `ticket_events` (سجل زمني) ويُرسل إشعاراً.
- الإيقاف يكتب أيضاً في `ticket_pause_logs` (سبب + من أوقف + متى استُؤنف).
- عند الإنهاء يمكن للفني تسجيل **قطع الغيار المستخدمة** (من الكتالوج: تُخصم من المخزون / خارج الكتالوج: تُسجَّل بالاسم فقط).

### تدفق قطع الغيار والشراء

```
الفني يطلب قطعة (من التذكرة)
        │
        ▼
رئيس القسم يعتمد طلب الصرف
        │
        ├─ متوفرة بالمخزون ──► المخزون يصرفها ──► تُخصم + تُسجَّل على التذكرة
        │
        └─ غير متوفرة / خارج الكتالوج ──► توريد تلقائي (PurchaseRequest)
                                            │
                                            ▼
                                  سلسلة اعتماد الشراء:
                                  رئيس القسم ► مدير التشغيل ► المالية
                                            │
                                            ▼
                                  المخزون يستلم ► يُنشأ صنف بالكتالوج ► يُصرف
```

سلسلة اعتماد الشراء **ذكية**: تصعد في شجرة الأقسام وتتخطّى المستويات التي لا رئيس لها أو التي يرأسها مُقدِّم الطلب نفسه، ثم تصل للمالية فالتنفيذ.

---

## 5. نظام الصلاحيات والأدوار

يعتمد على `spatie/laravel-permission`. **سبعة أدوار:**

| الدور | الاسم المعروض | الصلاحية |
|------|----------------|----------|
| `super_admin` | مدير المنصة | يدير كل الشركات، يتجاوز كل القيود |
| `company_admin` | مدير النظام | يدير شركته بالكامل |
| `department_head` | رئيس قسم | يستقبل بلاغات قسمه، يُسند، يعتمد الإنجاز |
| `technician` | فني | ينفّذ المهام المسندة إليه |
| `requester` | مبلّغ | يرفع البلاغات ويتابعها |
| `warehouse_manager` | مدير المخزون | يدير المخزون وقطع الغيار |
| `finance_manager` | مدير المالية | يعتمد المشتريات |

### البوّابات (Gates)
معرّفة في `AuthServiceProvider`:

| البوّابة | من يملكها |
|---------|-----------|
| `admin-access` | أدمن (شركة أو منصة) |
| `platform-access` | السوبر أدمن فقط |
| `view-reports` | أدمن أو رئيس قسم |
| `inventory-access` | أدمن أو مدير المخزون |
| `finance-access` | أدمن أو مدير المالية |

**`Gate::before`**: السوبر أدمن يتجاوز كل الفحوصات تلقائياً.

### السياسات (Policies)
- `TicketPolicy` — مثلاً: الفني المُسنَد فقط يبدأ/يوقف/يحل؛ رئيس القسم (أو الأدمن) فقط يُسند/يعتمد؛ المبلّغ يرى بلاغاته فقط.
- `PartRequestPolicy`, `PurchaseRequestPolicy`.

---

## 6. قاعدة البيانات (مبسّطة)

قاعدة واحدة مشتركة. الجداول الرئيسية (يحمل أغلبها `company_id`):

```
companies (جذر التعدد)
  ├── users (company_id, department_id, role عبر spatie)
  ├── departments (company_id, head_id, parent_id, type, accepts_tickets)
  ├── locations (شجرة: building/floor/room)
  ├── priorities ★ (مشترك عام — بلا company_id)
  ├── ticket_statuses ★ (مشترك عام)
  │
  ├── tickets (company_id, ticket_number, status, progress, due_at,
  │   │        created_by, assigned_to, department_id, priority_id, location_id)
  │   ├── ticket_events (سجل زمني: type, from_status, to_status, note, meta)
  │   ├── ticket_pause_logs (reason_code, reason, paused_at, resumed_at)
  │   ├── ticket_comments (تعليقات المتابعة)
  │   ├── ticket_attachments (مرفقات)
  │   └── ticket_spare_parts (spare_part_id؟, custom_name؟, quantity_used, unit_cost)
  │
  ├── spare_categories / spare_parts (كتالوج قطع الغيار)
  ├── categories / items (المخزون العام)
  ├── stock_transactions (in/out + ربط بالتذكرة)
  ├── asset_categories / assets (الأصول)
  │
  ├── part_requests (طلب صرف من تذكرة)
  │   └── part_request_items (spare_part_id؟, custom_name؟, qty_requested/approved/issued)
  │
  ├── purchase_requests (company_id, status, current_dept_id, part_request_id؟)
  │   ├── purchase_approvals (stage: dept/finance, decision)
  │   └── purchase_request_items
  ├── purchase_orders / purchase_order_items
  └── audit_logs
```

★ = جداول مرجعية مشتركة بين كل الشركات (غير معزولة).

**ملاحظة عزل**: الجداول الفرعية (مثل `ticket_events`) لا تحمل `company_id` مباشرة، بل تُعزَل **عبر الأب** (التذكرة). آمنة لأن الوصول يتم دائماً عبر الأب.

---

## 7. تعدد الشركات (Multi-Tenant)

**النموذج**: قاعدة بيانات مشتركة + عزل بعمود `company_id` (Shared Database, Shared Schema).

### آلية العزل التلقائي
1. **`App\Models\Concerns\BelongsToCompany`** (trait على 13 موديلاً):
   - **عند القراءة**: يضيف `CompanyScope` (Global Scope) فيُفلتر كل استعلام لشركة المستخدم.
   - **عند الإنشاء**: يختم السجل بـ `company_id` تلقائياً.
2. **`App\Models\Scopes\CompanyScope`**: `WHERE company_id = <شركة المستخدم>`. يُتجاوز إذا لم يوجد مستخدم (CLI/Seeders) أو إذا كان `company_id = null` (السوبر أدمن).
3. **موديل `User` بلا Global Scope** (تجنّباً لتكرار حلّ المصادقة) — يُستخدم بدلاً منه `User::tenantScoped($actor)`.
4. **السوبر أدمن** (`company_id = null`) يرى كل الشركات.

### توفير الشركات (Provisioning)
- `CompanyController::store` ينشئ **الشركة + أول مدير لها** (`company_admin`) في معاملة واحدة.
- `is_active` على الشركة والمستخدم: مربوط بالدخول (`LoginController`) وبكل طلب (`EnsureActiveAccount` middleware) — إيقاف فوري.

### تصدير بيانات الشركة
- `App\Support\TenantExporter` + الأمر `php artisan tenant:export {id|code}` → ملف ZIP بكل بيانات الشركة (JSON لكل جدول، بدون كلمات المرور). يُستخدم عند إنهاء الاشتراك.

---

## 8. الربط بين الويب والـ API

النظام حالياً **تطبيق ويب يُصيّر من الخادم (Server-Rendered Blade)** — لا توجد طبقة Frontend منفصلة (React/Vue).

| الطبقة | الملف | الوصف |
|-------|------|-------|
| مسارات الويب | `routes/web.php` | كل المسارات، محميّة بـ `['auth', 'active']` + بوّابات `can:` |
| مسارات الـ API | `routes/api.php` | **مهيّأة فقط**: نقطة `/api/user` عبر `auth:sanctum` |

**جاهزية الـ API المستقبلي:**
- `laravel/sanctum` مثبّت وجاهز لإصدار توكنات (للموبايل أو تكامل خارجي).
- لإضافة API: تُعرّف نقاط في `routes/api.php` تحت `auth:sanctum`، ويُعاد استخدام نفس الـ **Services** و**Policies** (منطق الأعمال مفصول عن الويب) — لا حاجة لتكرار المنطق.
- يُنصح بإضافة `App\Http\Resources` (API Resources) لتنسيق المخرجات JSON.

```
الويب (Blade)  ─┐
                ├─► نفس Services + Policies + Models ─► قاعدة البيانات
API (Sanctum) ─┘   (المنطق مشترك، لا تكرار)
```

---

## 9. خريطة الملفات الأساسية

| المسار | الدور |
|--------|------|
| `app/Services/TicketWorkflowService.php` | كل انتقالات حالة التذكرة |
| `app/Services/ProcurementService.php` | سلسلة اعتماد الشراء + التنفيذ |
| `app/Services/PartRequestWorkflowService.php` | دورة طلب صرف الإسبير |
| `app/Models/Concerns/BelongsToCompany.php` | عزل التعدد (trait) |
| `app/Models/Scopes/CompanyScope.php` | الـ Global Scope |
| `app/Providers/AuthServiceProvider.php` | البوّابات + ربط السياسات |
| `app/Http/Middleware/EnsureActiveAccount.php` | منع الحسابات/الشركات الموقوفة |
| `app/Support/TenantExporter.php` | تصدير بيانات شركة |
| `app/Support/Theme.php` | حساب متغيرات الألوان لكل شركة |
| `database/migrations/2026_01_01_000100_create_operations_schema.php` | الجداول التشغيلية |
| `database/seeders/MasterSeeder.php` | البيانات التجريبية |
| `resources/views/layouts/app.blade.php` | القالب الرئيسي + حقن الثيم |

---

## 10. التشغيل والأوامر

```bash
# التثبيت
composer install
cp .env.example .env          # اضبط DB_* و APP_URL
php artisan key:generate
php artisan migrate:fresh --seed   # ⚠️ يمسح البيانات ويعيد البذور
php artisan storage:link

# التشغيل
php artisan serve              # http://127.0.0.1:8000

# الاختبارات
php artisan test               # 43 اختباراً

# تصدير بيانات شركة
php artisan tenant:export RAWAD
```

> ملاحظة إنتاج: اضبط `APP_ENV=production` و`APP_DEBUG=false`، وHTTPS، وSMTP حقيقي، وطابق `APP_URL` مع طريقة الوصول.

---

## 11. حسابات تجريبية

كلمة المرور للجميع: `password`

| الدور | البريد |
|------|--------|
| مدير المنصة (Super Admin) | `super@ops.test` |
| مدير شركة الرواد | `admin@rawad.test` |
| رئيس قسم (مثال) | `it.head@rawad.test` |
| فني (مثال) | `it.tech1@rawad.test` |
| مدير المخزون | `warehouse@rawad.test` |
| مدير المالية | `finance@rawad.test` |
| مبلّغ | `user1@rawad.test` |
| شركة ثانية (لإثبات العزل) | `admin@gulf.test` |

> ⚠️ كلمات المرور التجريبية ضعيفة — غيّرها قبل أي تشغيل حقيقي.
