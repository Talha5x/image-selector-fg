<?php
/**
 * ইমেজ ডাউনলোড API
 * GET: file (ফাইল পাথ)
 * Response: ফোর্স ডাউনলোড
 */

// ফাইল প্যারামিটার
$file = isset($_GET['file']) ? $_GET['file'] : null;

if (!$file) {
    die('No file specified');
}

// পাথ ট্রাভার্সাল প্রিভেনশন
$file = preg_replace('/\.\.\//', '', $file);
$file = preg_replace('/[^a-zA-Z0-9_\-\/\.]/', '', $file);

// ফুল পাথ
$filePath = __DIR__ . '/uploads/' . $file;

// ফাইল এক্সিস্ট চেক
if (!file_exists($filePath)) {
    die('File not found');
}

// ফাইল টাইপ
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
];

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeType = isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'application/octet-stream';

// ফাইল নেম
$fileName = basename($filePath);

// ক্লিন আউটপুট বাফার
if (ob_get_level()) {
    ob_end_clean();
}

// হেডার সেট
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: public, max-age=86400');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

// ফাইল আউটপুট
readfile($filePath);
exit;
?>