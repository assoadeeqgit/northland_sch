# Subject Categorization System - Simplified

## Overview
The Northland Schools Kano system uses a simplified two-level classification for subjects based on the Nigerian secondary school system:

### 1. Subject Category (Stream)
This is defined at the **subject level** and represents the academic stream.

**Location:** `subjects` table → `category` field

**Purpose:** Classify subjects into the three main academic streams in Nigerian secondary schools.

**Available Categories:**
- **Science** - Mathematics, Biology, Chemistry, Physics, Agricultural Science, etc.
- **Arts** - Languages (English, French, etc.), Social Sciences (Geography, History), Religious Studies, Creative & Performing Arts
- **Commerce** - Economics, Accounting, Commerce, Business Studies, etc.

### 2. Subject Requirement Type (Compulsory/Elective)
This is defined at the **class assignment level** and determines if a subject is required or optional for a specific class.

**Location:** `class_subjects` table → `is_compulsory` field

**Purpose:** Define whether a subject is mandatory or optional when assigned to a specific class/grade.

**Values:**
- **Compulsory (1)** - Required subject that all students in the class must take
- **Elective (0)** - Optional subject that students can choose

## Subject Stream Breakdown

### **Science Stream**
Subjects that focus on scientific and mathematical concepts:
- Mathematics
- Biology  
- Chemistry
- Physics
- Further Mathematics (if offered)
- Agricultural Science (if offered)
- Computer Science/ICT

### **Arts Stream**  
Subjects in humanities, languages, and creative studies:
- **Languages:** English Language, French, Arabic, etc.
- **Social Sciences:** Geography, History, Government, Civics
- **Religious Studies:** Christian Religious Studies, Islamic Religious Studies
- **Creative Arts:** Music, Fine Arts, Drama, Literature

### **Commerce Stream**
Subjects related to business and financial studies:
- Economics
- Accounting  
- Commerce
- Business Studies
- Financial Accounting
- Office Practice (if offered)

## Current Subject Distribution

| Category | Subjects |
|----------|----------|
| **Science** | Mathematics, Biology, Chemistry, Physics |
| **Arts** | English Language, French, Geography, History, Christian Religious Studies, Islamic Religious Studies, Music, Dancing |
| **Commerce** | *(To be added as needed)* |

## Examples

### Example 1: Mathematics (Science Stream)
- **Subject Level:**
  - Name: Mathematics
  - Code: MAT
  - Category: **Science**
  
- **Class Assignment Level:**
  - JSS1-3: **Compulsory** (All students must take it)
  - SS1-3: **Compulsory** for Science stream students, may be Elective for others

### Example 2: Geography (Arts Stream)
- **Subject Level:**
  - Name: Geography
  - Code: GEO
  - Category: **Arts**
  
- **Class Assignment Level:**
  - When assigned to JSS2: **Compulsory** (Required)
  - When assigned to SS1: **Elective** (Students choose Arts subjects)

### Example 3: Economics (Commerce Stream - Future)
- **Subject Level:**
  - Name: Economics
  - Code: ECON
  - Category: **Commerce**
  
- **Class Assignment Level:**
  - When assigned to SS1: **Compulsory** for Commerce students
  - May be **Elective** for Science/Arts students

## Why This Structure?

This simplified three-stream system:

1. **Aligns with WAEC/NECO** - Matches the standard Nigerian secondary school subject grouping
2. **Student Streaming** - Helps students choose their academic path (Science, Arts, or Commercial)
3. **Timetable Organization** - Easier to schedule subjects by stream
4. **Career Guidance** - Clear pathway for university admissions and career choices

## Implementation Notes

- **Creating a Subject:** Select category as Science, Arts, or Commerce
- **Assigning to Class:** Specify whether it's Compulsory or Elective for that specific class
- **Break Times:** No category assigned (NULL)

## Database Schema

```sql
-- Subjects table (defines the subject itself)
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    category VARCHAR(100),  -- Stream: Science, Arts, or Commerce
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Class Subjects table (assigns subjects to classes)
CREATE TABLE class_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    is_compulsory TINYINT(1) DEFAULT 1,  -- 1 = Compulsory, 0 = Elective
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
);
```

## UI Workflow

1. **Admin creates subject** → Selects stream: Science, Arts, or Commerce
2. **Admin assigns subject to class** → Marks as Compulsory or Elective
3. **Students see subjects** → Grouped by stream with clear requirements

## Subject Stream Guidelines

**What goes in each category:**

| Stream | Subject Examples |
|--------|-----------------|
| **Science** | Math, Biology, Chemistry, Physics, Agric Science, Further Math |
| **Arts** | English, Literature, French, History, Geography, Government, CRS, IRS, Music, Fine Arts |
| **Commerce** | Economics, Accounting, Commerce, Business Studies, Financial Accounting |

---

**Last Updated:** 2025-11-28  
**Version:** 2.0 (Simplified)
