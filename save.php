<?php
session_start();
include 'config.php';

try {
    // Verify CSRF Token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Security validation failed. Please try again.");
    }

    // Validate POST data exists
    if (empty($_POST['person_name']) || empty($_POST['work_date'])) {
        throw new Exception("Missing required fields. Please fill in all timesheet entries.");
    }

    // Sanitize and validate person name
    $person = sanitizeInput($_POST['person_name']);
    if (strlen($person) < 2 || strlen($person) > 100) {
        throw new Exception("Invalid person name. Must be between 2 and 100 characters.");
    }

    // Validate arrays
    $dates = !empty($_POST['work_date']) ? (array)$_POST['work_date'] : [];
    $weeks = !empty($_POST['week_ending']) ? (array)$_POST['week_ending'] : [];
    $projects = !empty($_POST['project']) ? (array)$_POST['project'] : [];
    $tasks = !empty($_POST['task']) ? (array)$_POST['task'] : [];
    $hoursArr = !empty($_POST['hours']) ? (array)$_POST['hours'] : [];
    $remarksArr = !empty($_POST['remarks']) ? (array)$_POST['remarks'] : [];
    $types = !empty($_POST['type']) ? (array)$_POST['type'] : [];

    // Ensure arrays have same length
    $count = count($dates);
    if (count($weeks) !== $count || count($projects) !== $count || 
        count($tasks) !== $count || count($hoursArr) !== $count || 
        count($types) !== $count) {
        throw new Exception("Data mismatch. Please ensure all fields are filled correctly.");
    }

    if ($count === 0) {
        throw new Exception("Please add at least one timesheet entry.");
    }

    // Prepare statement
    $stmt = $conn->prepare("INSERT INTO timesheets
        (person_name, work_date, week_ending, project, task, hours, remarks, type, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $insertedCount = 0;

    // Process each row
    for ($i = 0; $i < $count; $i++) {
        // Validate and sanitize each field
        $date = sanitizeDate($dates[$i]);
        $weekEnding = sanitizeDate($weeks[$i]);
        $project = sanitizeInput($projects[$i]);
        $task = sanitizeInput($tasks[$i]);
        $hours = sanitizeNumber($hoursArr[$i]);
        $remarks = sanitizeInput($remarksArr[$i]);
        $type = sanitizeInput($types[$i]);

        // Validation checks
        if (empty($date) || empty($weekEnding) || empty($project) || empty($task) || empty($hours) || empty($type)) {
            continue; // Skip invalid rows
        }

        // Validate hours
        if ($hours < 0 || $hours > MAX_HOURS_PER_DAY) {
            error_log("Invalid hours for row $i: $hours");
            continue;
        }

        // Validate date is not in future
        if (strtotime($date) > time()) {
            error_log("Future date for row $i: $date");
            continue;
        }

        // Bind parameters
        if (!$stmt->bind_param("sssssiss", $person, $date, $weekEnding, $project, $task, $hours, $remarks, $type)) {
            throw new Exception("Binding parameters failed: " . $stmt->error);
        }

        // Execute statement
        if (!$stmt->execute()) {
            error_log("Failed to insert row $i: " . $stmt->error);
            continue;
        }

        $insertedCount++;
    }

    $stmt->close();

    if ($insertedCount === 0) {
        throw new Exception("No valid timesheet entries were saved. Please check your data.");
    }

    // Set success message
    $_SESSION['alert_message'] = "Timesheet saved successfully! ($insertedCount entries)";
    $_SESSION['alert_type'] = 'success';
    
    // Log the action
    error_log("Timesheet saved for $person with $insertedCount entries");

} catch (Exception $e) {
    $_SESSION['alert_message'] = $e->getMessage();
    $_SESSION['alert_type'] = 'danger';
    error_log("Timesheet save error: " . $e->getMessage());
}

$conn->close();
header("Location: index.php");
exit;

/**
 * Sanitize text input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    $input = strip_tags($input);
    return $input;
}

/**
 * Validate and sanitize date input
 */
function sanitizeDate($date) {
    $date = trim($date);
    if (empty($date)) {
        return null;
    }
    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return null;
    }
    // Ensure it's a valid date
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return null;
    }
    return $date;
}

/**
 * Validate and sanitize numeric input
 */
function sanitizeNumber($input) {
    $input = trim($input);
    if (empty($input)) {
        return null;
    }
    $number = floatval($input);
    return $number;
}
?>
