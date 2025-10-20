<?php
// admin.php - Handle form submissions and display admin panel


// Handle JSON API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action'])) {
        handleApiRequest($input);
    }
    exit;
}

// Handle regular form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    handleFormRequest($_POST);
    exit;
}

function handleApiRequest($data) {
    $action = $data['action'];
    
    switch ($action) {
        case 'order':
            handleOrderForm($data);
            break;
        case 'save_pengembangan_contact':
            handlePengembanganContactForm($data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
            exit;
    }
}

function handleFormRequest($data) {
    $action = $data['action'];
    
    switch ($action) {
        case 'save_contact':
            // Handle both E-commerce and Pengembangan-web forms
            if (isset($data['nama_lengkap'])) {
                // This is from Pengembangan-web.html
                handlePengembanganContactForm($data);
            } else {
                // This is from E-commerce.html
                handleEcommerceContactForm($data);
            }
            break;
        case 'get_contacts':
        case 'update_contact_status':
        case 'delete_contact':
            handleAdminAjaxRequests($data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenali: ' . $action]);
            exit;
    }
}

function handleOrderForm($data) {
    // Ambil data dari form order index.html
    $paket = htmlspecialchars($data['paket']);
    $nama_pemesan = htmlspecialchars($data['nama_pemesan']);
    $telepon = htmlspecialchars($data['telepon']);
    $email_pemesan = htmlspecialchars($data['email_pemesan']);
    $deskripsi = htmlspecialchars($data['deskripsi']);
    
    // Validasi data
    if (empty($paket) || empty($nama_pemesan) || empty($telepon) || empty($email_pemesan) || empty($deskripsi)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
        return;
    }
    
    // Validasi email
    if (!filter_var($email_pemesan, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
        return;
    }
    
    // Map paket ke nama yang lebih readable
    $paketMap = [
        'website_sederhana' => 'Website Sederhana - Rp 3.500.000',
        'website_bisnis' => 'Website Bisnis - Rp 7.500.000',
        'ecommerce' => 'E-commerce - Rp 15.000.000'
    ];
    
    $serviceName = $paketMap[$paket] ?? $paket;
    
    // Data order baru
    $orderData = [
        'id' => uniqid(),
        'name' => $nama_pemesan,
        'email' => $email_pemesan,
        'phone' => $telepon,
        'service' => $serviceName,
        'message' => $deskripsi,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'unread',
        'source' => 'index.html - Form Pesanan'
    ];
    
    saveContactData($orderData);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dikirim! Kami akan menghubungi Anda dalam 1x24 jam.']);
}

function handlePengembanganContactForm($data) {
    // Ambil data dari form Pengembangan-web.html
    $nama_lengkap = htmlspecialchars($data['nama_lengkap']);
    $email = htmlspecialchars($data['email']);
    $subjek = htmlspecialchars($data['subjek']);
    $pesan = htmlspecialchars($data['pesan']);
    
    // Validasi data
    if (empty($nama_lengkap) || empty($email) || empty($subjek) || empty($pesan)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
        return;
    }
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
        return;
    }
    
    // Data kontak baru
    $contactData = [
        'id' => uniqid(),
        'name' => $nama_lengkap,
        'email' => $email,
        'phone' => '-',
        'service' => $subjek,
        'message' => $pesan,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'unread',
        'source' => 'Pengembangan-web.html'
    ];
    
    saveContactData($contactData);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Pesan berhasil dikirim! Kami akan menghubungi Anda segera.']);
}

function handleEcommerceContactForm($data) {
    // Ambil data dari form E-commerce.html
    $name = htmlspecialchars($data['name']);
    $email = htmlspecialchars($data['email']);
    $phone = htmlspecialchars($data['phone']);
    $service = htmlspecialchars($data['service']);
    $message = htmlspecialchars($data['message']);
    
    // Validasi data
    if (empty($name) || empty($email) || empty($phone) || empty($service) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
        return;
    }
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
        return;
    }
    
    // Data kontak baru
    $contactData = [
        'id' => uniqid(),
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'service' => $service,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'unread',
        'source' => 'E-commerce.html'
    ];
    
    saveContactData($contactData);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Pesan berhasil dikirim! Kami akan menghubungi Anda segera.']);
}

function handleAdminAjaxRequests($data) {
    $action = $data['action'];
    $contactsFile = 'contacts.json';
    $contacts = [];
    
    if (file_exists($contactsFile)) {
        $existingData = file_get_contents($contactsFile);
        $contacts = json_decode($existingData, true) ?? [];
    }
    
    switch ($action) {
        case 'get_contacts':
            echo json_encode($contacts);
            break;
            
        case 'update_contact_status':
            $contactId = $data['contact_id'];
            $newStatus = $data['status'];
            
            foreach ($contacts as &$contact) {
                if ($contact['id'] === $contactId) {
                    $contact['status'] = $newStatus;
                    break;
                }
            }
            
            if (file_put_contents($contactsFile, json_encode($contacts, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal mengupdate status']);
            }
            break;
            
        case 'delete_contact':
            $contactId = $data['contact_id'];
            $contacts = array_filter($contacts, function($contact) use ($contactId) {
                return $contact['id'] !== $contactId;
            });
            $contacts = array_values($contacts);
            
            if (file_put_contents($contactsFile, json_encode($contacts, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus data']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
    }
}

function saveContactData($contactData) {
    $contactsFile = 'contacts.json';
    $contacts = [];
    
    if (file_exists($contactsFile)) {
        $existingData = file_get_contents($contactsFile);
        $contacts = json_decode($existingData, true) ?? [];
    }
    
    // Tambahkan kontak baru
    $contacts[] = $contactData;
    
    // Simpan ke file
    if (!file_put_contents($contactsFile, json_encode($contacts, JSON_PRETTY_PRINT))) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data']);
        exit;
    }
    
    // Kirim email notifikasi (opsional)
    sendEmailNotification($contactData);
}

function sendEmailNotification($contactData) {
    $to = 'admin@ecommpro.com';
    $subject = 'Pesan Baru dari ' . $contactData['source'];
    $message = "
    Pesan baru dari website:
    
    ID: {$contactData['id']}
    Nama: {$contactData['name']}
    Email: {$contactData['email']}
    Telepon: {$contactData['phone']}
    Layanan: {$contactData['service']}
    Waktu: {$contactData['timestamp']}
    Sumber: {$contactData['source']}
    
    Pesan:
    {$contactData['message']}
    
    ---
    Pesan ini dikirim dari form kontak website
    ";
    
    $headers = "From: {$contactData['email']}\r\n";
    $headers .= "Reply-To: {$contactData['email']}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Uncomment line below to enable email sending
    // mail($to, $subject, $message, $headers);
}

// Jika bukan POST request, tampilkan admin panel
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - E-Comm Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #7f8c8d;
            --white: #ffffff;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .admin-container {
            background: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            overflow: hidden;
        }

        .admin-header {
            background: var(--primary);
            color: var(--white);
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-logo {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .admin-logo span {
            color: var(--secondary);
        }

        .admin-nav {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .admin-content {
            padding: 30px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            min-height: 80vh;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-section {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow);
        }

        .sidebar-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-card {
            background: var(--light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            text-align: center;
            border-left: 4px solid var(--primary);
        }

        .stat-card.total { border-left-color: var(--primary); }
        .stat-card.pending { border-left-color: var(--warning); }
        .stat-card.confirmed { border-left-color: var(--secondary); }
        .stat-card.completed { border-left-color: var(--success); }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-primary { background: var(--secondary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-sm { padding: 8px 15px; font-size: 0.8rem; }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .section-title h2 {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .filter-section {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .orders-table-container {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-header {
            padding: 20px;
            background: var(--light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }

        .table-header h3 {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .orders-table th {
            background: var(--light);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .orders-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-unread { background: #fff3cd; color: #856404; }
        .status-read { background: #d1edff; color: #004085; }
        .status-replied { background: #d4edda; color: #155724; }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .message.success { background: #d4edda; color: #155724; border-left: 4px solid var(--success); }
        .message.error { background: #f8d7da; color: #721c24; border-left: 4px solid var(--danger); }
        .message.warning { background: #fff3cd; color: #856404; border-left: 4px solid var(--warning); }

        @media (max-width: 768px) {
            .admin-content {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .admin-nav {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="admin-logo">
                <i class="fas fa-shopping-cart"></i> Admin <span>Panel</span>
            </div>
            <nav class="admin-nav">
                <a href="index.html" class="nav-btn">
                    <i class="fas fa-home"></i> Website Utama
                </a>
                <a href="E-commerce.html" class="nav-btn">
                    <i class="fas fa-store"></i> E-commerce
                </a>
                <a href="Pengembangan-web.html" class="nav-btn">
                    <i class="fas fa-laptop-code"></i> Pengembangan Web
                </a>
                <button class="nav-btn" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <div class="nav-btn" style="background: var(--success);">
                    <i class="fas fa-envelope"></i> 
                    <span id="totalContactsBadge">0</span> Pesan
                </div>
            </nav>
        </header>

        <div class="admin-content">
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Quick Stats -->
                <div class="sidebar-section">
                    <div class="sidebar-title">
                        <i class="fas fa-chart-bar"></i> Statistik Pesan
                    </div>
                    <div class="stat-card total">
                        <div class="stat-number" id="statTotal">0</div>
                        <div class="stat-label">Total Pesan</div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-number" id="statUnread">0</div>
                        <div class="stat-label">Belum Dibaca</div>
                    </div>
                    <div class="stat-card confirmed">
                        <div class="stat-number" id="statRead">0</div>
                        <div class="stat-label">Sudah Dibaca</div>
                    </div>
                    <div class="stat-card completed">
                        <div class="stat-number" id="statReplied">0</div>
                        <div class="stat-label">Sudah Dibalas</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="sidebar-section">
                    <div class="sidebar-title">
                        <i class="fas fa-bolt"></i> Aksi Cepat
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <button class="btn btn-primary" onclick="loadContacts()">
                            <i class="fas fa-refresh"></i> Muat Ulang
                        </button>
                        <button class="btn btn-success" onclick="markAllAsRead()">
                            <i class="fas fa-check-double"></i> Tandai Semua Dibaca
                        </button>
                        <button class="btn btn-warning" onclick="showAllContacts()">
                            <i class="fas fa-list"></i> Semua Pesan
                        </button>
                    </div>
                </div>

                <!-- Filter by Source -->
                <div class="sidebar-section">
                    <div class="sidebar-title">
                        <i class="fas fa-filter"></i> Filter Sumber
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <button class="btn btn-sm" onclick="filterBySource('all')" style="background: var(--primary); color: white;">
                            Semua Sumber
                        </button>
                        <button class="btn btn-sm" onclick="filterBySource('E-commerce.html')">
                            E-commerce
                        </button>
                        <button class="btn btn-sm" onclick="filterBySource('Pengembangan-web.html')">
                            Pengembangan Web
                        </button>
                        <button class="btn btn-sm" onclick="filterBySource('index.html')">
                            Form Pesanan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="section-title">
                    <h2><i class="fas fa-envelope"></i> Manajemen Pesan & Order</h2>
                    <div style="flex: 1;"></div>
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="loadContacts()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-form">
                        <div class="filter-group">
                            <label for="filterStatus">Status</label>
                            <select id="filterStatus" onchange="filterContacts()">
                                <option value="">Semua Status</option>
                                <option value="unread">Belum Dibaca</option>
                                <option value="read">Sudah Dibaca</option>
                                <option value="replied">Sudah Dibalas</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="filterService">Layanan</label>
                            <select id="filterService" onchange="filterContacts()">
                                <option value="">Semua Layanan</option>
                                <option value="toko-sederhana">Toko Online Sederhana</option>
                                <option value="toko-bisnis">Toko Online Bisnis</option>
                                <option value="marketplace">Marketplace Custom</option>
                                <option value="konsultasi">Konsultasi</option>
                                <option value="website_sederhana">Website Sederhana</option>
                                <option value="website_bisnis">Website Bisnis</option>
                                <option value="ecommerce">E-commerce</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="searchContact">Cari</label>
                            <input type="text" id="searchContact" placeholder="Nama, email, atau pesan..." onkeyup="searchContacts()">
                        </div>
                    </div>
                </div>

                <!-- Contacts Table -->
                <div class="orders-table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-envelope"></i> Data Pesan & Order</h3>
                        <div id="tableInfo">Menampilkan 0 pesan</div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Telepon</th>
                                    <th>Layanan/Sumber</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="contactsTableBody">
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-spinner fa-spin"></i> Memuat data pesan...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Details Modal -->
    <div id="contactModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: var(--primary);">Detail Pesan</h3>
                <button onclick="closeContactModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--danger);">&times;</button>
            </div>
            <div id="contactModalContent">
                <!-- Detail kontak akan dimuat di sini -->
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let allContacts = [];
        let filteredContacts = [];
        let currentSourceFilter = 'all';

        // Load contacts from server
        function loadContacts() {
            showContactsLoading();
            
            const formData = new FormData();
            formData.append('action', 'get_contacts');
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(contacts => {
                allContacts = contacts;
                filteredContacts = [...contacts];
                displayContacts();
                updateStats();
            })
            .catch(error => {
                console.error('Error loading contacts:', error);
                showMessage('Gagal memuat data pesan: ' + error.message, 'error');
            });
        }

        // Show loading state
        function showContactsLoading() {
            document.getElementById('contactsTableBody').innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin"></i> Memuat data pesan...
                    </td>
                </tr>
            `;
        }

        // Display contacts in table
        function displayContacts() {
            const tbody = document.getElementById('contactsTableBody');
            
            if (filteredContacts.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox"></i> Tidak ada data pesan
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            filteredContacts.forEach(contact => {
                const statusClass = `status-${contact.status}`;
                const statusText = getContactStatusText(contact.status);
                const serviceText = getServiceText(contact.service);
                const sourceText = getSourceText(contact.source);
                const formattedDate = new Date(contact.timestamp).toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `
                <tr>
                    <td><strong>#${contact.id.substring(0, 8)}</strong></td>
                    <td>${formattedDate}</td>
                    <td><strong>${contact.name}</strong></td>
                    <td>${contact.email}</td>
                    <td>${contact.phone}</td>
                    <td>
                        <div><strong>${serviceText}</strong></div>
                        <small style="color: var(--gray);">${sourceText}</small>
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-primary btn-sm" onclick="viewContact('${contact.id}')" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${contact.status === 'unread' ? `
                                <button class="btn btn-success btn-sm" onclick="updateContactStatus('${contact.id}', 'read')" title="Tandai Sudah Dibaca">
                                    <i class="fas fa-check"></i>
                                </button>
                            ` : ''}
                            <button class="btn btn-danger btn-sm" onclick="deleteContact('${contact.id}')" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            });
            
            tbody.innerHTML = html;
            updateTableInfo();
        }

        // Update statistics
        function updateStats() {
            const total = allContacts.length;
            const unread = allContacts.filter(contact => contact.status === 'unread').length;
            const read = allContacts.filter(contact => contact.status === 'read').length;
            const replied = allContacts.filter(contact => contact.status === 'replied').length;

            document.getElementById('statTotal').textContent = total;
            document.getElementById('statUnread').textContent = unread;
            document.getElementById('statRead').textContent = read;
            document.getElementById('statReplied').textContent = replied;
            document.getElementById('totalContactsBadge').textContent = total;
        }

        // Update table information
        function updateTableInfo() {
            const total = allContacts.length;
            const showing = filteredContacts.length;
            document.getElementById('tableInfo').textContent = `Menampilkan ${showing} dari ${total} pesan`;
        }

        // Filter contacts
        function filterContacts() {
            const statusFilter = document.getElementById('filterStatus').value;
            const serviceFilter = document.getElementById('filterService').value;
            const searchTerm = document.getElementById('searchContact').value.toLowerCase();

            filteredContacts = allContacts.filter(contact => {
                const statusMatch = !statusFilter || contact.status === statusFilter;
                const serviceMatch = !serviceFilter || contact.service.toLowerCase().includes(serviceFilter.toLowerCase());
                const searchMatch = !searchTerm || 
                    contact.name.toLowerCase().includes(searchTerm) ||
                    contact.email.toLowerCase().includes(searchTerm) ||
                    contact.phone.includes(searchTerm) ||
                    contact.message.toLowerCase().includes(searchTerm);
                const sourceMatch = currentSourceFilter === 'all' || contact.source === currentSourceFilter;
                
                return statusMatch && serviceMatch && searchMatch && sourceMatch;
            });

            displayContacts();
        }

        // Filter by source
        function filterBySource(source) {
            currentSourceFilter = source;
            filterContacts();
        }

        // Search contacts
        function searchContacts() {
            filterContacts();
        }

        // View contact details
        function viewContact(contactId) {
            const contact = allContacts.find(c => c.id === contactId);
            if (!contact) return;

            const modalContent = document.getElementById('contactModalContent');
            const statusText = getContactStatusText(contact.status);
            const serviceText = getServiceText(contact.service);
            const sourceText = getSourceText(contact.source);
            const formattedDate = new Date(contact.timestamp).toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            modalContent.innerHTML = `
                <div class="order-details">
                    <div class="detail-item">
                        <span class="detail-label">ID Pesan:</span> #${contact.id}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Sumber:</span> ${sourceText}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Tanggal:</span> ${formattedDate}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Nama:</span> ${contact.name}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span> ${contact.email}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Telepon:</span> ${contact.phone}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Layanan:</span> ${serviceText}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span> 
                        <span class="status-badge status-${contact.status}">${statusText}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Pesan:</span>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px; border-left: 4px solid var(--secondary); white-space: pre-wrap;">
                        ${contact.message}
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button class="btn btn-primary" onclick="closeContactModal()">Tutup</button>
                    <a href="mailto:${contact.email}?subject=Balasan: ${serviceText}" class="btn btn-success">
                        <i class="fas fa-reply"></i> Balas Email
                    </a>
                    ${contact.status !== 'replied' ? `
                        <button class="btn btn-warning" onclick="updateContactStatus('${contact.id}', 'replied'); closeContactModal();">
                            Tandai Sudah Dibalas
                        </button>
                    ` : ''}
                </div>
            `;

            // Update status to 'read' when viewing
            if (contact.status === 'unread') {
                updateContactStatus(contactId, 'read', false);
            }

            document.getElementById('contactModal').style.display = 'flex';
        }

        // Close contact modal
        function closeContactModal() {
            document.getElementById('contactModal').style.display = 'none';
        }

        // Update contact status
        function updateContactStatus(contactId, newStatus, showMessage = true) {
            const formData = new FormData();
            formData.append('action', 'update_contact_status');
            formData.append('contact_id', contactId);
            formData.append('status', newStatus);

            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && showMessage) {
                    showMessage('Status pesan berhasil diupdate!', 'success');
                    loadContacts();
                } else if (!data.success && showMessage) {
                    showMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (showMessage) {
                    showMessage('Terjadi kesalahan saat mengupdate status', 'error');
                }
            });
        }

        // Delete contact
        function deleteContact(contactId) {
            if (!confirm('Apakah Anda yakin ingin menghapus pesan ini?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_contact');
            formData.append('contact_id', contactId);

            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Pesan berhasil dihapus!', 'success');
                    loadContacts();
                } else {
                    showMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Terjadi kesalahan saat menghapus pesan', 'error');
            });
        }

        // Mark all as read
        function markAllAsRead() {
            if (!confirm('Tandai semua pesan sebagai sudah dibaca?')) return;
            
            allContacts.forEach(contact => {
                if (contact.status === 'unread') {
                    updateContactStatus(contact.id, 'read', false);
                }
            });
            
            setTimeout(() => {
                loadContacts();
                showMessage('Semua pesan telah ditandai sebagai sudah dibaca!', 'success');
            }, 1000);
        }

        // Show all contacts
        function showAllContacts() {
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterService').value = '';
            document.getElementById('searchContact').value = '';
            currentSourceFilter = 'all';
            filteredContacts = [...allContacts];
            displayContacts();
        }

        // Utility functions
        function getContactStatusText(status) {
            const statusMap = {
                'unread': 'Belum Dibaca',
                'read': 'Sudah Dibaca',
                'replied': 'Sudah Dibalas'
            };
            return statusMap[status] || status;
        }

        function getServiceText(service) {
            const serviceMap = {
                'toko-sederhana': 'Toko Online Sederhana',
                'toko-bisnis': 'Toko Online Bisnis',
                'marketplace': 'Marketplace Custom',
                'konsultasi': 'Konsultasi',
                'website_sederhana': 'Website Sederhana',
                'website_bisnis': 'Website Bisnis',
                'ecommerce': 'E-commerce'
            };
            return serviceMap[service] || service;
        }

        function getSourceText(source) {
            const sourceMap = {
                'E-commerce.html': 'E-commerce',
                'Pengembangan-web.html': 'Pengembangan Web',
                'index.html - Form Pesanan': 'Form Pesanan'
            };
            return sourceMap[source] || source;
        }

        function showMessage(message, type) {
            // Remove existing message if any
            const existingMessage = document.querySelector('.message');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            // Create new message element
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
                ${message}
            `;
            
            // Add before main content
            const mainContent = document.querySelector('.main-content');
            mainContent.insertBefore(messageDiv, mainContent.firstChild);
            
            // Remove automatically after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }

        function refreshData() {
            loadContacts();
        }

        // Load contacts on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadContacts();
            // Auto refresh every 30 seconds
            setInterval(loadContacts, 30000);
        });
        
    </script>
</body>
</html>