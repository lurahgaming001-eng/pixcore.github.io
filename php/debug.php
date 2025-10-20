<?php
/**
 * DEBUG CONTACT - File untuk testing form tanpa redirect
 */

// Konfigurasi database (salin dari config.php)
$host = 'localhost';
$dbname = 'website_contact';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Contact Form</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .success { background: #d4edda; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; padding: 15px; margin: 10px 0; }
        form { max-width: 500px; }
        input, textarea { width: 100%; padding: 8px; margin: 5px 0; }
    </style>
</head>
<body>
    <h2>🧪 Debug Contact Form</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>📨 Data yang Diterima:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $email = $_POST['email'] ?? '';
    $subjek = $_POST['subjek'] ?? '';
    $pesan = $_POST['pesan'] ?? '';
    
    // Validasi
    $errors = [];
    if (empty($nama_lengkap)) $errors[] = "Nama lengkap harus diisi";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid";
    if (empty($subjek)) $errors[] = "Subjek harus diisi";
    if (empty($pesan)) $errors[] = "Pesan harus diisi";
    
    if (!empty($errors)) {
        echo "<div class='error'><strong>❌ Validasi Error:</strong><br>" . implode('<br>', $errors) . "</div>";
    } else {
        try {
            // Simpan ke database
            $stmt = $pdo->prepare("INSERT INTO contacts (nama_lengkap, email, subjek, pesan) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_lengkap, $email, $subjek, $pesan]);
            $last_id = $pdo->lastInsertId();
            
            echo "<div class='success'>✅ BERHASIL! Data disimpan dengan ID: $last_id</div>";
            echo "<p><a href='admin.php'>Lihat di Admin Panel</a></p>";
            
        } catch(PDOException $e) {
            echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Form untuk testing
echo "
    <h3>✏️ Test Form:</h3>
    <form method='POST'>
        <input type='text' name='nama_lengkap' placeholder='Nama Lengkap' required><br>
        <input type='email' name='email' placeholder='Email' required><br>
        <input type='text' name='subjek' placeholder='Subjek' required><br>
        <textarea name='pesan' placeholder='Pesan' rows='5' required></textarea><br>
        <button type='submit'>Kirim Test</button>
    </form>
    
    <hr>
    <p><a href='test_database.php'>🔍 Test Database Connection</a></p>
    <p><a href='admin.php'>📊 Admin Panel</a></p>
    <p><a href='index.html'>🏠 Kembali ke Home</a></p>
</body>
</html>";