
## Activities
- CRUD
- types example:
    - Learning activity
    - Play activity
    - Motorskills acitivity

SELECT `activity_id`, `activity_name`, `activity_description`, `activity_type`, `activity_active_at`, `activity_updated_at`, `activity_created_at`, `activity_status` FROM `activites` WHERE 1

## Assessment
- type of assessment the teacher make
- example:
    - title: Communication Skill
    - description: Communicate using mouth and respond to verbal cues
    - value score only from 1 - 5 
SELECT `assessment_id`, `category_id`, `assessment_icon`, `assessment_title`, `assessment_description`, `assessment_updated_at`, `assessment_created_at`, `assessment_status` FROM `assessments` WHERE 1

## Student Assessment
- CRUD
- Based on 
- value score only from 1 - 5
SELECT `student_assessment_id`, `assessment_id`, `student_id`, `student_assessment_value`, `student_assessment_month`, `student_assessment_year`, `student_assessment_updated_at`, `student_assessment_created_at`, `student_assessment_status` FROM `student_assessments` WHERE 1

## AI Assessment
- assess student data and use ai to give some feedback
- example:
    - strengths: Strong performance in Communication Skill, Social Interaction, Eye Contact. These are Nurul Aini Binti Ismail's key developmental strengths.
    - focus area: Attention and Focus, Sensory Processing, and Motor Skills. These areas require targeted intervention to support Nurul Aini's overall development.
    - trend analysis: Nurul Aini has shown a positive trend in Communication Skill, with a 15% improvement over the last quarter. However, there is a slight decline in Sensory Processing, indicating a need for additional support in this area.
    
SELECT `ai_assessment_id`, `student_id`, `ai_assessment_strengths`, `ai_assessment_focus_area`, `ai_assessment_trend_analysis`, `ai_assessment_month`, `ai_assessment_year`, `ai_assessment_updated_at`, `ai_assessment_created_at`, `ai_assessment_status` FROM `ai_assessments` WHERE 1


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

SELECT `attendance_id`, `student_id`, `attendance_type`, `attendance_notes`, `attendance_updated_at`, `attendance_created_at`, `attendance_status` FROM `attendances` WHERE 1

## Assignment
- CRUD
- the assignment is linked to activity

SELECT `assignment_id`, `activity_id`, `student_id`, `assignment_notes`, `assignment_outcome`, `assignment_updated_at`, `assignment_created_at`, `assignment_status` FROM `assignments` WHERE 1

## Admins
- CRUD
- Manage all data

SELECT `admin_id`, `admin_name`, `admin_email`, `admin_hash_password`, `admin_updated_at`, `admin_created_at`, `admin_status` FROM `admins` WHERE 1

## Payments
- CRUD

SELECT `payment_id`, `student_id`, `invoice_id`, `payment_value`, `payment_method`, `payment_updated_at`, `payment_created_at`, `payment_status` FROM `payments` WHERE 1

## Students
- CRUD

SELECT `student_id`, `category_id`, `student_name`, `student_year_of_birth`, `student_enrollment_date`, `student_parent_name`, `student_parent_email`, `student_parent_number`, `student_notes`, `student_updated_at`, `student_created_at`, `student_status` FROM `students` WHERE 1
## Categories
- CRUD
- example:
    - autisme
    - regular childcare
SELECT `category_id`, `category_name`, `category_description`, `category_price_invoice`, `category_updated_at`, `category_created_at`, `category_status` FROM `categories` WHERE 1