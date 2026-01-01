# Graduating Students Filter - Implementation Plan

## Summary
Add a graduating students filter to the students management page to view students by admission session and expected graduation session.

---

## Database Changes ✅ COMPLETE

### Columns Added:
- `admission_session_id` INT - Links to academic_sessions table
- `expected_graduation_session_id` INT - Expected graduation session
- Indexes created for faster filtering

**Migration File:** `/database/migrations/add_student_session_tracking.sql`

---

## Implementation Steps

### Step 1: Add Filter Variables
**File:** `/dashboard/students-management.php`  
**Line:** ~175 (after $classFilter)

```php
$statusFilter = isset($_REQUEST['status_filter']) ? $_REQUEST['status_filter'] : '';
$graduationFilter = isset($_REQUEST['graduation_filter']) ? $_REQUEST['graduation_filter'] : '';
```

### Step 2: Update Filter UI
**File:** `/dashboard/students-management.php`  
**Line:** ~1588 (after classFilter select)

Add this HTML after the class filter dropdown:

```html
<!-- Graduation/Status Filter -->
<select name="graduation_filter" id="graduationFilter"
    class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
    <option value="">All Students</option>
    <option value="graduating" <?= $graduationFilter == 'graduating' ? 'selected' : '' ?>>
        Graduating This Session
    </option>
    <option value="active" <?= $graduationFilter == 'active' ? 'selected' : '' ?>>
        Active Students
    </option>
    <option value="graduated" <?= $graduationFilter == 'graduated' ? selected' : '' ?>>
        Already Graduated
    </option>
</select>
```

### Step 3: Update Query Logic
**File:** `/dashboard/students-management.php`  
**Find:** WHERE clause building (around line 1000-1100)

Add to WHERE conditions:

```php
// Handle graduation filter
if (!empty($graduationFilter)) {
    if ($graduationFilter === 'graduating') {
        // Get current session
        $currentSessionStmt = $db->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
        $currentSession = $currentSessionStmt->fetchColumn();
        
        if ($currentSession) {
            $whereConditions[] = "s.expected_graduation_session_id = ?";
            $params[] = $currentSession;
        }
    } elseif ($graduationFilter === 'active') {
        $whereConditions[] = "s.status = 'active'";
    } elseif ($graduationFilter === 'graduated') {
        $whereConditions[] = "s.status = 'graduated'";
    }
}
```

### Step 4: Update Student Display
Add columns to show admission and graduation sessions in the table.

**Modify SQL SELECT:**
```sql
SELECT 
    ...existing fields...,
    adm_sess.session_name as admission_session,
    grad_sess.session_name as expected_graduation_session
FROM users u
LEFT JOIN students s ON u.id = s.user_id
LEFT JOIN academic_sessions adm_sess ON s.admission_session_id = adm_sess.id
LEFT JOIN academic_sessions grad_sess ON s.expected_graduation_session_id = grad_sess.id
...
```

### Step 5: Add to Student Card/Table
**Display in student card:**
```html
<p class="text-sm text-gray-600">
    <i class="fas fa-calendar-alt mr-1"></i>
    Joined: <?= $student['admission_session'] ?? 'N/A' ?>
</p>
<p class="text-sm text-gray-600">
    <i class="fas fa-graduation-cap mr-1"></i>
    Graduating: <?= $student['expected_graduation_session'] ?? 'Not Set' ?>
</p>
```

---

## Usage Examples

### View Graduating Students:
1. Go to Students Management
2. Select "Graduating This Session" from filter
3. Click "Filter"
4. **Shows:** All students whose `expected_graduation_session_id` matches current session

### View by Admission Session:
**Future enhancement:**
Add another dropdown for "Admission Session" to filter by year joined:
```html
<select name="admission_session_filter">
    <option value="">All Sessions</option>
    <?php foreach ($academic_sessions as $session): ?>
        <option value="<?= $session['id'] ?>">
            <?= $session['session_name'] ?>
        </option>
    <?php endforeach; ?>
</select>
```

---

## Data Entry

### When Adding Student:
Admins should specify:
1. **Admission Session**: Session when student joined (e.g., "2020/2021")
2. **Expected Graduation**: Calculated based on:
   - Nursery/Primary: +3-6 years
   - Secondary: +6 years
   - Can be manually set

### Auto-Calculate Graduation Session:
```php
// In addStudent function
$admissionSessionId = $_POST['admission_session_id'];

// Get class level to determine duration
$classStmt = $db->prepare("SELECT class_level FROM classes WHERE id = ?");
$classStmt->execute([$classId]);
$classLevel = $classStmt->fetchColumn();

// Calculate expected graduation year
$yearsToGraduation = 0;
if (strpos($classLevel, 'Nursery') !== false) {
    $yearsToGraduation = 3;
} elseif (strpos($classLevel, 'Primary') !== false) {
    $yearsToGraduation = 6;
} elseif (strpos($classLevel, 'Secondary') !== false || strpos($classLevel, 'SS') !== false) {
    $yearsToGraduation = 6;
}

// Find graduation session
$gradSessionStmt = $db->prepare("
    SELECT id FROM academic_sessions 
    WHERE SUBSTRING(session_name, 1, 4) >= SUBSTRING((SELECT session_name FROM academic_sessions WHERE id = ?), 1, 4) + ?
    ORDER BY session_name ASC LIMIT 1
");
$gradSessionStmt->execute([$admissionSessionId, $yearsToGraduation]);
$expectedGradSessionId = $gradSessionStmt->fetchColumn();
```

---

## Filter Combinations

| Class Filter | Graduation Filter | Result |
|-------------|------------------|---------|
| All Classes | Graduating This Session | All graduating students |
| Primary 6 | Graduating This Session | Primary 6 students graduating |
| All Classes | Active Students | All active students |
| SS 3 | Graduating This Session | SS 3 graduating this session |

---

## Benefits

1. ✅ **Track Graduating Class** - Know who's leaving
2. ✅ **Plan Resources** - Prepare for new admissions
3. ✅ **Alumni Management** - Track graduated students
4. ✅ **Session Tracking** - Know when each student joined
5. ✅ **Reports** - Generate graduation lists

---

## Files to Modify

1. ✅ `/database/migrations/add_student_session_tracking.sql` (DONE)
2. `/dashboard/students-management.php` (needs updates):
   - Add filter variables (~line 175)
   - Add dropdown UI (~line 1588)
   - Update WHERE clause (~line 1000-1100)
   - Update SELECT query (add session joins)
   - Display session info in cards/table

---

## Status

**Database Migration:** ✅ COMPLETE  
**UI Changes:** ⏳ PENDING  
**Query Updates:** ⏳ PENDING  
**Display Updates:** ⏳ PENDING

---

**Would you like me to proceed with implementing the UI and query changes?**
