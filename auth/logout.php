<?php
// filepath: c:\xampp\htdocs\YMC-Cashier\auth\logout.php

session_start();
session_unset(); // Hapus semua variabel sesi
session_destroy(); // Hancurkan sesi

header('Location: ../index.php'); // Arahkan kembali ke halaman indeks
exit;
?>