<?php
// api.php

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Function untuk response JSON
function jsonResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

// Database configuration - SESUAIKAN!
$host = 'localhost';
$dbname = 'webdev_pro';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    jsonResponse(false, 'Koneksi database gagal: ' . $e->getMessage());
}

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Jika bukan JSON, coba dari POST
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

$action = $_GET['action'] ?? $data['action'] ?? '';

// Log untuk debugging
error_log("API Action: " . $action . " - Data: " . print_r($data, true));

try {
    switch ($action) {
        case 'test':
            handleTest($pdo);
            break;
        case 'order':
            handleOrder($pdo, $data);
            break;
        case 'get_orders':
            getOrders($pdo);
            break;
        case 'update_status':
            updateOrderStatus($pdo, $data);
            break;
        case 'delete_order':
            deleteOrder($pdo, $data);
            break;
            case 'get_contacts':
        getContacts($pdo);
         break;
        case 'update_contact_status':
        updateContactStatus($pdo, $data);
        break;
        case 'delete_contact':
        deleteContact($pdo, $data);
        break;
        default:
            jsonResponse(false, 'Action tidak valid. Action yang tersedia: test, order, get_orders, update_status, delete_order');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
function getContacts($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT *, DATE_FORMAT(tanggal_kontak, '%d %b %Y %H:%i') as tanggal_format 
            FROM contacts 
            ORDER BY tanggal_kontak DESC
        ");
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($contacts);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error fetching contacts: ' . $e->getMessage()]);
    }
}

function updateContactStatus($pdo, $data) {
    if (empty($data['contact_id']) || empty($data['status'])) {
        jsonResponse(false, 'Contact ID and status are required');
    }
    
    $allowedStatuses = ['unread', 'read', 'replied'];
    if (!in_array($data['status'], $allowedStatuses)) {
        jsonResponse(false, 'Invalid status');
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE contacts SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $data['contact_id']]);
        
        if ($stmt->rowCount() > 0) {
            jsonResponse(true, 'Contact status updated successfully');
        } else {
            jsonResponse(false, 'Contact not found');
        }
        
    } catch (Exception $e) {
        jsonResponse(false, 'Error updating contact status: ' . $e->getMessage());
    }
}

function deleteContact($pdo, $data) {
    if (empty($data['contact_id'])) {
        jsonResponse(false, 'Contact ID is required');
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->execute([$data['contact_id']]);
        
        if ($stmt->rowCount() > 0) {
            jsonResponse(true, 'Contact deleted successfully');
        } else {
            jsonResponse(false, 'Contact not found');
        }
        
    } catch (Exception $e) {
        jsonResponse(false, 'Error deleting contact: ' . $e->getMessage());
    }
}
function handleTest($pdo) {
    try {
        // Test koneksi database
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        // Test jika table orders exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'orders'");
        $tableExists = $tableCheck->rowCount() > 0;
        
        jsonResponse(true, 'API dan database berfungsi dengan baik', [
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => 'Connected',
            'table_orders_exists' => $tableExists
        ]);
    } catch (Exception $e) {
        jsonResponse(false, 'Database test gagal: ' . $e->getMessage());
    }
}

function handleOrder($pdo, $data) {
    // Validasi field yang required
    $required = ['paket', 'nama_pemesan', 'telepon', 'email_pemesan', 'deskripsi'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        jsonResponse(false, 'Field berikut harus diisi: ' . implode(', ', $missing));
    }

    // Validasi email
    if (!filter_var($data['email_pemesan'], FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Format email tidak valid');
    }

    // Get harga paket
    $harga = getPackagePrice($data['paket']);
    if ($harga === 0) {
        jsonResponse(false, "Paket tidak valid: " . $data['paket']);
    }
    
    try {
        // Insert ke database
        $stmt = $pdo->prepare("
            INSERT INTO orders (paket, nama_pemesan, telepon, email_pemesan, deskripsi, harga, status, tanggal_order) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([
            $data['paket'],
            trim($data['nama_pemesan']),
            trim($data['telepon']),
            trim($data['email_pemesan']),
            trim($data['deskripsi']),
            $harga
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        jsonResponse(true, 
            'Pesanan berhasil dikirim! ID Pesanan: #' . $orderId . 
            '. Silakan cek di admin panel untuk melihat detail pesanan.',
            [
                'order_id' => $orderId,
                'paket' => $data['paket'],
                'harga' => 'Rp ' . number_format($harga, 0, ',', '.'),
                'status' => 'pending'
            ]
        );
        
    } catch (Exception $e) {
        jsonResponse(false, 'Gagal menyimpan pesanan: ' . $e->getMessage());
    }
}

function getPackagePrice($paket) {
    $prices = [
        'website_sederhana' => 3500000,
        'website_bisnis' => 7500000,
        'ecommerce' => 15000000
    ];
    return $prices[$paket] ?? 0;
}

function getOrders($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT *, DATE_FORMAT(tanggal_order, '%d %b %Y %H:%i') as tanggal_format 
            FROM orders 
            ORDER BY tanggal_order DESC
        ");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return langsung array orders untuk admin panel
        echo json_encode($orders);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error fetching orders: ' . $e->getMessage()]);
    }
}

function updateOrderStatus($pdo, $data) {
    if (empty($data['order_id']) || empty($data['status'])) {
        jsonResponse(false, 'Order ID dan status diperlukan');
    }
    
    $allowedStatuses = ['pending', 'confirmed', 'completed'];
    if (!in_array($data['status'], $allowedStatuses)) {
        jsonResponse(false, 'Status tidak valid');
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $data['order_id']]);
        
        if ($stmt->rowCount() > 0) {
            jsonResponse(true, 'Status berhasil diupdate');
        } else {
            jsonResponse(false, 'Pesanan tidak ditemukan');
        }
        
    } catch (Exception $e) {
        jsonResponse(false, 'Error updating status: ' . $e->getMessage());
    }
}

function deleteOrder($pdo, $data) {
    if (empty($data['order_id'])) {
        jsonResponse(false, 'Order ID diperlukan');
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$data['order_id']]);
        
        if ($stmt->rowCount() > 0) {
            jsonResponse(true, 'Pesanan berhasil dihapus');
        } else {
            jsonResponse(false, 'Pesanan tidak ditemukan');
        }
        
    } catch (Exception $e) {
        jsonResponse(false, 'Error deleting order: ' . $e->getMessage());
    }
}
?>