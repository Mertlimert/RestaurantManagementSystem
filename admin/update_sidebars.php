<?php
// Script to update all admin page sidebars to include shift types link

// Get all PHP files in the admin directory
$admin_files = glob("*.php");

// The pattern to search for (Employees link line)
$search_pattern = '<a href="employees.php"';
$search_pattern_active = '<a href="employees.php" class="active"';

// The line to add after the employees link
$shift_types_link = '        <a href="shift_types.php"><i class="fas fa-clock mr-2"></i> Shift Types</a>';

$update_count = 0;
$error_count = 0;

echo "Starting sidebar updates...\n";

foreach ($admin_files as $file) {
    // Skip this script itself
    if ($file === "update_sidebars.php" || $file === "employee_shifts.php" || $file === "shift_types.php") {
        continue;
    }
    
    // Read the file content
    $content = file_get_contents($file);
    if ($content === false) {
        echo "Error reading file: $file\n";
        $error_count++;
        continue;
    }
    
    // Check if shift_types link already exists to avoid duplicates
    if (strpos($content, 'shift_types.php') !== false) {
        echo "Shift types link already exists in: $file\n";
        continue;
    }
    
    // Check if the employees link exists in the file
    if (strpos($content, $search_pattern) !== false || strpos($content, $search_pattern_active) !== false) {
        // Find the employees link line
        $lines = explode("\n", $content);
        $updated = false;
        
        for ($i = 0; $i < count($lines); $i++) {
            // If we found the employees link line
            if (strpos($lines[$i], $search_pattern) !== false || strpos($lines[$i], $search_pattern_active) !== false) {
                // Insert shift types link after employees link
                array_splice($lines, $i + 1, 0, $shift_types_link);
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            // Join the lines back into a string and save
            $new_content = implode("\n", $lines);
            if (file_put_contents($file, $new_content)) {
                echo "Successfully updated: $file\n";
                $update_count++;
            } else {
                echo "Error writing to file: $file\n";
                $error_count++;
            }
        } else {
            echo "Could not locate exact insertion point in: $file\n";
            $error_count++;
        }
    } else {
        echo "No sidebar found in: $file\n";
    }
}

echo "Update complete. Updated $update_count files. Encountered $error_count errors.\n";
?> 