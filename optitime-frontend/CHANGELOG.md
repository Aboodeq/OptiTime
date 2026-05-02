# OptiTime Frontend - Changelog

## Overview

OptiTime Frontend is a bilingual (Arabic/English) single-page application (SPA) built with Vue 3 and Vite, providing a responsive web interface for academic timetable scheduling across multiple user roles (Admin, Coordinator, Instructor, Student, Management).

---

## Recent Changes (April 2026)

### Latest: Complete Frontend Rebuild (Commits: fe3a5db → b182321)
**What Changed:**
- Architectural refactor from monolithic structure to feature-based modular organization
- Auth flow cleaned up into isolated feature modules with dedicated stores
- Bilingual (AR/EN) i18n system fully implemented with locale file separation
- Bootstrap 5.3 integrated for consistent responsive UI components

**Why:**
- Improves maintainability: Each business domain (auth, schedules, users) owns its own API, store, and UI
- Enables parallel development: Teams can work on separate features without conflicts
- Scales for complexity: Adding "exams," "reports," or "analytics" modules follows established pattern
- Supports internationalization: Coordinators and instructors access system in their native language

---

## Major Features & Architecture

### 1. **Modular Feature-Based Structure**
**Organization Pattern:**
```
features/
├── auth/              # Login, token management, login guards
├── constraints/       # Schedule settings UI (study grid, constraints)
├── coordinator-schedule/  # Schedule board, session placement, generation trigger
├── instructor-preferences/ # Availability grid editor
├── lecture-requests/  # Request submission and review workflow
├── organization/      # Faculty, department, specialization CRUD
├── courses/           # Course catalog and offerings
├── users/             # User management (all roles)
├── instructors/       # Instructor profiles and management
├── students/          # Student enrollment and schedules
├── session-grades/    # Exam results tracking
└── [...other modules]

Each feature contains:
  api/        → Service methods (API calls to backend)
  model/      → TypeScript/Zod models (validation + types)
  ui/         → Vue components (views + dialogs)
  store.js    → Pinia state management (if stateful)
```

**Why:** Clear ownership; easy to add new features by copying pattern; reduces component naming conflicts.

### 2. **Bilingual Support (AR/EN)**
**Implementation:**
- `i18n/` folder with separated locale files for each language
- `vue-i18n` integration for runtime locale switching
- All UI strings externalized to locale files (no hardcoded text)
- Right-to-left (RTL) CSS handling for Arabic

**Features:**
- Locale toggle in sidebar
- Persistent locale selection in localStorage
- Fallback to English if translation missing
- Date/number formatting respects locale

**Why:** Academic institutions span Arabic and English-speaking regions; UI must reflect both languages seamlessly.

### 3. **Auth-First Architecture**
**Components:**
- Login form with role-based login options
- Token storage in Pinia store + localStorage (persistent across sessions)
- Router guards prevent unauthorized access
- Automatic token refresh on app load if valid

**Why:** Multi-user SPA; token in localStorage enables resumption; router guards prevent unauthorized route navigation.

### 4. **Role-Aware Navigation**
**Sidebar Structure:**
- Dynamically rendered based on authenticated user's role(s)
- Admin sees: Users, Roles, Audit Logs, System Settings
- Coordinator sees: Schedule Board, Course Management, Lecturer Request Review
- Instructor sees: Availability, Weekly Schedule, Lecturer Requests, PDF Export
- Student sees: Weekly Schedule, Grades, Exam Sessions
- Management sees: Reports, Analytics, Resource Utilization

**Why:** Reduces cognitive load; users only see actions relevant to their role.

### 5. **Responsive UI with Bootstrap 5.3**
**Features:**
- Mobile-first breakpoints (sm, md, lg, xl)
- Grid system for layout consistency
- Component library: buttons, forms, tables, modals, alerts
- Dark mode support (foundation in place)

**Why:** Instructors and students may access via tablets/phones; responsive ensures usability across devices.

### 6. **State Management with Pinia**
**Stores:**
- `auth.js` — Current user, token, roles, permissions
- `locale.js` — Active language (ar/en), UI language direction
- Optional feature-level stores for complex workflows (e.g., coordinator schedule board)

**Why:** Centralized state prevents prop-drilling; computed properties auto-update UI when role/locale changes.

### 7. **Input Validation with Zod**
**Pattern:**
```javascript
// models/lecturer-request.js
export const LecturerRequestSchema = z.object({
  courseId: z.number().min(1),
  instructorId: z.number().min(1),
  preferredDays: z.array(z.string()),
  specialRequirements: z.string().optional(),
});

// Usage in components
const validated = LecturerRequestSchema.parse(formData);
```

**Why:** Type-safe validation at form submission; catches bugs early; matches backend validation.

---

## Technology Stack

| Layer | Technology | Rationale |
|-------|-----------|-----------|
| **Framework** | Vue 3 (Composition API) | Reactive, intuitive; strong ecosystem; growing adoption |
| **Build** | Vite 8 | Fast HMR; instant dev startup; optimized production builds |
| **State** | Pinia | Simpler than Vuex; official Vue recommendation; TypeScript support |
| **i18n** | vue-i18n | Standard Vue i18n solution; supports pluralization, formatting |
| **Routing** | Vue Router 5 | Standard routing; lazy-loaded routes for code splitting |
| **Styling** | Bootstrap 5.3 + CSS | Pre-built components; consistent design; accessible components |
| **Validation** | Zod | Runtime validation; TypeScript inference; composable schemas |
| **HTTP** | Fetch API | Modern, native; no additional dependency; matches backend REST expectations |
| **Notifications** | vue-toastification | Non-blocking alerts; consistent UX; dismissable |

---

## Project Structure

```
src/
├── app/
│   ├── router.js              # Route definitions, guards, meta
│   ├── app.vue                # Root component (header, sidebar, router-view)
│   └── plugins/               # Vue plugins (i18n, pinia, router setup)
│
├── assets/
│   ├── css/
│   │   ├── global.css         # Bootstrap imports, global utilities
│   │   ├── variables.scss     # Color schemes, spacing, breakpoints
│   │   └── rtl.css            # Right-to-left overrides for Arabic
│   └── images/                # Icons, logos
│
├── components/
│   └── common/                # Shared UI components (button, loader, card, etc.)
│
├── constants/
│   └── storage.js             # localStorage keys for persistence
│
├── features/
│   ├── auth/
│   │   ├── api.js             # Login endpoint call
│   │   ├── model.js           # User schema (Zod)
│   │   ├── store.js           # Pinia auth store (token, user, roles)
│   │   └── ui/
│   │       ├── LoginPage.vue  # Login form view
│   │       └── [...other components]
│   │
│   ├── coordinator-schedule/
│   │   ├── api.js             # Schedule board, generation endpoints
│   │   ├── model.js           # Schedule, Session schemas
│   │   ├── store.js           # Shared state for board (if stateful)
│   │   └── ui/
│   │       ├── ScheduleBoard.vue
│   │       ├── SessionPlacementDialog.vue
│   │       └── [...]
│   │
│   ├── instructor-preferences/
│   ├── lecture-requests/
│   ├── organization/
│   ├── courses/
│   └── [... 8+ other feature modules following same pattern]
│
├── i18n/
│   ├── en.json                # English locale strings (all UI text)
│   ├── ar.json                # Arabic locale strings
│   └── messages.js            # i18n configuration, locale setup
│
├── store/
│   ├── auth.js                # Global auth store
│   ├── locale.js              # Global locale store
│   └── index.js               # Pinia setup
│
├── utils/
│   ├── api.js                 # Axios/Fetch wrapper with auth header injection
│   ├── formatters.js          # Date, number, locale-aware formatting
│   ├── validators.js          # Common validation helpers
│   └── guards.js              # Router guards (requireAuth, requireRole)
│
├── views/
│   ├── AdminLayout.vue        # Admin-only wrapper (with admin sidebar)
│   ├── CoordinatorLayout.vue  # Coordinator-only wrapper
│   ├── InstructorLayout.vue   # Instructor-only wrapper
│   └── [... role-specific layouts]
│
├── app.vue                    # Root component (header, navigation)
├── main.js                    # Vue app initialization
└── [...other configs]

public/
└── index.html                 # Entry point
```

---

## Getting Started

### Prerequisites
- Node.js 16+
- npm or yarn

### Installation
```bash
# Install dependencies
npm install

# Development server (HMR enabled)
npm run dev

# Build for production
npm run build

# Preview production build locally
npm run preview

# Lint code
npm run lint
```

### Demo Access
```
Email:    demo@optitime.com
Password: password123
Select a role via radio buttons on login page
```

---

## Usage Patterns

### Adding a New Role Module

1. **Create feature directory:**
   ```
   src/features/reports/
   ├── api.js          # API calls for reports
   ├── model.js        # Zod schemas for report data
   ├── store.js        # (optional) Pinia store if complex state
   └── ui/
       ├── ReportsPage.vue
       └── ReportDetailDialog.vue
   ```

2. **Define API methods** (api.js):
   ```javascript
   export async function fetchReports() {
     return fetch('/api/management/reports')
       .then(r => r.json());
   }
   ```

3. **Define models** (model.js):
   ```javascript
   export const ReportSchema = z.object({
     id: z.number(),
     title: z.string(),
     generatedAt: z.string().datetime(),
   });
   ```

4. **Build UI components** (ui/*.vue):
   - Use Composition API with `<script setup>`
   - Import store for auth + locale
   - Expose data and methods

5. **Register route** (app/router.js):
   ```javascript
   {
     path: '/reports',
     component: ReportsPage,
     meta: { requiresAuth: true, requiredRole: 'management' },
   }
   ```

6. **Add sidebar link** (if new role-specific menu):
   - Update management-layout sidebar in `views/ManagementLayout.vue`

### Internationalization (i18n)

**Adding a new string:**
1. Add to `i18n/en.json` and `i18n/ar.json`:
   ```json
   {
     "pages.reports.title": "Reports & Analytics"
   }
   ```

2. Use in component:
   ```vue
   <h1>{{ $t('pages.reports.title') }}</h1>
   ```

**Locale toggle:**
```javascript
// In any component
import { useLocaleStore } from '@/store/locale';
const locale = useLocaleStore();
locale.toggleLocale(); // Switches en ↔ ar
```

---

## Why These Design Choices?

| Choice | Reason |
|--------|--------|
| **Vue 3 Composition API** | More flexible for complex components; better TypeScript support than Options API |
| **Feature-based organization** | Scales: easy to add/remove features; reduces naming conflicts; clear ownership |
| **Bilingual from start** | Backend already supports AR/EN; UI must match; easy to maintain separate locale files |
| **Pinia over Vuex** | Simpler API; Vue 3 official recommendation; less boilerplate for auth + global state |
| **Zod for validation** | Runtime safety; TypeScript inference; matches backend validation approach |
| **Bootstrap 5.3** | Industry standard; accessible components; large community; responsive grid system |
| **Vite over Webpack** | Faster dev startup; HMR instant; modern build tool; better code splitting |

---

## Known Limitations & Future Work

- **Offline support:** Currently online-only; could add Service Worker for offline schedule viewing
- **Real-time updates:** Stateless SPA; could add WebSocket notifications for multi-coordinator workflows
- **Advanced scheduling UI:** Drag-drop schedule board exists; could add more visual feedback
- **Mobile app:** Currently web-responsive; React Native / Flutter app could improve mobile UX
- **Analytics dashboard:** Management reports basic; could add charts, KPIs, resource utilization

---

## Contributing

When adding new features:
1. Create feature module following the pattern above
2. Keep models in `model.js`, API in `api.js`, UI in `ui/` subdirectory
3. Add all strings to `i18n/{en,ar}.json`
4. Register routes with role guards in `app/router.js`
5. Update main layout's sidebar based on role

---

## License

MIT License
