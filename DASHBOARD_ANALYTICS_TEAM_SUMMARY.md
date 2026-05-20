# Gravity CBC Analytics — Team Summary (Updated)

**Last updated:** May 2026  
**Audience:** Product, operations, school partners, and engineering  
**Scope:** Filament admin panel, mobile/API clients, and role-based analytics

This document describes analytics on the Gravity CBC platform for **Gravity CBC administrators**, **schools (institutions)**, **teachers**, **learners**, and **per-assessment** reporting. It supersedes the earlier team summary and reflects the full dashboard specification plus extended CBC, gender equity, and operations metrics.

---

## How scores and levels work

Every completed assessment is scored as a **percentage of total marks**. That percentage maps to Kenya **CBE competency descriptors**:

| Level | Range | Meaning |
|-------|--------|---------|
| **BE** — Below Expectation | &lt; 50% | Needs significant support |
| **AE** — Approaching Expectation | 50–70% | Developing; more practice needed |
| **ME** — Meeting Expectation | 71–85% | On track |
| **EE** — Exceeding Expectation | &gt; 85% | Strong mastery |

**Competency areas** (strengths and gaps) come from **category tags** on marked questions. **Gender & inclusion** metrics segment the learner roster and outcomes by recorded gender (with a “not recorded” bucket where data is missing).

---

## Where analytics live

| Channel | Who uses it |
|---------|-------------|
| **Filament admin** (`/`) | Platform admins (Gravity CBC); institution admins (their school only) |
| **School analytics page** | Platform admins — drill into any registered school |
| **REST API** | Student app, teacher app, institution integrations |

**Core services:** `DashboardAnalyticsService` (deep analytics), `DashboardDataService` (dashboard/login/register payload). Analytics are **cached for 5 minutes** per scope for performance.

---

## 1. Gravity Master Dashboard (system overview)

**Who:** Platform administrators (Gravity CBC)  
**Filament:** Main dashboard + **School analytics** navigation item  
**API:** `GET /api/dashboard` and `GET /api/dashboard/analytics` (admin role)

### Must display (tables + stats)

| Metric | Available |
|--------|-----------|
| Total users (learners, teachers, admins, parents, institutions) | Yes |
| Total schools / institutions | Yes |
| Total assessments created | Yes |
| Total assessments completed | Yes |
| Total tokens purchased | Yes |
| Total tokens used | Yes |
| M-PESA payment success count | Yes |
| M-PESA payment failure count | Yes |
| System status (online / offline) | Yes (database connectivity check) |

### Minimum analytics (charts)

| Chart | Available |
|-------|-----------|
| Line: user growth over time (6 months) | Yes |
| Bar: assessments completed per period (14-day activity) | Yes |
| Pie: tokens purchased vs tokens used | Yes |

### Extended platform analytics (beyond minimum spec — retained)

- **Platform overview** — students, schools, platform average CBE, learners trending up, guardian email coverage, gender data coverage, revenue (KES), teachers/parents
- **System operations** — dedicated stat row for tokens, M-PESA, assessments, system health
- **Users by role** (doughnut) and **student type** (institution vs individual)
- **CBE competency distribution** (BE/AE/ME/EE) platform-wide
- **Performance by subject** and **by competency area (category tags)**
- **School comparison** — all schools + individual learners (table + bar chart)
- **Classroom comparison** — all classrooms across all schools (table + bar chart)
- **Assessment usage by school** — per-school completed attempts and distinct assessments used
- **Platform competency strengths / areas needing focus** — top and bottom category tags (tables)
- **Top performers** and **students needing support** (platform-wide tables)
- **Gender & inclusion** — reporting coverage stats, roster by gender, outcomes by gender (tables)
- **Recent attempts** — cross-platform activity feed
- **Recommended platform actions** — data-driven action items
- **Revenue by month** (where enabled)
- **Grade level distribution**

### Admin drill-down: School analytics page

Admins can select **any school** and see the **same depth as an institution admin**: overview, gender & inclusion, classrooms, competency areas, full learner roster with status, assessment usage, and recommended actions.

**API:** `GET /api/dashboard/institutions/{institutionId}`

---

## 2. Learner Dashboard

**Who:** Students (including individual learners without a school)  
**API:** `GET /api/dashboard`, `GET /api/dashboard/analytics`, `GET /api/token-balance`

### Must display

| Item | Available |
|------|-----------|
| Token balance | Yes (dashboard payload + analytics overview) |
| Assessment history (name, score %, date taken) | Yes (`assessment_history`) |
| Total assessments taken | Yes |

### Minimum analytics

| Item | Available |
|------|-----------|
| Line chart: score trend (last 5–10 assessments) | Yes (`charts.score_trend_last_10`) |
| Progress counter (assessments completed) | Yes (`progress_counter`) |

### Extended learner analytics (retained)

- CBE level, improvement trend, distinct assessments, last activity
- **Strengths** and **areas for improvement** by competency tag
- Subject and category breakdowns and bar charts
- 14-day activity chart, full performance-over-time chart
- CBE competency distribution (BE/AE/ME/EE)
- **Action items** for the learner

---

## 3. Teacher / Class Dashboard

**Who:** Teachers assigned to a classroom  
**API:** `GET /api/teacher/dashboard` (includes analytics + assessment stats + recent attempts)

### Must display

| Item | Available |
|------|-----------|
| Number of learners in class | Yes |
| Average class score | Yes |
| Learner performance table (name, average score, assessments completed) | Yes (`learner_performance_table`) |

### Minimum analytics

| Item | Available |
|------|-----------|
| Bar chart: learner ranking (top to lowest) | Yes (`charts.learner_ranking`) |
| Class average score indicator | Yes |
| Completion rate (%) | Yes |

### Extended teacher analytics (retained)

- Classroom details (name, grade)
- Gender & inclusion for the class
- Subject and category performance charts
- Class strengths and weaknesses by competency tag
- Top performers and learners needing support
- Recent activity and **action items**

---

## 4. School Dashboard (institution)

**Who:** Institution (school) administrators  
**Filament:** Institution-scoped widgets on main dashboard  
**API:** `GET /api/dashboard` and `GET /api/dashboard/analytics` (institution role)

### Must display

| Item | Available |
|------|-----------|
| Total learners | Yes |
| Total classes (classrooms) | Yes |
| Average school score | Yes |
| Total assessments completed | Yes (all-time + 30-day activity) |

### Minimum analytics

| Item | Available |
|------|-----------|
| Line chart: school performance trend | Yes (14-day activity) |
| Bar chart: class comparison | Yes |
| Table: assessment usage per school | Yes (`assessment_usage` + Filament table widget) |

### Extended institution analytics (retained)

- Teachers count, guardian email coverage, gender reporting rate
- Learners trending up (%)
- **CBE competency distribution** for the school
- **Performance by subject** and **by competency area**
- **Gender & inclusion** — coverage stats, roster table, performance-by-gender table
- **Top performers** and **learners needing support**
- **Recent attempts** and **inactive learners** (30 days)
- **Completion rate** for all attempts at the school
- **Recommended actions** for the institution

---

## 5. Assessment Dashboard

**Who:** Any authenticated client with assessment context (typically admin or reporting tools)  
**API:** `GET /api/dashboard/assessments/{assessmentId}`

### Must display

| Item | Available |
|------|-----------|
| Assessment name | Yes |
| Total attempts | Yes |
| Average score (%) | Yes |
| Completion rate | Yes |

### Minimum analytics

| Item | Available |
|------|-----------|
| Pie chart: pass vs fail (≥50% threshold) | Yes |
| Bar chart: completed vs in progress / dropped | Yes |
| Difficulty label (rule-based) | Yes (High / Moderate / Standard / Accessible) |

---

## API reference (dashboard-related)

| Endpoint | Access | Purpose |
|----------|--------|---------|
| `GET /api/dashboard` | Authenticated | Full dashboard + user/institution + `analytics` |
| `GET /api/dashboard/analytics` | Authenticated | Role-specific analytics only |
| `GET /api/dashboard/students/{id}` | Admin, institution, teacher (scoped) | Student drill-down |
| `GET /api/dashboard/institutions/{id}` | Admin only | Full school analytics |
| `GET /api/dashboard/assessments/{id}` | Authenticated | Per-assessment analytics |
| `GET /api/token-balance` | Authenticated | Wallet balance |
| `GET /api/assessment-stats` | Authenticated | Attempt counts, completion rate |
| `GET /api/recent-assessments` | Authenticated | Recent assessment list |
| `GET /api/teacher/dashboard` | Teacher | Class dashboard bundle |

Login and register responses also embed the same dashboard payload via `DashboardDataService`.

---

## Filament admin widget map (Gravity CBC)

| Widget / page | Purpose |
|---------------|---------|
| **Platform overview** | Headline platform stats |
| **System operations** | Tokens, M-PESA, assessments created/completed, system status |
| **User growth** (line) | Registrations per month |
| **Tokens purchased vs used** (pie) | Token economy |
| **Platform activity** (line) | Completed attempts (14 days) |
| **Users by type**, **student type**, **grade levels** | Population breakdown |
| **CBE competency**, **subject**, **category** charts | Outcomes |
| **School / classroom comparison** | Cross-school tables and charts |
| **Assessment usage by school** | Per-institution usage summary |
| **Competency strengths / areas needing focus** | Category tag tables |
| **Top students / needing support** | Intervention lists |
| **Inclusion coverage + gender tables** | Equity reporting |
| **Recent attempts**, **action items**, **revenue** | Operations |
| **School analytics** (page) | Per-school full dashboard |

Institution admins see the **institution subset** of the above (scoped to their school), including **assessment usage at your school**.

---

## Admin vs institution: parity summary

| Capability | Institution admin | Gravity CBC admin |
|------------|-------------------|-------------------|
| Learner counts & school average | Their school only | All schools + platform average |
| Class comparison | Their classrooms | All classrooms (all schools) |
| Gender & inclusion | Their roster | Platform-wide (+ per school on drill-down) |
| Competency strengths / gaps | Their marked answers | Platform-wide + per school |
| Top / support learners | Their students | All students (with school column) |
| Assessment usage table | Their school | Per-school summary + drill-down |
| Tokens / M-PESA / system health | — | Yes |
| User growth / token pie | — | Yes |
| Pick any school to view | — | **School analytics** page |
| Revenue | — | Yes (if enabled) |

**Conclusion:** Institution admins receive a **full school operations and learning dashboard**. Gravity CBC admins receive **everything institutions have, aggregated platform-wide**, plus **operations, payments, tokens, system health, cross-school comparisons**, and **per-school drill-down**.

---

## Technical notes for the team

- **Caching:** Analytics recompute at most every 5 minutes; after bulk imports run `DashboardAnalyticsService::flushCache()`.
- **Performance:** Admin dashboard uses lazy-loaded widgets and a single cached `adminAnalytics()` call per request.
- **Branding:** Admin UI uses Gravity CBC colors — green `#90C142`, blue `#3D90C7`, red `#EC2735`.
- **Deployment:** Ensure `storage/` is writable by the web server for Blade compilation (especially custom pages like School analytics).

---

## Reporting tips

1. **Weekly platform report (Gravity CBC):** Use Platform overview + System operations + User growth + Activity chart + Assessment usage by school.
2. **School partner report:** Use School analytics page or institution API; include average score, completion rate, assessment usage, and gender reporting rate.
3. **Learner progress:** Use student API `assessment_history` and `score_trend_last_10`.
4. **Class report:** Use teacher API — learner ranking, class average, completion rate.
5. **Assessment quality review:** Use assessment API — pass/fail, completion vs drop-off, difficulty label.

---

*For implementation details, see `app/Services/DashboardAnalyticsService.php`, `app/Services/DashboardDataService.php`, and `app/Filament/Widgets/`.*
