<?php
// php/admin_processor.php - Proses data untuk admin panel
include 'config.php';

// Fungsi untuk mendapatkan semua pesanan
function getAllOrders($conn) {
    $sql = "SELECT * FROM orders ORDER BY tanggal_order DESC";
    $result = $conn->query($sql);
    
    $orders = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    
    return $orders;
}

// Fungsi untuk update status pesanan
function updateOrderStatus($conn, $order_id, $status) {
    $order_id = intval($order_id);
    $status = $conn->real_escape_string($status);
    
    $sql = "UPDATE orders SET status = '$status' WHERE id = $order_id";
    return $conn->query($sql);
}

// Fungsi untuk menghapus pesanan
function deleteOrder($conn, $order_id) {
    $order_id = intval($order_id);
    $sql = "DELETE FROM orders WHERE id = $order_id";
    return $conn->query($sql);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    if ($action == 'get_orders') {
        $orders = getAllOrders($conn);
        echo json_encode($orders);
    }
    elseif ($action == 'update_status') {
        $order_id = $_POST['order_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if (updateOrderStatus($conn, $order_id, $status)) {
            echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal update status']);
        }
    }
    elseif ($action == 'delete_order') {
        $order_id = $_POST['order_id'] ?? 0;
        
        if (deleteOrder($conn, $order_id)) {
            echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus pesanan']);
        }
    }
    
    exit;
}
?>