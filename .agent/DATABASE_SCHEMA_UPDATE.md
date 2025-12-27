# ✅ Database Schema Updated - database.sql

**Date:** December 27, 2025  
**File:** `/var/www/html/nsknbkp1/database.sql`  
**Status:** ✅ **UPDATED**

---

## 📝 Changes Made

### **Students Table Schema Updated**

Added two new columns to the `students` table:

```sql
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `admission_number` varchar(50) NOT NULL,
  `class_id` int NOT NULL,
  `status` enum('active','graduated','transferred','withdrawn') NOT NULL DEFAULT 'active',  -- NEW
  `graduation_date` date DEFAULT NULL,                                                       -- NEW
  `parent_id` int DEFAULT NULL,
  `admission_date` date NOT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Nigerian',
  `state_of_origin` varchar(50) DEFAULT NULL,
  `lga` varchar(50) DEFAULT NULL,
  `medical_conditions` text,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `admission_number` (`admission_number`),
  KEY `idx_students_class_id` (`class_id`),
  KEY `idx_students_parent_id` (`parent_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `students_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=315 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

## 🆕 New Columns

### **1. status**
- **Type:** `enum('active','graduated','transferred','withdrawn')`
- **Default:** `'active'`
- **Null:** NOT NULL
- **Purpose:** Tracks the current enrollment status of the student
- **Values:**
  - `'active'` - Currently enrolled student
  - `'graduated'` - Completed SS 3 and graduated
  - `'transferred'` - Moved to another school
  - `'withdrawn'` - Left school (dropped out, expelled, etc.)

### **2. graduation_date**
- **Type:** `date`
- **Default:** `NULL`
- **Null:** NULL allowed
- **Purpose:** Records the date when a student graduated
- **Usage:**
  - Set to `CURDATE()` when student graduates
  - Remains `NULL` for non-graduated students

---

## 📋 Documentation Added

Added comment block above the students table:

```sql
--
-- Table structure for table `students`
-- Updated: 2025-12-27 - Added status and graduation_date columns for graduation system
-- - status: enum('active','graduated','transferred','withdrawn') - Tracks student enrollment status
-- - graduation_date: date - Records when student graduated (NULL for non-graduated students)
--
```

---

## 🔄 Migration Notes

### **For Fresh Installations:**
Simply run this updated `database.sql` file to create the database with the correct schema.

### **For Existing Databases:**
The columns have already been added via ALTER TABLE commands. This file now documents the final schema for reference and future installations.

**ALTER TABLE commands used:**
```sql
ALTER TABLE students ADD COLUMN status ENUM('active','graduated','transferred','withdrawn') 
NOT NULL DEFAULT 'active' AFTER class_id;

ALTER TABLE students ADD COLUMN graduation_date DATE NULL AFTER status;
```

---

## ✅ Verification

The schema has been updated and matches the live database schema:

```
mysql> DESCRIBE students;
+-------------------------+------------------------------------------------------+------+-----+-------------------+-------------------+
| Field                   | Type                                                 | Null | Key | Default           | Extra             |
+-------------------------+------------------------------------------------------+------+-----+-------------------+-------------------+
| id                      | int                                                  | NO   | PRI | NULL              | auto_increment    |
| user_id                 | int                                                  | NO   | UNI | NULL              |                   |
| student_id              | varchar(20)                                          | NO   | UNI | NULL              |                   |
| admission_number        | varchar(50)                                          | NO   | UNI | NULL              |                   |
| class_id                | int                                                  | NO   | MUL | NULL              |                   |
| status                  | enum('active','graduated','transferred','withdrawn') | NO   |     | active            |                   |
| graduation_date         | date                                                 | YES  |     | NULL              |                   |
| parent_id               | int                                                  | YES  | MUL | NULL              |                   |
| admission_date          | date                                                 | NO   |     | NULL              |                   |
| religion                | varchar(50)                                          | YES  |     | NULL              |                   |
| nationality             | varchar(50)                                          | YES  |     | Nigerian          |                   |
| state_of_origin         | varchar(50)                                          | YES  |     | NULL              |                   |
| lga                     | varchar(50)                                          | YES  |     | NULL              |                   |
| medical_conditions      | text                                                 | YES  |     | NULL              |                   |
| emergency_contact_name  | varchar(100)                                         | YES  |     | NULL              |                   |
| emergency_contact_phone | varchar(20)                                          | YES  |     | NULL              |                   |
| created_at              | timestamp                                            | YES  |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
+-------------------------+------------------------------------------------------+------+-----+-------------------+-------------------+
```

✅ **Live Database Schema:** Matches  
✅ **database.sql File:** Updated  
✅ **Documentation:** Complete

---

## 🎯 Purpose

These schema changes support the **Student Graduation System**, which:

1. Automatically graduates SS 3 students during promotion
2. Filters graduated students from active student lists
3. Maintains historical records of all students
4. Tracks different student statuses (active, graduated, transferred, withdrawn)
5. Records graduation dates for reporting and compliance

---

## 📚 Related Documentation

- **`GRADUATION_SYSTEM.md`** - Complete technical guide
- **`GRADUATION_FEATURE_SUMMARY.md`** - Overview and workflow
- **`STUDENT_LISTING_UPDATE.md`** - Student page updates

All available in: `/var/www/html/nsknbkp1/.agent/`

---

**Updated By:** Antigravity AI Assistant  
**Date:** December 27, 2025  
**Status:** Complete ✅
