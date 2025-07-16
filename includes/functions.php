<?php
// Memulai session di satu tempat terpusat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fungsi untuk menampilkan header HTML, termasuk CSS dan title halaman.
 * Ini mencegah duplikasi kode di setiap file.
 *
 * @param string $pageTitle Judul halaman yang akan ditampilkan di tag <title>
 */
function get_header($pageTitle = 'YMC-Cashier')
{
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$pageTitle}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-slate-100">
HTML;
}

/**
 * Fungsi untuk menampilkan footer HTML dan tag penutup.
 */
function get_footer()
{
    echo <<<HTML
</body>
</html>
HTML;
}

/**
 * Memeriksa apakah pengguna sudah login dan memiliki level yang diizinkan.
 * Jika tidak, akan diarahkan ke halaman yang sesuai.
 *
 * @param array $allowed_levels Array berisi level yang diizinkan (e.g., ['admin', 'pemilik'])
 */
function checkUserLevel($allowed_levels)
{
    if (!isset($_SESSION['pengguna'])) {
        header('Location: ../auth/login.php');
        exit;
    }
    if (!in_array($_SESSION['pengguna']['level'], $allowed_levels)) {
        header('Location: ../pages/dashboard.php');
        exit;
    }
}

/**
 * Mengambil semua data pengguna dari database.
 * Tidak mengambil kolom password untuk keamanan.
 *
 * @param PDO $pdo Objek koneksi PDO
 * @return array Daftar pengguna
 */
function getPengguna($pdo)
{
    $stmt = $pdo->prepare("SELECT id_user, nama_user, username, level FROM pengguna ORDER BY nama_user");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Anda bisa menambahkan lebih banyak fungsi di sini di kemudian hari
// seperti getBarang(), getKategori(), dll.
