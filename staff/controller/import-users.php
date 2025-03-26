<?php
session_start();
require '../../vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $fileName = $_FILES['file']['tmp_name'];
    $fileType = $_FILES['file']['type'];

    
    if (!in_array($fileType, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
        $_SESSION['status'] = "Invalid file type. Please upload an Excel file.";
        $_SESSION['status_code'] = "error";
        header('Location: ../s_user.php');
        exit(0);
    }

    try {
        
        $spreadsheet = IOFactory::load($fileName);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $conn = new mysqli('localhost', 'root', '', 'ucheque');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        
        $stmt = $conn->prepare("
            INSERT INTO employee (employeeId, lastName, firstName, emailAddress, password, department, profilePicture)
            VALUES (?, ?, ?, ?, ?, ?,?)
        ");

        
        $roleStmt = $conn->prepare("
            INSERT INTO employee_role (userId, role_id)
            VALUES (?, ?)
        ");
        
        $departments = [
            1 => 'Information Technology',
            2 => 'Technology Communication Management',
            3 => 'Computer Science',
            4 => 'Data Science'
        ];

        
        $facultyRoleId = 2;
        $missingData = false;
        

        //TARGET SPECIFIC COLUMN
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; 

            $facultyId = trim($row[0]);  
            $lastName = trim($row[1]);   
            $firstName = trim($row[2]); 
            $email = trim($row[3]);     
            $departmentName = trim($row[4]);

            if (empty($facultyId) || empty($lastName) || empty($firstName) || empty($email) || empty($departmentName)) {
                $missingData = true; 
                break; 
            }

            $departmentId = array_search($departmentName, $departments);
            if ($departmentId === false) { 
                
                $departmentId = 1; 
            }

            
            $password = $lastName . $facultyId;
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $defaultPicture = file_get_contents('../assets/images/user.jpg');

            $checkStmt = $conn->prepare("SELECT * FROM employee WHERE employeeId = ?");
            $checkStmt->bind_param('s', $facultyId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows === 0) {
                
                $stmt->bind_param(
                    'sssssis',
                    $facultyId, 
                    $lastName, 
                    $firstName, 
                    $email, 
                    $hashedPassword,  
                    $departmentId,
                    $defaultPicture
                );
                $stmt->execute();

                
                $userId = $stmt->insert_id;

                
                $roleStmt->bind_param('ii', $userId, $facultyRoleId);
                $roleStmt->execute();
            }
            $checkStmt->close();
        }

        
        if ($missingData) {
            $_SESSION['status'] = "Data successfully imported.";
            $_SESSION['status_code'] = "success";
        } else {
            $_SESSION['status'] = "The file is missing required data.";
            $_SESSION['status_code'] = "error";
        }

        
        $stmt->close();
        $roleStmt->close();
        $conn->close();

        header('Location: ../s_user.php');
        exit(0);

    } catch (Exception $e) {
        $_SESSION['status'] = "Error: " . $e->getMessage();
        $_SESSION['status_code'] = "error";
        header('Location: ../s_user.php');
        exit(0);
    }
}
?>
