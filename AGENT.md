# EKC Genius IR4.0 Platform - Agent Documentation

## System Overview

**EKC Genius IR4.0 Platform** is an integrated IR4.0 platform leveraging **Mathematical Analytics** and **Artificial Intelligence** to modernize management, intervention, and inclusive education for early childhood centers, specifically for students with autism.

- **Developer Collaboration**: Universiti Pendidikan Sultan Idris (UPSI) + EKC Genius Sdn. Bhd.
- **Lead Researcher**: Dr. Adib Bin Mashuri (UPSI)
- **Developer**: Danial Irfan Bin Zakaria
- **Project Duration**: 12 months

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP with PDO (PHP Data Objects) |
| Database | MySQL |
| Frontend | TailwindCSS |
| JS Charts | Chart.js (Radar/Spiderweb charts) |

### Database Connection Pattern
```php
$host = 'localhost';
$dbname = 'ekc_genius';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

---

## Database Schema

### Core Entities

#### 1. `students`
| Column | Type | Description |
|--------|------|-------------|
| student_id | INT (PK, AUTO) | Primary key |
| category_id | INT (FK) | Links to categories |
| student_name | VARCHAR(255) | Student full name |
| student_year_of_birth | YEAR | Year of birth |
| student_enrollment_date | DATE | Enrollment date |
| student_parent_name | VARCHAR(255) | Parent/guardian name |
| student_parent_email | VARCHAR(255) | Parent email |
| student_parent_number | VARCHAR(20) | Parent contact number |
| student_notes | TEXT | Additional notes |
| student_updated_at | TIMESTAMP | Last update |
| student_created_at | TIMESTAMP | Creation date |
| student_status | ENUM('active','inactive') | Status |

#### 2. `teachers`
| Column | Type | Description |
|--------|------|-------------|
| teacher_id | INT (PK, AUTO) | Primary key |
| teacher_name | VARCHAR(255) | Teacher full name |
| teacher_email | VARCHAR(255) | Email |
| teacher_phone_number | VARCHAR(20) | Phone |
| teacher_specialization | VARCHAR(255) | Specialization area |
| teacher_notes | TEXT | Additional notes |
| teacher_updated_at | TIMESTAMP | Last update |
| teacher_created_at | TIMESTAMP | Creation date |
| teacher_status | ENUM('active','inactive') | Status |

#### 3. `categories`
| Column | Type | Description |
|--------|------|-------------|
| category_id | INT (PK, AUTO) | Primary key |
| category_name | VARCHAR(255) | e.g., "autism", "regular childcare" |
| category_description | TEXT | Description |
| category_updated_at | TIMESTAMP | Last update |
| category_created_at | TIMESTAMP | Creation date |
| category_status | ENUM('active','inactive') | Status |

#### 4. `activities`
| Column | Type | Description |
|--------|------|-------------|
| activity_id | INT (PK, AUTO) | Primary key |
| activity_title | VARCHAR(255) | Activity title |
| activity_description | TEXT | Description |
| activity_type | ENUM('learning','play','motorskills') | Activity type |
| activity_active_at | DATE | Active date |
| activity_updated_at | TIMESTAMP | Last update |
| activity_created_at | TIMESTAMP | Creation date |
| activity_status | ENUM('active','inactive') | Status |

#### 5. `assessments`
| Column | Type | Description |
|--------|------|-------------|
| assessment_id | INT (PK, AUTO) | Primary key |
| category_id | INT (FK) | Links to category |
| assessment_icon | VARCHAR(50) | Icon identifier |
| assessment_title | VARCHAR(255) | Title (e.g., "Communication Skill") |
| assessment_description | TEXT | Description |
| assessment_updated_at | TIMESTAMP | Last update |
| assessment_created_at | TIMESTAMP | Creation date |
| assessment_status | ENUM('active','inactive') | Status |

#### 6. `student_assessments`
| Column | Type | Description |
|--------|------|-------------|
| student_assessment_id | INT (PK, AUTO) | Primary key |
| assessment_id | INT (FK) | Links to assessment |
| student_id | INT (FK) | Links to student |
| student_assessment_value | TINYINT(1) | Score 1-5 |
| student_assessment_month | MONTH | Assessment month |
| student_assessment_year | YEAR | Assessment year |
| student_assessment_updated_at | TIMESTAMP | Last update |
| student_assessment_created_at | TIMESTAMP | Creation date |
| student_assessment_status | ENUM('active','inactive') | Status |

#### 7. `ai_assessments`
| Column | Type | Description |
|--------|------|-------------|
| ai_assessment_id | INT (PK, AUTO) | Primary key |
| student_id | INT (FK) | Links to student |
| ai_assessment_strengths | TEXT | AI-generated strengths |
| ai_assessment_focus_area | TEXT | Areas needing focus |
| ai_assessment_trend_analysis | TEXT | Trend analysis |
| ai_assessment_month | MONTH | Assessment month |
| ai_assessment_year | YEAR | Assessment year |
| ai_assessment_updated_at | TIMESTAMP | Last update |
| ai_assessment_created_at | TIMESTAMP | Creation date |
| ai_assessment_status | ENUM('active','inactive') | Status |

#### 8. `attendances`
| Column | Type | Description |
|--------|------|-------------|
| attendance_id | INT (PK, AUTO) | Primary key |
| student_id | INT (FK) | Links to student |
| attendance_type | TINYINT(1) | 1=Present, 2=Absent, 3=Late |
| attendance_notes | TEXT | Notes (e.g., "sick", "family matter") |
| attendance_updated_at | TIMESTAMP | Last update |
| attendance_created_at | TIMESTAMP | Creation date |
| attendance_status | ENUM('active','inactive') | Status |

#### 9. `assignments`
| Column | Type | Description |
|--------|------|-------------|
| assignment_id | INT (PK, AUTO) | Primary key |
| activity_id | INT (FK) | Links to activity |
| student_id | INT (FK) | Links to student |
| assignment_notes | TEXT | Teacher notes |
| assignment_outcome | TEXT | Outcome result |
| assignment_updated_at | TIMESTAMP | Last update |
| assignment_created_at | TIMESTAMP | Creation date |
| assignment_status | ENUM('active','inactive') | Status |

#### 10. `admins`
| Column | Type | Description |
|--------|------|-------------|
| admin_id | INT (PK, AUTO) | Primary key |
| admin_name | VARCHAR(255) | Admin name |
| admin_email | VARCHAR(255) | Email |
| admin_hash_password | VARCHAR(255) | Hashed password |
| admin_updated_at | TIMESTAMP | Last update |
| admin_created_at | TIMESTAMP | Creation date |
| admin_status | ENUM('active','inactive') | Status |

#### 11. `payments`
| Column | Type | Description |
|--------|------|-------------|
| payment_id | INT (PK, AUTO) | Primary key |
| student_id | INT (FK) | Links to student |
| invoice_id | INT (FK) | Links to invoice |
| payment_value | DECIMAL(10,2) | Payment amount |
| payment_method | VARCHAR(50) | Payment method |
| payment_updated_at | TIMESTAMP | Last update |
| payment_created_at | TIMESTAMP | Creation date |
| payment_status | ENUM('active','inactive') | Status |

#### 12. `invoices`
| Column | Type | Description |
|--------|------|-------------|
| invoice_id | INT (PK, AUTO) | Primary key |
| student_id | INT (FK) | Links to student |
| invoice_amount | DECIMAL(10,2) | Total amount |
| invoice_month | MONTH | Invoice month |
| invoice_year | YEAR | Invoice year |
| invoice_status | ENUM('paid','unpaid','overdue') | Status |
| invoice_due_date | DATE | Due date |
| invoice_updated_at | TIMESTAMP | Last update |
| invoice_created_at | TIMESTAMP | Creation date |

---

## User Roles & Permissions

### 1. Administrator
- Manage all data (students, teachers, parents)
- Attendance tracking overview
- Payment processing and invoice generation
- Reports and analytics access
- Full system configuration

### 2. Teacher
- Record student assessments (1-5 scale)
- Manage classroom activities
- Record attendance
- View AI-driven recommendations
- Monitor student progress

### 3. Parent
- Remote progress monitoring
- View automated digital reports
- View billing/invoice history
- Payment viewing

---

## Key Features

### 1. Smart Assessment & AI Analytics

**Digital Rubrics**
- Teachers record assessments on 1-5 scale
- Indicators: Communication, Social Interaction, Cognitive Development, etc.

**Early Warning System**
- Automated alerts when score < 2.5 threshold
- Intervention suggestions

**Predictive Analysis**
- Formula: `Predicted Score = Current Score + Average Growth`
- Growth Rate: `((Score_final - Score_initial) / Score_initial) × 100`

### 2. Advanced Visualization

**Radar/Spiderweb Charts**
- Interactive visual profiles
- Compare current vs previous assessments
- Highlight progress at a glance

**Trend Analysis**
- Categories: "Improvement", "Stable", "Declining"
- Visual tracking of developmental patterns

### 3. AI Chatbot Assistant
- Natural language interface
- Helps interpret time-series data
- Identifies anomalies
- Explains predicted trends

### 4. Operational Management

| Module | Features |
|--------|----------|
| Attendance | Present/Absent/Late tracking with notes |
| Activities | Learning/Play/Motor skills activities |
| Assignments | Activity-to-student linking |
| Payments | Invoice generation, payment tracking |
| Reports | Automated digital reports (20-30 min vs 2-3 hrs) |

---

## Mathematical Analytics

### Growth Rate Formula
```
Growth Rate = ((Score_final - Score_initial) / Score_initial) × 100
```

### Predicted Score Formula
```
Predicted Score = Current Score + Average Growth
```

### Development Index
- Monthly averages
- Annual averages
- Center-wide performance tracking

---

## Projected Impact

| Metric | Before | After |
|--------|--------|-------|
| Productivity | Baseline | +60% |
| Operating Costs | RM80,000/year | RM30,000/year |
| Report Time | 2-3 hours | 20-30 minutes |
| Paper Usage | 30,000 sheets/year | 3,000 sheets/year (-90%) |

---

## File Structure

```
/srv/http/git/EKC-GENIUS/
├── README.md                    # System overview
├── index.php                    # Landing page
├── DESIGN.md                    # Design system reference
├── agent.md                     # This file
├── documentation/
│   ├── structure.md            # DB schema reference
│   ├── skop.md                  # Project scope
│   ├── erd.drawio               # ERD diagram
│   └── documents/              # Supporting docs
├── config/
│   └── connect.php              # PDO connection
├── backend/
│   ├── models/               # All functions to CRUD entity
│   ├── functions/               # All functions to manipulate data
│   └── api/              # All API endpoints
│   └── login.php              # Login backend
│   └── logout.php              # Logout backend
│   └── register.php              # Register backend
│   └── dashboard.php              # Dashboard backend
│   └── profile.php              # Profile backend
│   └── settings.php              # Settings backend
├── admin/
├── teacher/
├── parents/
└── src/
    └── images/             # SQL migrations
    └── css/             # SQL migrations
    └── js/             # SQL migrations
    └── php/             # SQL migrations
```

---

## Design System (TailwindCSS)

### Color Palette
```css
/* Primary */
--opencode-dark: #201d1d;
--opencode-light: #fdfcfc;
--mid-gray: #9a9898;

/* Secondary */
--dark-surface: #302c2c;
--border-gray: #646262;
--light-surface: #f1eeee;

/* Accent */
--accent-blue: #007aff;
--accent-blue-hover: #0056b3;

/* Semantic */
--danger-red: #ff3b30;
--success-green: #30d158;
--warning-orange: #ff9f0a;
```

### Typography
- Font: System monospace stack (for terminal aesthetic)
- Heading: text-2xl (Berkeley Mono style via font-mono)
- Body: text-base

### Spacing
- Base: 8px grid (Tailwind default)
- Margins: 8, 16, 24, 32, 48, 64, 96

### Border Radius
- Default: 4px (rounded)
- Inputs: 6px (rounded-md)

---

## Development Guidelines

### PHP PDO Patterns

**Fetch All Records**
```php
$stmt = $pdo->prepare("SELECT * FROM table_name WHERE status = 'active'");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

**Fetch Single Record**
```php
$stmt = $pdo->prepare("SELECT * FROM table_name WHERE id = ?");
$stmt->execute([$id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
```

**Insert Record**
```php
$stmt = $pdo->prepare("INSERT INTO table_name (col1, col2) VALUES (?, ?)");
$stmt->execute([$val1, $val2]);
```

**Update Record**
```php
$stmt = $pdo->prepare("UPDATE table_name SET col1 = ? WHERE id = ?");
$stmt->execute([$val, $id]);
```

### TailwindCSS Classes

**Dark Background Card**
```html
<div class="bg-[#201d1d] text-[#fdfcfc] p-6 rounded">
```

**Input Field**
```html
<input class="bg-[#f8f7f7] border border-black/10 rounded-md px-5 py-3">
```

**Button**
```html
<button class="bg-[#201d1d] text-[#fdfcfc] px-5 py-1 rounded hover:bg-[#302c2c]">
```

**Semantic Colors**
```html
<span class="text-[#ff3b30]">Danger</span>
<span class="text-[#30d158]">Success</span>
<span class="text-[#ff9f0a]">Warning</span>
<span class="text-[#007aff]">Info</span>
```

### Chart.js Radar Chart Config
```javascript
{
  type: 'radar',
  data: {
    labels: ['Communication', 'Social', 'Cognitive', 'Motor'],
    datasets: [{
      label: 'Current',
      data: [4, 3, 4, 2],
      borderColor: '#007aff',
      backgroundColor: 'rgba(0, 122, 255, 0.2)'
    }]
  },
  options: {
    responsive: true,
    scale: { min: 0, max: 5, stepSize: 1 }
  }
}
```

---

## Implementation Phases

### Phase 1: Foundation
- [ ] Database setup with MySQL
- [ ] PDO connection class
- [ ] Base MVC structure
- [ ] TailwindCSS setup
- [ ] Authentication system (Admin, Teacher, Parent)

### Phase 2: Core Modules
- [ ] Student CRUD
- [ ] Teacher CRUD
- [ ] Category CRUD
- [ ] Attendance tracking

### Phase 3: Assessment Engine
- [ ] Assessment templates CRUD
- [ ] Student assessment recording
- [ ] Growth rate calculations
- [ ] Early warning alerts (threshold < 2.5)

### Phase 4: AI & Analytics
- [ ] AI assessment generation
- [ ] Trend analysis engine
- [ ] Radar chart integration (Chart.js)
- [ ] Predictive score calculations

### Phase 5: Operations
- [ ] Activity management
- [ ] Assignment tracking
- [ ] Invoice generation
- [ ] Payment tracking

### Phase 6: Parent Portal
- [ ] Progress dashboard
- [ ] Report viewing
- [ ] Invoice history

### Phase 7: AI Chatbot
- [ ] Natural language interface
- [ ] Time-series data interpretation
- [ ] Anomaly detection responses

### Phase 8: Optimization
- [ ] Performance optimization
- [ ] Paper reduction (90%)
- [ ] Report automation testing
