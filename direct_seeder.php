<?php
// This is a direct database seeder that can be run without login
// It will populate your restaurant management system with sample data

// Check if seeder has already been run
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Include the database connection
    require_once 'config/database.php';
    
    // First check if the database exists, if not create it
    $create_db_conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    if (!$create_db_conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if (mysqli_query($create_db_conn, $sql)) {
        echo "<p>Database created successfully or already exists.</p>";
    } else {
        echo "<p>Error creating database: " . mysqli_error($create_db_conn) . "</p>";
    }
    mysqli_close($create_db_conn);
    
    // Now run the seeder script
    // We're just including the admin/seed_data.php which has been modified to not require login
    include_once 'admin/seed_data.php';
    
    exit;
}

// HTML for the confirmation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seeder - Restaurant Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 40px 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #28a745;
            margin-bottom: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Restaurant Management System - Database Seeder</h1>
        
        <div class="alert alert-warning">
            <strong>Warning!</strong> This script will populate your database with sample data. It will:
            <ul>
                <li>Create tables if they don't exist</li>
                <li>Add sample customers, employees, menu items, ingredients, etc.</li>
                <li>Create sample orders and reservations</li>
                <li>Create an admin user (username: admin, password: admin123)</li>
            </ul>
            <p>This is intended for demonstration or testing purposes.</p>
        </div>
        
        <p>Before proceeding, make sure:</p>
        <ol>
            <li>You have configured your database connection in config/database.php</li>
            <li>You understand this will add multiple records to your database</li>
        </ol>
        
        <div class="mt-4">
            <a href="direct_seeder.php?confirm=yes" class="btn btn-success btn-lg">Seed Database</a>
            <a href="index.php" class="btn btn-danger btn-lg ml-2">Cancel</a>
        </div>
    </div>
</body>
</html> 