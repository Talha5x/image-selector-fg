<?php
/**
 * ইমেজ আপলোড API
 * POST: image (base64), name (ফাইল নেম)
 * Response: JSON {success, url, filename}
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// শুধু POST রিকোয়েস্ট গ্রহণ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST allowed']);
    exit;
}

// আপলোড ফোল্ডার
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// মাস অনুযায়ী সাব-ফোল্ডার
$monthFolder = date('Y-m');
$targetDir = $uploadDir . $monthFolder . '/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

try {
    // JSON বডি পার্স
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['image'])) {
        echo json_encode(['success' => false, 'error' => 'No image data']);
        exit;
    }
    
    $base64 = $input['image'];
    $userName = isset($input['name']) ? preg_replace('/[^a-zA-Z0-9_\-\x{0980}-\x{09FF}]/u', '', $input['name']) : 'user';
    
    // Base64 ডিকোড
    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
        $imageType = $matches[1];
        $base64 = substr($base64, strpos($base64, ',') + 1);
    } else {
        $imageType = 'jpg';
    }
    
    $base64 = str_replace(' ', '+', $base64);
    $imageData = base64_decode($base64);
    
    if ($imageData === false) {
        echo json_encode(['success' => false, 'error' => 'Invalid base64']);
        exit;
    }
    
    // ইউনিক ফাইল নেম
    $fileName = $userName . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $imageType;
    $filePath = $targetDir . $fileName;
    
    // ফাইল সেভ
    if (file_put_contents($filePath, $imageData)) {
        // ইমেজ ভ্যালিডেশন
        $imgInfo = @getimagesize($filePath);
        if (!$imgInfo) {
            unlink($filePath);
            echo json_encode(['success' => false, 'error' => 'Invalid image file']);
            exit;
        }
        
        // URL জেনারেট
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
        $fileUrl = $baseUrl . '/uploads/' . $monthFolder . '/' . $fileName;
        $downloadUrl = $baseUrl . '/download.php?file=' . urlencode($monthFolder . '/' . $fileName);
        
        echo json_encode([
            'success' => true,
            'url' => $fileUrl,
            'download_url' => $downloadUrl,
            'filename' => $fileName,
            'path' => 'uploads/' . $monthFolder . '/' . $fileName,
            'size' => filesize($filePath),
            'type' => $imageType
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>