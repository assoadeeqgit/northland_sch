# Subject-Class Relationships Implementation

## Overview
This document outlines the implementation of subject-class relationships for Northland School Kano's database system.

## Problem Statement
Every subject in the database needed to have a class_id association to properly organize subjects by their respective class levels (Pre-Nursery, Nursery, Primary, Junior Secondary, and Senior Secondary).

## Solution Implemented

### 1. Database Structure
Created a **many-to-many relationship** between subjects and classes using the `subject_class_assignments` table.

**Why Many-to-Many?**
- Subjects like "English" and "Mathematics" are taught across multiple classes
- Each class has multiple subjects
- This provides maximum flexibility for the school's curriculum

### 2. Table Schema

```sql
CREATE TABLE subject_class_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subject_class (subject_id, class_id)
);
```

### 3. Subject Organization

#### Pre-Nursery Section (Class ID: 1)
**9 Subjects:**
1. English
2. Mathematics
3. Phonics
4. Basic Science
5. Health Habits
6. Social Habits
7. Handwriting
8. Rhymes
9. Coloring

#### Nursery 1 & 2 (Class IDs: 2, 3)
**11 Subjects:**
1. Quantitative Reasoning
2. Verbal Reasoning
3. Practical Life Skills
4. English
5. Mathematics
6. Phonics
7. Basic Science
8. Health Habits
9. Social Habits
10. Handwriting
11. Rhymes

#### Primary Section (Class IDs: 4, 5, 6, 7, 8)
**14 Subjects:**
1. English
2. Mathematics
3. Basic Science
4. Basic Technology
5. Social Studies
6. Civic Education
7. Handwriting
8. C.C.A (Cultural and Creative Arts)
9. I.R.K (Islamic Religious Knowledge)
10. Computer
11. PHE (Physical and Health Education)
12. ARABIC
13. Quantitative Reasoning
14. Verbal Reasoning

#### Junior Secondary Section (Class IDs: 9, 10, 11)
**15 Subjects:**
1. English
2. Mathematics
3. Basic Science
4. Basic Technology
5. C.C.A
6. Civic Education
7. Basic Science & Technology
8. National Values
9. Security Education
10. Pre-Vocational Studies
11. P.H.E
12. Business Studies
13. Hausa
14. Arabic
15. History

#### Senior Secondary Section (Class IDs: 12, 13, 14)
**11 Subjects:**
1. English
2. Mathematics
3. Chemistry (Science Department)
4. Computer
5. History (Arts Department)
6. Economics (Arts/Commercial)
7. Eng. Literature (Arts)
8. Civic Education
9. I.R.K
10. Animal Husbandry (Vocational)
11. Catering Craft (Vocational)

### 4. Implementation Files

#### Migration File
- **Location:** `/var/www/html/nsknbkp1/database/migrations/add_subject_class_relationships.sql`
- **Purpose:** Creates the relationship table and populates all subjects with their class assignments

#### Helper Functions
- **Location:** `/var/www/html/nsknbkp1/includes/subject_class_helper.php`
- **Functions:**
  - `getSubjectsByClass($pdo, $class_id)` - Get all subjects for a specific class
  - `getClassesBySubject($pdo, $subject_id)` - Get all classes for a specific subject
  - `isSubjectAssignedToClass($pdo, $subject_id, $class_id)` - Check assignment
  - `assignSubjectToClass($pdo, $subject_id, $class_id)` - Create assignment
  - `removeSubjectFromClass($pdo, $subject_id, $class_id)` - Remove assignment
  - `getSubjectCountPerClass($pdo)` - Get statistics
  - `getAllSubjectsWithClasses($pdo)` - Get all subjects with their classes
  - `getSubjectsBySection($pdo, $section)` - Get subjects by school section

#### Usage Examples
- **Location:** `/var/www/html/nsknbkp1/test/subject_class_examples.php`
- **Purpose:** Demonstrates how to use the helper functions

### 5. Database Statistics

- **Total Subjects:** 60
- **Total Subject-Class Assignments:** 179
- **Classes Coverage:** All 14 classes have subjects assigned

### 6. Usage in Timetable Generation

When generating timetables for a specific class:

```php
require_once '../includes/subject_class_helper.php';

// Get all subjects for the selected class
$class_id = $_POST['class_id'];
$subjects = getSubjectsByClass($pdo, $class_id);

// Loop through subjects to create timetable slots
foreach ($subjects as $subject) {
    echo "<option value='{$subject['id']}'>{$subject['subject_name']}</option>";
}
```

### 7. Benefits of This Implementation

1. **Flexibility:** Easy to add or remove subject-class assignments
2. **Data Integrity:** Foreign key constraints ensure referential integrity
3. **Scalability:** Can easily add new subjects or classes
4. **Maintainability:** Centralized helper functions reduce code duplication
5. **Accuracy:** Each subject is properly associated with its appropriate class levels

### 8. Migration Executed

The migration has been successfully executed on the database. All subjects are now properly assigned to their respective classes according to the Northland School curriculum structure.

### 9. Next Steps

To integrate this into your timetable generation system:

1. Update `timetable_generate.php` to use `getSubjectsByClass()` when loading subjects
2. Update any subject management pages to show class assignments
3. Consider creating an admin interface to manage subject-class assignments
4. Update any reports or analytics to use the new relationship structure

## Verification

Run the test file to verify the implementation:
```
http://your-domain/nsknbkp1/test/subject_class_examples.php
```

Or query the database directly:
```sql
SELECT c.class_name, COUNT(sca.id) as subject_count 
FROM classes c 
LEFT JOIN subject_class_assignments sca ON c.id = sca.class_id 
GROUP BY c.id, c.class_name 
ORDER BY c.id;
```
