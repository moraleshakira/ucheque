<?php
session_start();
require '../config/config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['file_id']) && is_numeric($_GET['file_id'])) {
    $file_id = intval($_GET['file_id']);

    $query = "SELECT `filePath` FROM `dtr_extracted_data` WHERE `id` = ?";
    $stmt = $con->prepare($query);

    if (!$stmt) {
        die("Error preparing the query: " . $con->error);
    }

    $stmt->bind_param('i', $file_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($filePath);

    if ($stmt->fetch()) {
        $stmt->close();

        $uploadsDir = realpath('../../uploads/');
        $fullFilePath = $uploadsDir . DIRECTORY_SEPARATOR . basename($filePath);

        // List of allowed file extensions
        $allowedExtensions = ['pdf', 'docx', 'xlsx']; // Add other allowed extensions if needed
        $fileExtension = pathinfo($fullFilePath, PATHINFO_EXTENSION);

        if (file_exists($fullFilePath) && strpos($fullFilePath, $uploadsDir) === 0 && in_array($fileExtension, $allowedExtensions)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($fullFilePath));
            header('Pragma: public');
            ob_end_clean(); //when extension is corrupted

            readfile($fullFilePath);
            exit;
        } else {
            $_SESSION['status'] = "File not found, invalid file path, or invalid file extension.";
            $_SESSION['status_code'] = "error";
        }
    } else {
        $_SESSION['status'] = "Invalid file ID.";
        $_SESSION['status_code'] = "error";
        $stmt->close();
    }
} else {
    $_SESSION['status'] = "Missing or invalid file ID parameter.";
    $_SESSION['status_code'] = "error";
}

header('Location: ../f_dtr.php');
exit;
?>
