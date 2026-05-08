<?php
$conn = new mysqli('localhost', 'root', '', 'rental_kamera');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}