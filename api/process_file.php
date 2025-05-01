<?php
// api/process_file.php
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Check if file is uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'File upload failed'
    ]);
    exit;
}

// Get file details
$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmpPath = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = $file['type'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Check file size (limit to 10MB)
if ($fileSize > 10 * 1024 * 1024) {
    echo json_encode([
        'success' => false,
        'message' => 'File size exceeds the limit (10MB)'
    ]);
    exit;
}

// Check file extension
$allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];
if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file type. Only PDF, Word, and plain text files are allowed.'
    ]);
    exit;
}

// Database connection
require_once '../config/Database.php';
require_once '../includes/GeminiAPI.php';

$database = new Database();
$db = $database->getConnection();

try {
    $content = '';
    
    // Process based on file type
    if ($fileExtension === 'txt') {
        // Plain text file
        $content = file_get_contents($fileTmpPath);
    } elseif ($fileExtension === 'pdf') {
        // PDF file
        if (!extension_loaded('pdfparser')) {
            // If PDF parser not available, use Gemini API to extract text
            $geminiAPI = new GeminiAPI($db, 'AIzaSyDFnDzuI_XtqBfiT4e43xfOaDuqDikjDnk');
            
            // Encode file content as base64
            $fileContent = base64_encode(file_get_contents($fileTmpPath));
            
            // Use Gemini to extract text
            $content = $geminiAPI->extractTextFromPDF($fileContent);
        } else {
            // Use PDF parser library if available
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($fileTmpPath);
            $content = $pdf->getText();
        }
    } elseif ($fileExtension === 'doc' || $fileExtension === 'docx') {
        // Word document
        if (extension_loaded('zip') && $fileExtension === 'docx') {
            // Extract text from DOCX using ZIP extension
            $zip = new ZipArchive();
            if ($zip->open($fileTmpPath) === true) {
                if (($index = $zip->locateName('word/document.xml')) !== false) {
                    $data = $zip->getFromIndex($index);
                    $xml = new DOMDocument();
                    $xml->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                    $content = strip_tags($xml->saveXML());
                }
                $zip->close();
            }
        } else {
            // Use Gemini API to extract text
            $geminiAPI = new GeminiAPI($db, 'YOUR_GEMINI_API_KEY');
            
            // Encode file content as base64
            $fileContent = base64_encode(file_get_contents($fileTmpPath));
            
            // Use Gemini to extract text
            $content = $geminiAPI->extractTextFromWord($fileContent);
        }
    }
    
    // Clean up the content
    $content = trim($content);
    
    if (empty($content)) {
        echo json_encode([
            'success' => false,
            'message' => 'Could not extract text from the file'
        ]);
        exit;
    }
    
    // Return the extracted content
    echo json_encode([
        'success' => true,
        'content' => $content
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing file: ' . $e->getMessage()
    ]);
}
?>