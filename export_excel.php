<?php
include 'config.php';

try {
    // Set appropriate headers for Excel file
    $fileName = 'timesheet_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"" . htmlspecialchars($fileName) . "\"");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Query data
    $result = $conn->query("SELECT person_name, work_date, week_ending, project, task, hours, remarks, type, created_at 
                           FROM timesheets 
                           ORDER BY week_ending DESC, work_date ASC");

    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    // Start output to CSV (Excel compatible)
    $output = fopen("php://output", "w");
    if (!$output) {
        throw new Exception("Failed to open output stream");
    }

    // Set UTF-8 BOM for Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Write header row
    fputcsv($output, ['Person Name', 'Date', 'Week Ending', 'Project', 'Task', 'Hours', 'Remarks', 'Type', 'Created At'], ',');

    // Fetch and write data rows
    $rowCount = 0;
    while ($row = $result->fetch_assoc()) {
        $data = [
            htmlspecialchars($row['person_name']),
            htmlspecialchars($row['work_date']),
            htmlspecialchars($row['week_ending']),
            htmlspecialchars($row['project']),
            htmlspecialchars($row['task']),
            $row['hours'],
            htmlspecialchars($row['remarks']),
            htmlspecialchars($row['type']),
            htmlspecialchars($row['created_at'])
        ];
        fputcsv($output, $data, ',');
        $rowCount++;
    }

    fclose($output);
    
    // Log the export
    error_log("Timesheet exported successfully. Rows exported: $rowCount");
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    // If headers already sent, output error message
    if (!headers_sent()) {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Export failed: " . htmlspecialchars($e->getMessage());
    }
}

$conn->close();
exit;
?>
