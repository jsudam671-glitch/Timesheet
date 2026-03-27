<?php 
session_start();
include 'config.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get alert messages from session
$alertMessage = '';
$alertType = '';
if (isset($_SESSION['alert_message'])) {
    $alertMessage = $_SESSION['alert_message'];
    $alertType = $_SESSION['alert_type'];
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Timesheet Entry System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <?php if ($alertMessage): ?>
                <div class="alert alert-<?php echo htmlspecialchars($alertType); ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $alertType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($alertMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-body">
                    <h3 class="mb-4">
                        <i class="bi bi-clock-history"></i> Timesheet Entry
                    </h3>

                    <form method="POST" action="save.php" id="timesheetForm" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-person"></i> Person Name
                            </label>
                            <input type="text" name="person_name" id="personName" class="form-control" placeholder="Enter your full name" required 
                                   minlength="2" maxlength="100" pattern="[a-zA-Z\s]+">
                            <div class="invalid-feedback">Please enter a valid name (letters and spaces only).</div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="timesheetTable">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Date</th>
                                        <th>Week Ending</th>
                                        <th>Project</th>
                                        <th style="width:250px;">Task</th>
                                        <th style="width:90px;">Hours</th>
                                        <th style="width:220px;">Remarks</th>
                                        <th>Type</th>
                                        <th style="width:50px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                </tbody>
                            </table>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                            <button type="button" class="btn btn-secondary" id="addRowBtn">
                                <i class="bi bi-plus-circle"></i> Add Row
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-save"></i> Save Timesheet
                            </button>
                            <a href="export_excel.php" class="btn btn-success">
                                <i class="bi bi-file-earmark-excel"></i> Export to Excel
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Project list
const PROJECTS = ['PCPL', 'AIMS', 'VMS Aquachill', 'LES', 'Coinworld', 'Rajgors'];
const TYPES = ['Development', 'Support', 'Testing'];
const MAX_HOURS_PER_DAY = 8;

// Add Row
document.getElementById('addRowBtn').addEventListener('click', addRow);
document.getElementById('submitBtn').addEventListener('click', validateForm);

function addRow() {
    const tbody = document.getElementById('tableBody');
    const row = document.createElement('tr');
    const rowIndex = tbody.children.length;

    row.innerHTML = `
        <td>
            <input type="date" name="work_date[]" class="form-control" max="${new Date().toISOString().split('T')[0]}" required>
        </td>
        <td>
            <input type="date" name="week_ending[]" class="form-control" readonly>
        </td>
        <td>
            <select name="project[]" class="form-select" required>
                <option value="">Select Project</option>
                ${PROJECTS.map(p => `<option value="${p}">${p}</option>`).join('')}
            </select>
        </td>
        <td>
            <textarea name="task[]" class="form-control" rows="2" maxlength="500" required></textarea>
        </td>
        <td>
            <input type="number" step="0.5" min="0" max="${MAX_HOURS_PER_DAY}" name="hours[]" class="form-control" required>
        </td>
        <td>
            <textarea name="remarks[]" class="form-control" rows="2" maxlength="500"></textarea>
        </td>
        <td>
            <select name="type[]" class="form-select" required>
                ${TYPES.map((t, i) => `<option value="${t}" ${i === 1 ? 'selected' : ''}>${t}</option>`).join('')}
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    // Add event listeners
    row.querySelector('input[name="work_date[]"]').addEventListener('change', (e) => calculateWeekEnding(e.target));
    row.querySelector('input[name="hours[]"]').addEventListener('change', (e) => validateHours(e.target));

    tbody.appendChild(row);
}

function removeRow(button) {
    button.closest('tr').remove();
}

function calculateWeekEnding(input) {
    if (!input.value) return;

    const date = new Date(input.value);
    const day = date.getDay();
    const diff = 7 - day;
    date.setDate(date.getDate() + diff);

    const weekEnding = date.toISOString().split('T')[0];
    input.closest("tr").querySelector("input[name='week_ending[]']").value = weekEnding;
}

function validateHours(input) {
    if (parseFloat(input.value) > MAX_HOURS_PER_DAY) {
        alert(`Cannot enter more than ${MAX_HOURS_PER_DAY} hours per day!`);
        input.value = MAX_HOURS_PER_DAY;
    }
}

function validateForm(e) {
    const form = document.getElementById('timesheetForm');
    
    // Check if table has rows
    const tbody = document.getElementById('tableBody');
    if (tbody.children.length === 0) {
        alert('Please add at least one row to the timesheet.');
        e.preventDefault();
        return false;
    }

    // Bootstrap form validation
    if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
        form.classList.add('was-validated');
        return false;
    }

    // Custom validation
    let isValid = true;
    const hoursInputs = form.querySelectorAll('input[name="hours[]"]');
    hoursInputs.forEach(input => {
        const hours = parseFloat(input.value) || 0;
        if (hours < 0 || hours > MAX_HOURS_PER_DAY) {
            alert(`Invalid hours value: ${hours}`);
            isValid = false;
        }
    });

    if (!isValid) {
        e.preventDefault();
        return false;
    }

    form.classList.add('was-validated');
}
</script>

</body>
</html>>