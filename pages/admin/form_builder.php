<?php
/**
 * Custom Form Builder
 * Create custom forms for assessments, surveys, intake forms
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('admin');

$page_title = 'Form Builder';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Breadcrumb
$breadcrumb_items = [
    ['label' => 'Home', 'url' => '/nov10-crisis/'],
    ['label' => 'Admin', 'url' => '/nov10-crisis/pages/admin/dashboard.php'],
    ['label' => 'Form Builder', 'url' => '']
];

// Handle form creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_form'])) {
    $form_name = sanitize_input($_POST['form_name']);
    $form_description = sanitize_input($_POST['form_description'] ?? '');
    $form_type = sanitize_input($_POST['form_type']);
    $fields = json_encode($_POST['fields'] ?? []);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO custom_forms (form_name, form_description, form_type, fields, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $form_name, $form_description, $form_type, $fields, $is_active, $user_id);
    
    if ($stmt->execute()) {
        $success = "Form created successfully!";
    } else {
        $error = "Failed to create form";
    }
    $stmt->close();
}

// Get existing forms
$stmt = $conn->prepare("SELECT cf.*, u.full_name as creator_name FROM custom_forms cf LEFT JOIN users u ON cf.created_by = u.user_id ORDER BY cf.created_at DESC");
$stmt->execute();
$forms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../../includes/header.php';
?>

<style>
.form-builder-container {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
}

.field-palette {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--shadow-sm);
    height: fit-content;
    position: sticky;
    top: 20px;
}

.field-type {
    background: var(--bg-secondary);
    padding: 12px;
    margin-bottom: 8px;
    border-radius: 6px;
    cursor: grab;
    transition: var(--transition-fast);
    display: flex;
    align-items: center;
    gap: 8px;
}

.field-type:hover {
    background: var(--primary-color);
    color: white;
}

.canvas {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: 24px;
    box-shadow: var(--shadow-sm);
    min-height: 600px;
}

.field-item {
    background: var(--bg-secondary);
    border: 2px dashed var(--border-color);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
    position: relative;
}

.field-controls {
    display: flex;
    gap: 8px;
    position: absolute;
    top: 8px;
    right: 8px;
}

.field-preview {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border-color);
}

.form-list {
    display: grid;
    gap: 16px;
}

.form-card {
    background: var(--bg-secondary);
    border-left: 4px solid var(--primary-color);
    border-radius: var(--border-radius);
    padding: 20px;
}

.form-card.inactive {
    opacity: 0.6;
    border-left-color: var(--text-secondary);
}

.form-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}
</style>

<div class="container-fluid">
    <h1>📝 Custom Form Builder</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="mb-4">
        <button class="btn btn-primary" onclick="showBuilder()">➕ Create New Form</button>
    </div>
    
    <!-- Form Builder Interface (Hidden by default) -->
    <div id="formBuilder" style="display: none;">
        <form method="POST">
            <div class="mb-3">
                <label>Form Name *</label>
                <input type="text" name="form_name" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label>Description</label>
                <textarea name="form_description" class="form-control" rows="2"></textarea>
            </div>
            
            <div class="mb-3">
                <label>Form Type *</label>
                <select name="form_type" class="form-control" required>
                    <option value="assessment">Assessment</option>
                    <option value="survey">Survey</option>
                    <option value="intake">Intake Form</option>
                    <option value="feedback">Feedback</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-builder-container">
                <!-- Field Palette -->
                <div class="field-palette">
                    <h3>Field Types</h3>
                    <p>Click to add fields:</p>
                    
                    <div class="field-type" onclick="addField('text')">
                        📝 Text Input
                    </div>
                    <div class="field-type" onclick="addField('textarea')">
                        📄 Text Area
                    </div>
                    <div class="field-type" onclick="addField('number')">
                        🔢 Number Input
                    </div>
                    <div class="field-type" onclick="addField('email')">
                        ✉️ Email Input
                    </div>
                    <div class="field-type" onclick="addField('phone')">
                        📞 Phone Input
                    </div>
                    <div class="field-type" onclick="addField('date')">
                        📅 Date Picker
                    </div>
                    <div class="field-type" onclick="addField('select')">
                        📋 Dropdown
                    </div>
                    <div class="field-type" onclick="addField('radio')">
                        ⭕ Radio Buttons
                    </div>
                    <div class="field-type" onclick="addField('checkbox')">
                        ☑️ Checkbox
                    </div>
                    <div class="field-type" onclick="addField('rating')">
                        ⭐ Rating Scale
                    </div>
                    <div class="field-type" onclick="addField('file')">
                        📎 File Upload
                    </div>
                    <div class="field-type" onclick="addField('heading')">
                        🔤 Section Heading
                    </div>
                </div>
                
                <!-- Canvas -->
                <div class="canvas" id="formCanvas">
                    <h3>Form Preview</h3>
                    <p>Click field types on the left to add them to your form.</p>
                    <div id="fieldsContainer"></div>
                </div>
            </div>
            
            <div class="mt-3">
                <label>
                    <input type="checkbox" name="is_active" checked> Activate form immediately
                </label>
            </div>
            
            <div class="mt-3">
                <button type="submit" name="create_form" class="btn btn-primary">💾 Save Form</button>
                <button type="button" class="btn btn-light" onclick="hideBuilder()">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Existing Forms -->
    <div class="form-list">
        <h2>Existing Forms</h2>
        
        <?php if (empty($forms)): ?>
            <p>No custom forms created yet.</p>
        <?php else: ?>
            <?php foreach ($forms as $form): ?>
                <div class="form-card <?php echo $form['is_active'] ? '' : 'inactive'; ?>">
                    <h3>
                        <?php echo $form['is_active'] ? '✅' : '⏸️'; ?>
                        <?php echo htmlspecialchars($form['form_name']); ?>
                    </h3>
                    <p><?php echo htmlspecialchars($form['form_description']); ?></p>
                    <div>
                        <strong>Type:</strong> <?php echo ucfirst($form['form_type']); ?><br>
                        <strong>Fields:</strong> <?php echo count(json_decode($form['fields'] ?? '[]', true)); ?><br>
                        <strong>Created:</strong> <?php echo date('M d, Y', strtotime($form['created_at'])); ?> by <?php echo htmlspecialchars($form['creator_name']); ?>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-sm btn-primary">✏️ Edit</button>
                        <button class="btn btn-sm btn-light">👁️ Preview</button>
                        <button class="btn btn-sm btn-<?php echo $form['is_active'] ? 'warning' : 'success'; ?>">
                            <?php echo $form['is_active'] ? '⏸️ Deactivate' : '▶️ Activate'; ?>
                        </button>
                        <button class="btn btn-sm btn-danger">🗑️ Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
let fieldCounter = 0;

function showBuilder() {
    document.getElementById('formBuilder').style.display = 'block';
    document.getElementById('formBuilder').scrollIntoView({ behavior: 'smooth' });
}

function hideBuilder() {
    document.getElementById('formBuilder').style.display = 'none';
    document.getElementById('fieldsContainer').innerHTML = '';
    fieldCounter = 0;
}

function addField(type) {
    const container = document.getElementById('fieldsContainer');
    const fieldId = 'field_' + fieldCounter++;
    
    const fieldDiv = document.createElement('div');
    fieldDiv.className = 'field-item';
    fieldDiv.innerHTML = `
        <div class="field-controls">
            <button type="button" class="btn btn-sm btn-light" onclick="moveUp(this)">↑</button>
            <button type="button" class="btn btn-sm btn-light" onclick="moveDown(this)">↓</button>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeField(this)">×</button>
        </div>
        
        <input type="hidden" name="fields[${fieldCounter}][type]" value="${type}">
        
        <div class="mb-2">
            <label>Field Label</label>
            <input type="text" name="fields[${fieldCounter}][label]" class="form-control form-control-sm" 
                   value="${type.charAt(0).toUpperCase() + type.slice(1)} Field" required>
        </div>
        
        <div class="mb-2">
            <label>Field Name (used for database)</label>
            <input type="text" name="fields[${fieldCounter}][name]" class="form-control form-control-sm" 
                   value="${type}_${fieldCounter}" required>
        </div>
        
        <div class="mb-2">
            <label>
                <input type="checkbox" name="fields[${fieldCounter}][required]" value="1"> Required field
            </label>
        </div>
        
        ${type === 'select' || type === 'radio' ? `
            <div class="mb-2">
                <label>Options (one per line)</label>
                <textarea name="fields[${fieldCounter}][options]" class="form-control form-control-sm" rows="3">Option 1
Option 2
Option 3</textarea>
            </div>
        ` : ''}
        
        ${type === 'rating' ? `
            <div class="mb-2">
                <label>Max Rating</label>
                <input type="number" name="fields[${fieldCounter}][max]" class="form-control form-control-sm" value="5" min="1" max="10">
            </div>
        ` : ''}
        
        <div class="field-preview">
            <strong>Preview:</strong>
            ${getFieldPreview(type)}
        </div>
    `;
    
    container.appendChild(fieldDiv);
}

function getFieldPreview(type) {
    switch(type) {
        case 'text':
            return '<input type="text" class="form-control" placeholder="Text input">';
        case 'textarea':
            return '<textarea class="form-control" rows="3" placeholder="Text area"></textarea>';
        case 'number':
            return '<input type="number" class="form-control" placeholder="Number">';
        case 'email':
            return '<input type="email" class="form-control" placeholder="email@example.com">';
        case 'phone':
            return '<input type="tel" class="form-control" placeholder="(555) 123-4567">';
        case 'date':
            return '<input type="date" class="form-control">';
        case 'select':
            return '<select class="form-control"><option>Option 1</option><option>Option 2</option></select>';
        case 'radio':
            return '<div><label><input type="radio" name="preview"> Option 1</label> <label><input type="radio" name="preview"> Option 2</label></div>';
        case 'checkbox':
            return '<label><input type="checkbox"> Checkbox option</label>';
        case 'rating':
            return '<div>⭐⭐⭐⭐⭐</div>';
        case 'file':
            return '<input type="file" class="form-control">';
        case 'heading':
            return '<h4>Section Heading</h4>';
        default:
            return '<p>Field preview</p>';
    }
}

function moveUp(btn) {
    const fieldItem = btn.closest('.field-item');
    const prev = fieldItem.previousElementSibling;
    if (prev) {
        fieldItem.parentNode.insertBefore(fieldItem, prev);
    }
}

function moveDown(btn) {
    const fieldItem = btn.closest('.field-item');
    const next = fieldItem.nextElementSibling;
    if (next) {
        fieldItem.parentNode.insertBefore(next, fieldItem);
    }
}

function removeField(btn) {
    if (confirm('Remove this field?')) {
        btn.closest('.field-item').remove();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
