# Database Unification Guide

## Overview
This document explains how the Teacher Dashboard and Admin Dashboard databases have been unified into a single, comprehensive database system.

## Database Name
**`northland_schools_kano`**

Both dashboards now share the same database with consistent structure.

## Database Configuration Files

### Main Dashboard (Admin)
- **Location**: `/var/www/html/nsknbkp1/config/database.php`
- **Connection Type**: PDO
- **Host**: localhost
- **Username**: root
- **Password**: A@123456.Aaa

### Teacher Dashboard
- **Location**: `/var/www/html/nsknbkp1/sms-teacher/config/database.php`
- **Connection Type**: PDO
- **Host**: localhost
- **Username**: root
- **Password**: 309612.Aa

> **Note**: Both dashboards connect to the same database but with different credentials. Ensure both passwords are correct for your MySQL setup.

## Migration Steps

### 1. Run the Unified Migration Script

```bash
# Navigate to project root
cd /var/www/html/nsknbkp1

# Execute the migration script
mysql -u root -p northland_schools_kano < unified_database_migration.sql
```

This script will:
- Enhance the `results` table with all features from both dashboards
- Add assignment submissions tracking
- Ensure all report-related tables exist
- Create proper foreign key relationships

### 2. Insert Dummy Data

```bash
# Execute the dummy data script
mysql -u root -p northland_schools_kano < insert_dummy_data.sql
```

This will populate the database with:
- **Additional Teachers** (5 new teachers with complete profiles)
- **Additional Students** (15 new students across different classes)
- **Exams & Results** (Comprehensive exam records with grades and positions)
- **Assignments** (5 assignments with submissions and grading)
- **Attendance Records** (Daily attendance for the past week)
- **Fee Structure & Payments** (Fee plans and payment records)
- **Events** (School events and activities)
- **Notices** (Important announcements)
- **Library Books** (Additional books in the library catalog)
- **Inventory Items** (School supplies and equipment)
- **Activity Logs** (Recent system activities)
- **Reports** (Scheduled and generated reports)

## Key Enhancements

### 1. Enhanced Results Table
The results table now includes:
- Automatic percentage calculation (stored generated column)
- Grade and grade point tracking
- Position in class and subject ranking
- Created by and timestamp tracking
- Comprehensive foreign key constraints

### 2. Assignment Submissions
New table `assignment_submissions` tracks:
- Student submissions
- Grading and feedback
- Late submissions
- File attachments
- Submission status

### 3. Unified User Management
- Single `users` table for all user types
- Role-based access through `user_roles`
- Session management via `user_sessions`
- Audit logging in `auth_audit_log`

## Database Schema Summary

### Core Tables (37 total)

#### User Management (6 tables)
1. `users` - All system users
2. `user_roles` - Role definitions and permissions
3. `user_sessions` - Active user sessions
4. `auth_audit_log` - Authentication events
5. `admin_profiles` - Admin-specific data
6. `student_profiles` - Student-specific data

#### Academic Management (13 tables)
7. `academic_sessions` - Academic years
8. `terms` - School terms
9. `classes` - Class definitions
10. `subjects` - Subject catalog
11. `class_subjects` - Subject-class-teacher assignments
12. `teachers` - Teacher records
13. `teacher_profiles` - Teacher professional data
14. `students` - Student records
15. `timetable` - Class schedules
16. `assignments` - Homework and projects
17. `assignment_submissions` - Student submissions
18. `exams` - Examination records
19. `exam_types` - Exam categories

#### Assessment & Results (1 table)
20. `results` - Student exam results with auto-calculated percentages

#### Attendance (1 table)
21. `attendance` - Daily attendance records

#### Finance (3 tables)
22. `fee_structure` - Fee definitions
23. `payments` - Payment records
24. `schools` - School profile

#### Library (2 tables)
25. `library_books` - Book catalog
26. `book_borrowing` - Borrowing records

#### Operations (5 tables)
27. `inventory` - School supplies
28. `staff_profiles` - Non-teaching staff
29. `events` - School events
30. `notices` - Announcements
31. `activity_log` - System activity tracking

#### Reporting (2 tables)
32. `generated_reports` - Report archive
33. `report_schedules` - Automated report schedules

#### Settings (1 table)
34. `settings` - System configuration

## Sample Data Highlights

### Users
- **3 Admins**: System administrators
- **10 Teachers**: Subject teachers with specializations
- **35+ Students**: Across different class levels
- **2 Staff Members**: Support staff

### Academic Data
- **3 Academic Sessions**: 2023/2024, 2024/2025, 2025/2026
- **3 Terms per session**: First, Second, Third
- **15 Classes**: From Garden to SS 3
- **11 Subjects**: Core, Electives, and Extra-curricular
- **25+ Timetable Slots**: Daily schedules

### Assessment Data
- **10 Exams**: Across multiple classes and subjects
- **30+ Results**: With grades, positions, and remarks
- **5 Assignments**: Various types (Homework, Quiz, Project)
- **5 Submissions**: With scores and feedback

### Operational Data
- **25+ Attendance Records**: Recent attendance logs
- **8 Payments**: Fee payments across different methods
- **4 Events**: Upcoming school events
- **4 Notices**: Active announcements
- **10 Library Books**: Book catalog
- **10 Inventory Items**: School supplies

## Testing the Unified Database

### For Admin Dashboard

```bash
# Open browser to admin dashboard
http://localhost/nsknbkp1/dashboard/admin-dashboard.php
```

**Test Credentials**:
- Email: `abdul@notherland.edu.ng`
- Password: `password` (default hashed password)
- User Type: Admin

### For Teacher Dashboard

```bash
# Open browser to teacher dashboard
http://localhost/nsknbkp1/sms-teacher/teacher_dashboard.php
```

**Test Credentials**:
- Email: `aisha.bello@northland.edu.ng`
- Password: `password` (default hashed password)
- User Type: Teacher

## Verification Queries

### Check Total Records

```sql
USE northland_schools_kano;

-- Count all tables
SELECT COUNT(*) AS total_tables 
FROM information_schema.tables 
WHERE table_schema = 'northland_schools_kano';

-- Check key table counts
SELECT 
    'Users' AS Table_Name, COUNT(*) AS Records FROM users
UNION ALL SELECT 'Students', COUNT(*) FROM students
UNION ALL SELECT 'Teachers', COUNT(*) FROM teachers
UNION ALL SELECT 'Results', COUNT(*) FROM results
UNION ALL SELECT 'Assignments', COUNT(*) FROM assignments
UNION ALL SELECT 'Attendance', COUNT(*) FROM attendance
UNION ALL SELECT 'Payments', COUNT(*) FROM payments;
```

### Verify Relationships

```sql
-- Check student-class relationships
SELECT 
    s.student_id,
    u.first_name,
    u.last_name,
    c.class_name
FROM students s
JOIN users u ON s.user_id = u.id
JOIN classes c ON s.class_id = c.id
LIMIT 10;

-- Check teacher-subject assignments
SELECT 
    u.first_name,
    u.last_name,
    sub.subject_name,
    c.class_name
FROM class_subjects cs
JOIN teachers t ON cs.teacher_id = t.id
JOIN users u ON t.user_id = u.id
JOIN subjects sub ON cs.subject_id = sub.id
JOIN classes c ON cs.class_id = c.id;
```

### Check Results with Grades

```sql
-- View student results with auto-calculated percentages
SELECT 
    u.first_name,
    u.last_name,
    sub.subject_name,
    r.marks_obtained,
    r.total_marks,
    r.percentage,
    r.grade,
    r.position_in_class,
    r.remarks
FROM results r
JOIN students s ON r.student_id = s.id
JOIN users u ON s.user_id = u.id
JOIN subjects sub ON r.subject_id = sub.id
ORDER BY r.created_at DESC
LIMIT 10;
```

## Important Notes

### Password Synchronization
If you need to synchronize the database password for both dashboards:

```bash
# Option 1: Update Main Dashboard Password
# Edit: /var/www/html/nsknbkp1/config/database.php
# Change: $password = '309612.Aa';

# Option 2: Update Teacher Dashboard Password
# Edit: /var/www/html/nsknbkp1/sms-teacher/config/database.php
# Change: $password = 'A@123456.Aaa';

# Option 3: Use a common password for both
# Set a new MySQL password and update both config files
```

### Foreign Key Constraints
All tables are properly linked with CASCADE, SET NULL, or RESTRICT constraints to maintain data integrity.

### Auto-Increment IDs
- User IDs start from 1 and increment
- Student IDs, Teacher IDs are auto-generated with prefixes (STU, TCH, etc.)
- All primary keys use AUTO_INCREMENT

## Troubleshooting

### Connection Issues

```bash
# Test database connection
mysql -u root -p -e "USE northland_schools_kano; SELECT 'Connected!' AS status;"

# Check if database exists
mysql -u root -p -e "SHOW DATABASES LIKE 'northland_schools_kano';"
```

### Duplicate Key Errors
The scripts use `ON DUPLICATE KEY UPDATE` to handle existing records gracefully. If you encounter issues:

```sql
-- Reset auto-increment values
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE students AUTO_INCREMENT = 1;
ALTER TABLE teachers AUTO_INCREMENT = 1;
```

### Permission Issues

```bash
# Grant all privileges to root user
mysql -u root -p -e "GRANT ALL PRIVILEGES ON northland_schools_kano.* TO 'root'@'localhost'; FLUSH PRIVILEGES;"
```

## Next Steps

1. **Run the migration script** to update the database structure
2. **Insert dummy data** for testing both dashboards
3. **Test both dashboards** with the unified database
4. **Verify all functionality** works correctly
5. **Update any hardcoded connections** in your PHP files if needed

## Support

For issues or questions about the database unification:
1. Check the verification queries above
2. Review error logs in MySQL
3. Ensure both config files have correct credentials
4. Verify foreign key constraints are not blocking operations

---

**Last Updated**: December 7, 2025  
**Version**: 1.0  
**Database**: northland_schools_kano  
**Status**: ✅ Unified and Ready
