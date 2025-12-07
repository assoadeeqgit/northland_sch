# Quick Reference Card - Unified Database

## 🚀 Quick Start

### Database Connection
```bash
# Database Name
northland_schools_kano

# Admin Dashboard Config
/var/www/html/nsknbkp1/config/database.php
Password: A@123456.Aaa

# Teacher Dashboard Config
/var/www/html/nsknbkp1/sms-teacher/config/database.php
Password: 309612.Aa
```

### Test Logins
```
Admin:
- Email: abdul@notherland.edu.ng
- Password: password

Teacher:
- Email: aisha.bello@northland.edu.ng
- Password: password
```

## 📊 Quick Stats
- **40 Users** (10 teachers, 20+ students, 3 admin)
- **18 Results** (with auto-calculated percentages)
- **18 Assignments** (5 new with submissions)
- **21 Attendance** records
- **24 Payments** (full & partial)
- **10 Library Books**
- **12 Events & Notices**

## 🔧 Quick Commands

### View All Students
```sql
SELECT s.student_id, CONCAT(u.first_name, ' ', u.last_name) AS name, 
       c.class_name
FROM students s
JOIN users u ON s.user_id = u.id
JOIN classes c ON s.class_id = c.id;
```

### View Results with Auto %
```sql
SELECT CONCAT(u.first_name, ' ', u.last_name) AS student,
       sub.subject_name,
       r.marks_obtained,
       r.percentage,  -- Auto-calculated!
       r.grade
FROM results r
JOIN students s ON r.student_id = s.id
JOIN users u ON s.user_id = u.id
JOIN subjects sub ON r.subject_id = sub.id
LIMIT 10;
```

### View Teacher Assignments
```sql
SELECT CONCAT(u.first_name, ' ', u.last_name) AS teacher,
       sub.subject_name,
       c.class_name
FROM class_subjects cs
JOIN teachers t ON cs.teacher_id = t.id
JOIN users u ON t.user_id = u.id
JOIN subjects sub ON cs.subject_id = sub.id
JOIN classes c ON cs.class_id = c.id;
```

## 📝 Key Tables
1. `users` - All users
2. `students` - Student records  
3. `teachers` - Teacher records
4. `results` - Exam results (with auto %)
5. `assignments` - Homework/tasks
6. `assignment_submissions` - Student work
7. `attendance` - Daily tracking
8. `payments` - Fee payments
9. `classes` - Class definitions
10. `subjects` - Subject catalog

## ✅ What's New
- ✅ Auto-percentage calculation in results
- ✅ Assignment submission tracking
- ✅ Enhanced with 20+ new records
- ✅ Proper foreign key constraints
- ✅ Report generation tables

## 📄 Documentation
- `DATABASE_UNIFICATION_GUIDE.md` - Full guide
- `DATABASE_UNIFICATION_COMPLETE.md` - Summary
- `unified_database_migration.sql` - Schema updates
- `insert_dummy_data.sql` - Test data

---
**Ready to use! Both dashboards share one database now.** 🎉
