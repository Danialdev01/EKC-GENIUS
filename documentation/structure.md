## Database Structure

All tables follow a consistent pattern with `*_id` as primary key, `*_status`, `*_created_at`, and `*_updated_at` fields.

---

## Admins
- CRUD
- Manage all data

```
SELECT `admin_id`, `admin_name`, `admin_email`, `admin_hash_password`, `admin_status`, `admin_created_at`, `admin_updated_at` FROM `admins` WHERE 1
```

---

## Teachers
- CRUD
- Manage teachers and students

```
SELECT `teacher_id`, `teacher_name`, `teacher_email`, `teacher_phone_number`, `teacher_specialization`, `teacher_notes`, `teacher_status`, `teacher_created_at`, `teacher_updated_at` FROM `teachers` WHERE 1
```

---

## Categories
- CRUD
- example:
    - autism
    - regular childcare
```
SELECT `category_id`, `category_name`, `category_description`, `category_price_invoice`, `category_status`, `category_created_at`, `category_updated_at` FROM `categories` WHERE 1
```

---

## Students
- CRUD
- Linked to categories

```
SELECT `student_id`, `student_ic`, `student_name`, `student_year_of_birth`, `category_id`, `student_parent_name`, `student_parent_email`, `student_parent_number`, `student_notes`, `student_status`, `student_created_at`, `student_updated_at` FROM `students` WHERE 1
```

---

## Activities
- CRUD
- types example:
    - Learning activity
    - Play activity
    - Motorskills activity

```
SELECT `activity_id`, `activity_title`, `activity_description`, `activity_type`, `activity_active_at`, `activity_status`, `activity_created_at`, `activity_updated_at` FROM `activities` WHERE 1
```

---

## Assignments
- CRUD
- Linked to activities and students

```
SELECT `assignment_id`, `activity_id`, `student_id`, `assignment_notes`, `assignment_outcome`, `assignment_status`, `assignment_created_at`, `assignment_updated_at` FROM `assignments` WHERE 1
```

---

## Assessments
- type of assessment the teacher make
- example:
    - title: Communication Skill
    - description: Communicate using mouth and respond to verbal cues
    - value score only from 1 - 5
- Linked to categories

```
SELECT `assessment_id`, `category_id`, `assessment_icon`, `assessment_title`, `assessment_description`, `assessment_status`, `assessment_created_at`, `assessment_updated_at` FROM `assessments` WHERE 1
```

---

## Student Assessments
- CRUD
- Based on assessments
- value score only from 1 - 5

```
SELECT `student_assessment_id`, `student_id`, `assessment_id`, `student_assessment_value`, `student_assessment_month`, `student_assessment_year`, `student_assessment_status`, `student_assessment_created_at`, `student_assessment_updated_at` FROM `student_assessments` WHERE 1
```

---

## AI Assessments
- assess student data and use ai to give some feedback
- example:
    - strengths: Strong performance in Communication Skill, Social Interaction, Eye Contact. These are Nurul Aini Binti Ismail's key developmental strengths.
    - focus_areas: Attention and Focus, Sensory Processing, and Motor Skills. These areas require targeted intervention to support Nurul Aini's overall development.
    - trend_analysis: Nurul Aini has shown a positive trend in Communication Skill, with a 15% improvement over the last quarter. However, there is a slight decline in Sensory Processing, indicating a need for additional support in this area.
    
```
SELECT `ai_assessment_id`, `student_id`, `ai_assessment_strengths`, `ai_assessment_focus_areas`, `ai_assessment_trend_analysis`, `ai_assessment_month`, `ai_assessment_year`, `ai_assessment_status`, `ai_assessment_created_at`, `ai_assessment_updated_at` FROM `ai_assessments` WHERE 1
```

---

## Alerts
- CRUD
- Auto-generated when student avg score < 2.5
- Provides recommended actions and activities

```
SELECT `alert_id`, `student_id`, `alert_recommended_action`, `alert_recommended_activity`, `alert_status`, `alert_created_at` FROM `alerts` WHERE 1
```

---

## Attendance
- CRUD
- type of attendance example:
    - 1 = Present
    - 2 = Absent
    - 3 = Late
- notes example: 
    - sick
    - family matter
    - etc

```
SELECT `attendance_id`, `student_id`, `attendance_type`, `attendance_notes`, `attendance_datetime`, `attendance_status` FROM `attendances` WHERE 1
```

---

## Invoices
- CRUD
- Monthly invoices for students

```
SELECT `invoice_id`, `student_id`, `invoice_due_month`, `invoice_due_year`, `invoice_type`, `invoice_status`, `invoice_created_at`, `invoice_updated_at` FROM `invoices` WHERE 1
```

---

## Payments
- CRUD

```
SELECT `payment_id`, `student_id`, `invoice_id`, `payment_value`, `payment_method`, `payment_status`, `payment_created_at` FROM `payments` WHERE 1
```

---

## Table Relationships

```
categories (1) ──→ (N) students
                        │
                        ├──→ (N) student_assessments ──→ (1) assessments
                        │
                        ├──→ (N) assignments ──→ (1) activities
                        │
                        ├──→ (N) alerts
                        │
                        ├──→ (N) attendances
                        │
                        ├──→ (N) invoices ──→ (N) payments
                        │
                        └──→ (N) ai_assessments

teachers (1) ──→ (N) assignments (via activity)

admins (1) ──→ manage all
```