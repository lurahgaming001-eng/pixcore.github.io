<?php
// process_contact.php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database configuration - SESUAIKAN DENGAN SETTING ANDA
$host = 'localhost';
$dbname = 'webdev_pro';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $e->getMessage()]);
    exit;
}

// Function untuk response JSON
function jsonResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

// Get POST data
$nama_lengkap = $_POST['nama_lengkap'] ?? '';
$email = $_POST['email'] ?? '';
$subjek = $_POST['subjek'] ?? '';
$pesan = $_POST['pesan'] ?? '';

// Validasi input
if (empty($nama_lengkap) || empty($email) || empty($subjek) || empty($pesan)) {
    jsonResponse(false, 'Semua field harus diisi!');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Format email tidak valid!');
}

try {
    // Insert ke tabel contacts
    $stmt = $pdo->prepare("
        INSERT INTO contacts (nama_lengkap, email, subjek, pesan, status, tanggal_kontak) 
        VALUES (?, ?, ?, ?, 'unread', NOW())
    ");
    
    $stmt->execute([
        trim($nama_lengkap),
        trim($email),
        trim($subjek),
        trim($pesan)
    ]);
    
    $contactId = $pdo->lastInsertId();
    
    jsonResponse(true, 
        'Pesan Anda berhasil dikirim! Kami akan menghubungi Anda segera.',
        [
            'contact_id' => $contactId,
            'nama' => $nama_lengkap,
            'email' => $email
        ]
    );
    
} catch (Exception $e) {
    jsonResponse(false, 'Gagal mengirim pesan: ' . $e->getMessage());
}
?>