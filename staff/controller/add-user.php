<?php
include("../config/config.php");
session_start();

if (isset($_POST['addUser'])) {
    $employeeId = $_POST['employeeId'];
    $firstName = $_POST['firstName'];
    $middleName = isset($_POST['middleName']) ? $_POST['middleName'] : null;
    $lastName = $_POST['lastName'];
    $phoneNumber = isset($_POST['phoneNumber']) ? $_POST['phoneNumber'] : null;
    $emailAddress = $_POST['emailAddress'];
    $roles = isset($_POST['role']) ? $_POST['role'] : [];
    $staffRole = isset($_POST['staffRole']) ? $_POST['staffRole'] : '';

    $errorMessages = [];

    if (empty($roles)) {
        $errorMessages[] = "Role must be selected.";
    }

    $checkQuery = "SELECT * FROM `employee` WHERE `employeeId` = ? OR `phoneNumber` = ? OR `emailAddress` = ?";
    $checkStmt = mysqli_prepare($con, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "sss", $employeeId, $phoneNumber, $emailAddress);
    mysqli_stmt_execute($checkStmt);
    $result = mysqli_stmt_get_result($checkStmt);

    $checkEmployeeId = true;
    $checkPhoneNumber = false;
    $checkEmailAddress = true;

    if ($row = mysqli_fetch_assoc($result)) {
        if ($checkEmployeeId && $row['employeeId'] == $employeeId) {
            $errorMessages[] = "Employee ID already taken.";
        }
        if ($checkPhoneNumber && $row['phoneNumber'] == $phoneNumber) {
            $errorMessages[] = "Phone Number already taken.";
        }
        if ($checkEmailAddress && $row['emailAddress'] == $emailAddress) {
            $errorMessages[] = "Email Address already taken.";
        }
    }

    if (!empty($errorMessages)) {
        $_SESSION['status'] = implode('<br>', $errorMessages);
        $_SESSION['status_code'] = "error";
        header('Location: ../s_add-user.php');
        exit(0);
    }

    // Generate password
    $generatedPassword = $lastName . $employeeId;
    $hashedPassword = password_hash($generatedPassword, PASSWORD_BCRYPT);

    // Read the default profile picture as binary data
    $defaultPicturePath = '../assets/images/user.jpg';
    $defaultPictureData = file_get_contents($defaultPicturePath);

    // Insert query with binary profile picture
    $query = "INSERT INTO `employee` 
              (`employeeId`, `firstName`, `middleName`, `lastName`, `phoneNumber`, `emailAddress`, `password`, `profilePicture`) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($con, $query);
    if (!$stmt) {
        die("Query preparation failed: " . mysqli_error($con));
    }

    // Bind parameters for the insert query, with binary data for the profile picture
    mysqli_stmt_bind_param($stmt, "ssssssss", $employeeId, $firstName, $middleName, $lastName, 
    $phoneNumber, $emailAddress, $hashedPassword, $defaultPictureData);

    $execute = mysqli_stmt_execute($stmt);

    if ($execute) {
        $userId = mysqli_insert_id($con);

        // Insert roles for the user
        if (!empty($roles)) {
            foreach ($roles as $role) {
                $roleQuery = "INSERT INTO `employee_role` (`userId`, `role_id`) VALUES (?, ?)";
                $roleStmt = mysqli_prepare($con, $roleQuery);
                if (!$roleStmt) {
                    die("Role query preparation failed: " . mysqli_error($con));
                }
                mysqli_stmt_bind_param($roleStmt, "ii", $userId, $role);
                if (!mysqli_stmt_execute($roleStmt)) {
                    die("Role query execution failed: " . mysqli_error($con));
                }
            }
        }

        // Insert additional staff role if present
        if ($staffRole) {
            $staffRoleId = 4; // Example role ID for staff role
            $insertStaffRoleQuery = "INSERT INTO employee_role (userId, role_id) VALUES (?, ?)";
            $stmtStaffInsert = $con->prepare($insertStaffRoleQuery);
            $stmtStaffInsert->bind_param('ii', $userId, $staffRoleId);
            $stmtStaffInsert->execute();
            $stmtStaffInsert->close();
        }

        $_SESSION['status'] = "User has been added successfully!";
        $_SESSION['status_code'] = "success";
        header('Location: ../s_user.php');
        exit(0);
    } else {
        echo "Error: " . mysqli_error($con);
    }

    mysqli_close($con);
}
?>
