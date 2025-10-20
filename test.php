<?php
// test_permissions.php
$file = 'contacts.json';

if (is_writable($file)) {
    echo "✅ File contacts.json dapat ditulis (writable)";
} else {
    echo "❌ File contacts.json TIDAK dapat ditulis";
    
    // Coba buat file baru untuk test
    if (file_put_contents('test_write.txt', 'test')) {
        echo "<br>✅ Server dapat menulis file baru";
        unlink('test_write.txt');
    } else {
        echo "<br>❌ Server tidak dapat menulis file baru";
    }
}
?>