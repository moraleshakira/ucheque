<?php
include '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['hr_id']) || !isset($_POST['hr_name']) || 
        empty($_POST['hr_id']) || empty($_POST['hr_name'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'HR Personnel ID and name are required'
        ]);
        exit;
    }

    $hrId = mysqli_real_escape_string($con, $_POST['hr_id']);
    $hrName = mysqli_real_escape_string($con, $_POST['hr_name']);
    
    $sql = "UPDATE hr_personnel SET hr_name = '$hrName' WHERE hr_id = '$hrId'";
    
    if (mysqli_query($con, $sql)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'HR Personnel updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update HR Personnel: ' . mysqli_error($con)
        ]);
    }
}

mysqli_close($con);
?>
