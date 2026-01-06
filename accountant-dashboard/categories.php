<?php
require_once '../auth-check.php';
checkAuth('accountant');
include '../includes/header.php';
require_once '../config/DatabaseManager.php';

$dbManager = DatabaseManager::getInstance();
$conn = $dbManager->getConnection();

$message = '';
$messageType = '';

// --- HANDLE FORM SUBMISSION ---

// Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
    $description = trim($_POST['description']);

    if (!empty($name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO expense_categories (name, parent_id, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $parent_id, $description]);
            $message = "Category added successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error adding category: " . $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = "Category Name is required.";
        $messageType = "warning";
    }
}

// Update Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $name = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
    $description = trim($_POST['description']);

    if (!empty($name) && $id) {
        try {
            // Prevent circular dependency (Parent cannot be itself)
            if ($parent_id == $id) {
                throw new Exception("A category cannot be its own parent.");
            }
            
            $stmt = $conn->prepare("UPDATE expense_categories SET name = ?, parent_id = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $parent_id, $description, $id]);
            $message = "Category updated successfully!";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Error updating category: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $id = $_POST['category_id'];
    if ($id) {
        try {
            // Check for dependencies (subcategories)
            $check = $conn->prepare("SELECT COUNT(*) FROM expense_categories WHERE parent_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                 $message = "Cannot delete: This category has subcategories. Please delete them first.";
                 $messageType = "warning";
            } else {
                $stmt = $conn->prepare("DELETE FROM expense_categories WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Category deleted successfully!";
                $messageType = "success";
            }
        } catch (PDOException $e) {
             $message = "Error deleting category: " . $e->getMessage();
             $messageType = "danger";
        }
    }
}

// --- FETCH DATA ---
$categories = $conn->query("SELECT c.*, p.name as parent_name FROM expense_categories c LEFT JOIN expense_categories p ON c.parent_id = p.id ORDER BY c.parent_id ASC, c.name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get potential parents (top-level categories)
$parents = $conn->query("SELECT * FROM expense_categories WHERE parent_id IS NULL ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="content-body" style="padding: 30px;">
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: <?php echo $messageType == 'success' ? '#d4edda' : ($messageType == 'warning' ? '#fff3cd' : '#f8d7da'); ?>; color: <?php echo $messageType == 'success' ? '#155724' : ($messageType == 'warning' ? '#856404' : '#721c24'); ?>;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="page-title-box" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 class="page-title">Expense Categories</h1>
            <p style="color: var(--text-light); margin-top: 5px;">Manage categories and subcategories for expenses.</p>
        </div>
        <button onclick="document.getElementById('addCategoryModal').style.display='block'" class="btn btn-primary"><i class="fas fa-plus" style="margin-right:8px;"></i> Add Category</button>
    </div>

    <!-- Categories List -->
    <div class="table-container" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; text-align: left;">
                    <th style="padding: 12px;">Category Name</th>
                    <th style="padding: 12px;">Type</th>
                    <th style="padding: 12px;">Parent Category</th>
                    <th style="padding: 12px;">Description</th>
                    <th style="padding: 12px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="5" style="padding: 20px; text-align: center;">No categories found.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px; font-weight: 600; color: var(--brand-navy);"><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td style="padding: 12px;">
                            <?php if ($cat['parent_id']): ?>
                                <span class="badge" style="background: #eef2ff; color: var(--nskblue); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">Subcategory</span>
                            <?php else: ?>
                                <span class="badge" style="background: #f0fdf4; color: var(--nskgreen); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">Main Category</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($cat['parent_name'] ?? '-'); ?></td>
                        <td style="padding: 12px; color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($cat['description']); ?></td>
                        <td style="padding: 12px; text-align: right;">
                            <button onclick='editCategory(<?php echo json_encode($cat); ?>)' class="btn" style="color: var(--brand-navy); padding: 6px;"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name']); ?>')" class="btn" style="color: #ef4444; padding: 6px;"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Add Modal -->
<div id="addCategoryModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 25px; border: 1px solid #888; width: 500px; border-radius: 8px;">
        <span onclick="document.getElementById('addCategoryModal').style.display='none'" style="cursor: pointer; float: right; font-size: 28px;">&times;</span>
        <h2 style="margin-top:0; margin-bottom: 20px;">Add New Category</h2>
        <form method="POST">
            <input type="hidden" name="add_category" value="1">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Category Name *</label>
                <input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Parent Category (Optional)</label>
                <select name="parent_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">None (Main Category)</option>
                    <?php foreach ($parents as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Description</label>
                <textarea name="description" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
            </div>
            <div style="text-align: right;">
                <button type="button" onclick="document.getElementById('addCategoryModal').style.display='none'" class="btn" style="margin-right: 10px; background: #f3f4f6;">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editCategoryModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 25px; border: 1px solid #888; width: 500px; border-radius: 8px;">
        <span onclick="document.getElementById('editCategoryModal').style.display='none'" style="cursor: pointer; float: right; font-size: 28px;">&times;</span>
        <h2 style="margin-top:0; margin-bottom: 20px;">Edit Category</h2>
        <form method="POST">
            <input type="hidden" name="update_category" value="1">
            <input type="hidden" name="category_id" id="edit_id">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Category Name *</label>
                <input type="text" name="name" id="edit_name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Parent Category</label>
                <select name="parent_id" id="edit_parent_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">None (Main Category)</option>
                    <?php foreach ($parents as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Description</label>
                <textarea name="description" id="edit_description" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
            </div>
            <div style="text-align: right;">
                <button type="button" onclick="document.getElementById('editCategoryModal').style.display='none'" class="btn" style="margin-right: 10px; background: #f3f4f6;">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_category" value="1">
    <input type="hidden" name="category_id" id="delete_id">
</form>

<script>
function editCategory(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_name').value = cat.name;
    document.getElementById('edit_parent_id').value = cat.parent_id || '';
    document.getElementById('edit_description').value = cat.description;
    
    // Hide Self from Parent dropdown to avoid cycle (simple UI check)
    const opts = document.getElementById('edit_parent_id').options;
    for(let i=0; i<opts.length; i++) {
        opts[i].disabled = (opts[i].value == cat.id);
    }
    
    document.getElementById('editCategoryModal').style.display = 'block';
}

function deleteCategory(id, name) {
    if(confirm('Are you sure you want to delete category "' + name + '"?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
