# OptiTime Backend - Changelog

## Overview

OptiTime Backend is a Laravel-based REST API for academic timetable scheduling with intelligent algorithm-driven schedule generation, multi-role access control, and comprehensive academic resource management.

---

## Recent Changes (April 2026)

### Latest: Scheduling Algorithm Documentation (Commit: b182321)
**What Changed:**
- Added comprehensive bilingual (Arabic/English) documentation: `docs/scheduling-algorithms-scenario-ar-en.md`
- Includes step-by-step testing scenarios for both Backtracking and Genetic Algorithm implementations
- Demo credentials and seeding instructions provided

**Why:**
- Provides operators and developers with concrete examples of how the scheduling system works
- Enables non-technical coordinators to understand algorithmic behavior and tune parameters
- Documents the complete workflow from course setup through schedule generation and export

---

## Major Features & Architecture

### 1. **Dual Scheduling Algorithms**
**What:** Backend supports two complementary scheduling engines:
- **Backtracking Algorithm** — Depth-first exhaustive search with feasibility focus
  - Max iterations: 100,000 | Timeout: 25 seconds
  - Best for: Small-to-medium schedules with strict constraint satisfaction
- **Genetic Algorithm** — Population-based heuristic with crossover/mutation
  - Population: 80 | Generations: 200 | Timeout: 30 seconds
  - Best for: Large, complex schedules requiring optimization trade-offs

**Why:** Allows coordinators to choose based on schedule size and constraint complexity. Backtracking guarantees feasibility; Genetic provides better solutions for larger problems.

### 2. **Settings-Driven Configuration**
**What:** All scheduling behavior configured via `ScheduleSettings` entity:
- Study grid layout (days, time slots, break management)
- Hard constraints (e.g., no instructor double-booking)
- Soft constraints (e.g., minimize gaps, prefer morning sessions)
- Room capacity constraints and resource requirements

**Why:** Non-technical coordinators can adjust solver behavior via UI without code changes. Constraints stored in database for audit trail and version history.

### 3. **Multi-Role Access Control (RBAC)**
**Roles:**
- **Admin** — Full system access, user management, role assignments, audit logs
- **Coordinator** — Schedule board management, course setup, lecturer request review, algorithm parameter tuning
- **Instructor** — Availability management, lecture requests, view own schedule, export for distribution
- **Student** — View own schedule, grades, exam sessions
- **Management** — Reporting and analytics dashboards

**Why:** Enables safe delegation; each role sees only relevant data. Audit trail tracks all modifications for compliance.

### 4. **Academic Resource Management**
**Core Entities:**
- Faculties, Departments, Specializations (organizational hierarchy)
- Courses, Course Offerings, Course Sections (curriculum structure)
- Instructors with availability profiles (human resource scheduling)
- Rooms with capacity and resource constraints (physical infrastructure)
- Semesters and schedules (temporal organization)

**Why:** Complete data model supports real-world academic operations from enrollment through certification.

### 5. **Workflow: Lecture Requests → Schedule Review → Generation**
**What:** Structured process for schedule coordination:
1. Instructors submit lecture requests (preferred times, special requirements)
2. Coordinators review, approve, or modify requests
3. Coordinator triggers algorithm with final settings
4. System generates optimized schedule
5. Instructors and students export/view final schedule as PDF

**Why:** Prevents ad-hoc scheduling chaos; creates audit trail and ensures stakeholder input before algorithm execution.

### 6. **Export & Reporting**
**Features:**
- Generate weekly schedule PDFs for instructors and students
- Grade-session exports for management
- Audit logs with full change history

**Why:** Supports both operational distribution (instructors need printable schedules) and compliance (management audit requirements).

---

## Technology Stack

| Layer | Technology | Rationale |
|-------|-----------|-----------|
| **Framework** | Laravel 10 + PHP 8.1+ | Enterprise PHP framework; expressive syntax; mature ecosystem |
| **Authentication** | Laravel Sanctum | API token-based; no session state; mobile-friendly |
| **ORM** | Eloquent | Express, maintain, query all relationships; migration support |
| **Database** | MySQL/PostgreSQL | Industry standard; referential integrity for academic hierarchy |
| **Export** | DOMPDF | HTML → PDF without external services; easy to template |
| **HTTP Client** | Guzzle | Async integrations if future external systems needed |
| **Testing** | PHPUnit + Mockery | Built-in Laravel testing; mock third-party dependencies |

---

## Project Structure

```
app/
├── Console/              # Scheduled tasks, batch operations
├── Exceptions/           # Custom exception handlers
├── Http/
│   ├── Controllers/      # Route handlers (Admin, Coordinator, Instructor, Student modules)
│   ├── Middleware/       # Request validation, permission checks, CORS
│   └── Requests/         # Form request validation rules
├── Models/               # 30+ Eloquent models (User, Course, Schedule, etc.)
├── Providers/            # Service registration and bootstrapping
├── Scheduling/           # Cron job definitions
└── Services/
    ├── ScheduleGenerateService.php   # Orchestrates algorithm selection, constraint setup
    ├── BacktrackingScheduler.php      # Depth-first solver
    ├── GeneticScheduler.php           # Population-based solver
    └── *                              # Business logic services

config/
├── optitime.php          # Scheduler timeouts, iteration limits, defaults
├── app.php, auth.php, database.php, ...

routes/
├── api.php               # RESTful endpoints: /api/admin/*, /api/coordinator/*, etc.
├── web.php               # Legacy web routes (if any)

database/
├── migrations/           # Schema versioning
├── seeders/              # Sample data and reference tables
└── factories/            # Fixture generation for tests

tests/
├── Feature/              # Integration tests (controller → database)
├── Unit/                 # Algorithm and service unit tests

docs/
└── scheduling-algorithms-scenario-ar-en.md   # User-facing workflow documentation
```

---

## Getting Started

### Prerequisites
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Node.js (for frontend asset builds)

### Installation
```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed initial data (roles, permissions, settings)
php artisan db:seed

# Run tests
php artisan test
```

### Running
```bash
# Development server
php artisan serve

# Production: Use Apache/Nginx with `public/index.php` as document root
```

---

## API Overview

### Authentication
```bash
POST /api/login
Body: { "email": "demo@optitime.com", "password": "password123" }
Response: { "token": "..." }
```

### Coordinator Schedule Board
```bash
GET /api/coordinator/schedule-board/{semesterId}
POST /api/coordinator/generate-schedule
  Body: { 
    "semesterId": 1, 
    "algorithm": "backtracking", | "genetic"
    "algorithm_params": { "timeout": 25, "max_iterations": 100000 }
  }
```

### Instructor Schedule
```bash
GET /api/instructor/schedule/{semesterId}
POST /api/instructor/export-pdf/{semesterId}
```

### Availability Management
```bash
GET /api/instructor/availability-profile
PUT /api/instructor/availability-profile
  Body: { "grid": [...cells marked as available/unavailable...] }
```

---

## Why These Design Choices?

| Choice | Reason |
|--------|--------|
| **Dual algorithms** | Different problems need different solutions; settings let operators choose |
| **Settings-driven** | Academic coordinators should tune solver behavior via UI, not code |
| **Multi-role RBAC** | Prevents unauthorized access; each role autonomously operates in their scope |
| **PDF export** | Instructors and students need offline access; compliance requires audit trail |
| **Lecture request workflow** | Captures stakeholder input before auto-scheduling; prevents surprises |
| **Bilingual docs** | Serves Arabic/English-speaking academic institutions |

---

## Known Limitations & Future Work

- **Async Processing:** Currently synchronous; consider Laravel Queue for large schedules (>5000 sessions)
- **Conflict Reporting:** Algorithm doesn't yet expose why constraints conflict; useful for coordinator debugging
- **Published Schedule Persistence:** Need version history for schedule archives
- **Advanced Reporting:** Basic exports exist; could expand to utilization reports, resource contention analysis

---

## Contributing

When adding new features:
1. Update `ScheduleSettings` if new parameter needed
2. Add corresponding service method
3. Add bilingual documentation to `docs/` folder
4. Include algorithm behavior notes for coordinators

---

## License

MIT License
