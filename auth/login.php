<?php
session_start();
require_once '../config/koneksi.php'; // Pastikan file koneksi sudah benar

if (isset($_SESSION['pengguna'])) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(htmlspecialchars($_POST['username']));
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $userData = array(
                'id_user' => $user['id_user'],
                'nama_user' => $user['nama_user'],
                'username' => $user['username'],
                'level' => $user['level']
            );
            $_SESSION['pengguna'] = $userData;
            header('Location: ../pages/dashboard.php');
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan pada sistem: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kasir YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .leaf-pattern {
            background-image:
                radial-gradient(circle at 20% 80%, rgba(34, 197, 94, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(16, 185, 129, 0.1) 0%, transparent 50%);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-green-400 via-emerald-500 to-teal-600 min-h-screen flex items-center justify-center leaf-pattern">
    <div class="bg-white/95 backdrop-blur-sm rounded-xl shadow-2xl p-8 w-full max-w-md border border-green-100">
        <!-- Header dengan ikon -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Sistem Kasir YMC</h1>
            <p class="text-green-600 font-medium">Masuk ke akun Anda</p>
        </div>

        <!-- Alert Error -->
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-gray-700 font-semibold mb-2">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Username
                </label>
                <input type="text"
                    id="username"
                    name="username"
                    class="w-full px-4 py-3 border border-green-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-300 bg-green-50/50 hover:bg-green-50"
                    placeholder="Masukkan username Anda"
                    required>
            </div>

            <div>
                <label for="password" class="block text-gray-700 font-semibold mb-2">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Password
                </label>
                <input type="password"
                    id="password"
                    name="password"
                    class="w-full px-4 py-3 border border-green-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-300 bg-green-50/50 hover:bg-green-50"
                    placeholder="Masukkan password Anda"
                    required>
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:scale-105">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Masuk ke Sistem
            </button>
        </form>
    </div>

    <!-- Dekoratif elemen -->
    <div class="fixed top-10 left-10 w-20 h-20 bg-green-400/20 rounded-full blur-xl"></div>
    <div class="fixed bottom-10 right-10 w-32 h-32 bg-emerald-400/20 rounded-full blur-xl"></div>
    <div class="fixed top-1/2 right-20 w-16 h-16 bg-teal-400/20 rounded-full blur-xl"></div>
</body>

</html>