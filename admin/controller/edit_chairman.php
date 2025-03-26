<?php
include '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['chairman_id']) || !isset($_POST['chairman_name']) || 
        empty($_POST['chairman_id']) || empty($_POST['chairman_name'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Chairman ID and name are required'
        ]);
        exit;
    }

    $chairmanId = mysqli_real_escape_string($con, $_POST['chairman_id']);
    $chairmanName = mysqli_real_escape_string($con, $_POST['chairman_name']);
    
    $sql = "UPDATE chairmans SET chairman_name = '$chairmanName' WHERE chairman_id = '$chairmanId'";
    
    if (mysqli_query($con, $sql)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Chairman updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update Chairman: ' . mysqli_error($con)
        ]);
    }
}

mysqli_close($con);
?>