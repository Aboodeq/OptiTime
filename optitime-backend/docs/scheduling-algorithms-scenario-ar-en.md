# OptiTime — سيناريو تجربة الخوارزميات / Scheduling algorithms walkthrough

---

<br>

## المحتويات / Table of contents

1. [العربية — سيناريو كامل من البداية](#العربية--سيناريو-كامل-من-البداية)
2. [English — Full walkthrough from scratch](#english--full-walkthrough-from-scratch)

<br>

---

<br>

# العربية — سيناريو كامل من البداية

<br>

## 1) المرحلة 0 — تجهيز المشروع

1. من جذر المشروع نفّذ:

   `php artisan migrate:fresh --seed`

2. شغّل السيرفر:

   `php artisan serve`

3. كلمات المرور التجريبية في السيدر هي **`password`** (يظهر تنبيه في الطرفية عند التشغيل).

بعد السيدر تكون جاهزة تقريباً كل مدخلات الخوارزمية: فصل دراسي، عروض مقررات نشطة، أقسام مع محاضرين، قاعات، وإعدادات جدولة.

<br>

## 2) المرحلة 1 — جمع المعرّفات (UUID)

تحتاج على الأقل **`semester_id`**، ويُفضّل **`settings_id`** لإعدادات الجدولة من قاعدة البيانات.

**من الـ API (مثال):**

- `POST /api/auth/login`  
  جسم JSON مثلاً: `{ "email": "admin@optitime.local", "password": "password" }`  
  انسخ **`token`** من الرد.

- مع الهيدرات:  
  `Authorization: Bearer <token>`  
  `Accept: application/json`

- `GET /api/admin/semesters` → خذ **`id`** الفصل الدراسي.

- `GET /api/admin/schedule-settings` → خذ **`id`** أول سجل إعدادات.

**أو من Tinker:**

- `App\Models\Semester::first()->id`  
- `App\Models\ScheduleSetting::first()->id`

احفظ القيم كـ `SEMESTER_ID` و `SETTINGS_ID`.

<br>

## 3) المرحلة 2 — معاينة Backtracking (بدون حفظ في DB)

- المسار: **`POST /api/schedule/generate`**
- في بيئة **`local`** غالباً لا يُطلب توكن. في **الإنتاج** يلزم مستخدم بصلاحية **`schedule.generate`**.

**جسم JSON أدنى:**

```json
{
  "algorithm": "backtracking",
  "semester_id": "SEMESTER_ID"
}
```

**ماذا تراقب في الرد؟**

- `success: true` ومصفوفة **`sessions`** → تجربة ناجحة.
- `success: false` أو `422` → راجع العروض النشطة، الأقسام، المحاضرين، والقاعات لنفس الفصل.

**اختياري:** أرسل **`settings_id`** أو **`schedule_settings`** أو **`instructor_availabilities`** أو **`baseDraft`** أو **`rooms_override`** حسب الحاجة (قواعد التحقق في `GenerateScheduleRequest`).

<br>

## 4) المرحلة 3 — معاينة Genetic (بدون حفظ)

نفس المسار مع تغيير الخوارزمية وتثبيت عشوائي اختياري:

```json
{
  "algorithm": "genetic",
  "semester_id": "SEMESTER_ID",
  "seed": 42
}
```

راقب **`sessions`** و **`meta`** (مثل معلومات الأجيال للـ genetic).  
نفس **`seed`** يعيد نفس النتيجة تقريباً؛ تغيير **`seed`** يعطي حلولاً مختلفة للمقارنة.

<br>

## 5) المرحلة 4 — حفظ جدول منشور (الاستفادة الكاملة في النظام)

المعاينة لا تكتب `semester_schedule_plans` / `schedule_sessions`.  
النشر عبر:

**`POST /api/coordinator/schedules/publish-from-generation`**

1. سجّل دخول منسق، مثلاً:

   `coordinator@optitime.local` / `password`

2. استخدم **`Authorization: Bearer <token>`**.

3. مثال **Backtracking:**

```json
{
  "algorithm": "backtracking",
  "semester_id": "SEMESTER_ID",
  "settings_id": "SETTINGS_ID",
  "notes": "نشر تجريبي — backtracking"
}
```

4. مثال **Genetic:**

```json
{
  "algorithm": "genetic",
  "semester_id": "SEMESTER_ID",
  "settings_id": "SETTINGS_ID",
  "seed": 7,
  "notes": "نشر تجريبي — genetic"
}
```

رد ناجح غالباً **201** مع **`schedule_plan`** و **`sessions`** المخزّنة.

<br>

## 6) المرحلة 5 — التحقق من النتيجة في التطبيق

| الإجراء | المسار / الفكرة |
|--------|------------------|
| قائمة الخطط | `GET /api/coordinator/schedules` ويمكن `?semester_id=...` |
| تفاصيل خطة | `GET /api/coordinator/schedules/{plan_id}` |
| جدول المحاضر | تسجيل دخول محاضر → `GET /api/instructor/weekly-schedule?semester_id=...` |
| تقارير الإدارة | حساب بصلاحيات التقارير → مسارات `/api/management/reports/...` مع `semester_id` |

**ملاحظة جدول الطالب:**  
`GET /api/student/weekly-schedule` يعتمد على **`schedule_session_students`**. السيدر الافتراضي قد لا يملأ هذا الربط، فيظهر الجدول فارغاً للطالب حتى تُضاف البيانات. هذا لا يعني فشل الخوارزميتين.

<br>

## 7) المرحلة 6 — اختبارات آلية (اختياري)

```bash
php artisan test --filter=ScheduleGenerateTest
```

<br>

## 8) ملخص مسارات الخوارزميتين

| الهدف | المسار | الحقل الحاسم |
|--------|--------|----------------|
| معاينة فقط | `POST /api/schedule/generate` | `algorithm`: `backtracking` أو `genetic` |
| توليد + حفظ منشور | `POST /api/coordinator/schedules/publish-from-generation` | نفس الحقل |

<br>

---

<br>

# English — Full walkthrough from scratch

<br>

## Phase 0 — Project setup

1. From the project root, run:

   `php artisan migrate:fresh --seed`

2. Start the app server:

   `php artisan serve`

3. Demo accounts use the password **`password`** (the seeder prints a hint in the terminal).

After seeding you should have: an active **semester**, active **course offerings**, **sections** with **instructors**, **rooms**, and **schedule settings** (including related rows).

<br>

## Phase 1 — Collect UUIDs

You need at least **`semester_id`**. **`settings_id`** (a row in `schedule_settings`) is recommended so generation uses your DB configuration.

**Via API (example):**

- `POST /api/auth/login` with JSON body, e.g.  
  `{ "email": "admin@optitime.local", "password": "password" }`  
  Copy **`token`**.

- Headers:  
  `Authorization: Bearer <token>`  
  `Accept: application/json`

- `GET /api/admin/semesters` → copy semester **`id`**.

- `GET /api/admin/schedule-settings` → copy the first setting **`id`**.

**Or via Tinker:**

- `App\Models\Semester::first()->id`  
- `App\Models\ScheduleSetting::first()->id`

Store them as `SEMESTER_ID` and `SETTINGS_ID`.

<br>

## Phase 2 — Preview Backtracking (no DB write)

- Endpoint: **`POST /api/schedule/generate`**
- In **`local`**, the request usually works **without** a token. In **production**, a user with **`schedule.generate`** is required.

**Minimal JSON body:**

```json
{
  "algorithm": "backtracking",
  "semester_id": "SEMESTER_ID"
}
```

**What to check in the response:**

- `success: true` and a **`sessions`** array → OK.
- `success: false` or **422** → verify offerings, sections, instructors, and rooms for that semester.

**Optional:** send **`settings_id`**, **`schedule_settings`**, **`instructor_availabilities`**, **`baseDraft`**, or **`rooms_override`** (see `GenerateScheduleRequest` rules).

<br>

## Phase 3 — Preview Genetic (no DB write)

Same endpoint; change algorithm and optionally fix randomness:

```json
{
  "algorithm": "genetic",
  "semester_id": "SEMESTER_ID",
  "seed": 42
}
```

Inspect **`sessions`** and **`meta`** (e.g. generation stats for genetic).  
Same **`seed`** → reproducible runs; different **`seed`** → compare alternative solutions.

<br>

## Phase 4 — Persist a published timetable

Preview does **not** create `semester_schedule_plans` / `schedule_sessions`.  
Use:

**`POST /api/coordinator/schedules/publish-from-generation`**

1. Log in as a **coordinator**, e.g.  
   `coordinator@optitime.local` / `password`

2. Send **`Authorization: Bearer <token>`**.

3. **Backtracking** example:

```json
{
  "algorithm": "backtracking",
  "semester_id": "SEMESTER_ID",
  "settings_id": "SETTINGS_ID",
  "notes": "Demo publish — backtracking"
}
```

4. **Genetic** example:

```json
{
  "algorithm": "genetic",
  "semester_id": "SEMESTER_ID",
  "settings_id": "SETTINGS_ID",
  "seed": 7,
  "notes": "Demo publish — genetic"
}
```

A successful response is usually **201** with **`schedule_plan`** and stored **`sessions`**.

<br>

## Phase 5 — Verify in the app

| Goal | Endpoint / idea |
|------|------------------|
| List plans | `GET /api/coordinator/schedules` (optional `?semester_id=...`) |
| Plan detail | `GET /api/coordinator/schedules/{plan_id}` |
| Instructor timetable | Log in as instructor → `GET /api/instructor/weekly-schedule?semester_id=...` |
| Management reports | Account with report permissions → `/api/management/reports/...` with `semester_id` |

**Student timetable note:**  
`GET /api/student/weekly-schedule` depends on **`schedule_session_students`**. The default seeder may not create those links, so the student view can be empty until you add rows. That does **not** mean the algorithms failed.

<br>

## Phase 6 — Automated tests (optional)

```bash
php artisan test --filter=ScheduleGenerateTest
```

<br>

## Summary — Where both algorithms are used

| Goal | Endpoint | Key field |
|------|----------|-----------|
| Preview only | `POST /api/schedule/generate` | `algorithm`: `backtracking` or `genetic` |
| Generate + save as published | `POST /api/coordinator/schedules/publish-from-generation` | same field |

<br>

---

<br>

*Document generated for OptiTime. Update paths or credentials if your environment differs.*
