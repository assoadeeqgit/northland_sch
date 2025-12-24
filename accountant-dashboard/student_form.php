<?php 
require_once '../auth-check.php';
checkAuth('accountant');
include '../includes/header.php'; 
?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="margin-bottom: 20px;">
        <a href="students.php" style="text-decoration: none; color: var(--text-light); font-size: 0.9rem; margin-bottom: 10px; display: inline-block;">&larr; Back to Students</a>
        <h1 class="page-title">Register New Student</h1>
    </div>

    <div style="max-width: 800px; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-top: 5px solid var(--brand-navy);">
        
        <form action="save_student.php" method="POST">
            <h4 style="color: var(--brand-navy); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px;">Personal Information</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">First Name</label>
                    <input type="text" name="first_name" class="form-control" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Gender</label>
                    <select name="gender" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>

            <h4 style="color: var(--brand-navy); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px; margin-top: 40px;">Academic Details</h4>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Admission Number</label>
                    <input type="text" name="admission_number" class="form-control" placeholder="e.g. ADM/24/..." required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Enrollment Date</label>
                    <input type="date" name="enrollment_date" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Class</label>
                    <select name="class_name" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <option value="JSS 1">JSS 1</option>
                        <option value="JSS 2">JSS 2</option>
                        <option value="JSS 3">JSS 3</option>
                        <option value="SS 1">SS 1</option>
                        <option value="SS 2">SS 2</option>
                        <option value="SS 3">SS 3</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Section / Arm</label>
                    <select name="section" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="Science">Science</option>
                        <option value="Art">Art</option>
                        <option value="Commercial">Commercial</option>
                    </select>
                </div>
            </div>

            <h4 style="color: var(--brand-navy); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px; margin-top: 40px;">Guardian Information</h4>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Guardian Name</label>
                    <input type="text" name="guardian_name" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Relationship</label>
                    <input type="text" name="relationship" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
            </div>

             <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 35px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Phone Number</label>
                    <input type="text" name="parent_phone" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Email Address</label>
                    <input type="email" name="guardian_email" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 25px;">
                <button type="button" class="btn" style="color: var(--text-light); background: #f3f4f6;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding-left: 30px; padding-right: 30px;">Register Student</button>
            </div>

        </form>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
