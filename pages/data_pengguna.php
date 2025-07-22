<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Keamanan Halaman
checkUserLevel(['admin', 'pemilik']);

$user_level = $_SESSION['pengguna']['level'];
$notification = null;

// Validasi input
function validateInput($data)
{
    return htmlspecialchars(trim($data));
}

// Proses Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_level === 'admin') {
    try {
        if ($_POST['action'] === 'add') {
            $nama_user = validateInput($_POST['nama_user']);
            $username = validateInput($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $level = validateInput($_POST['level']);

            // Cek username
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $notification = ['type' => 'error', 'message' => 'Username sudah digunakan!'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO pengguna (nama_user, username, password, level) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nama_user, $username, $password, $level]);
                $notification = ['type' => 'success', 'message' => 'Pengguna baru berhasil ditambahkan.'];
            }
        } elseif ($_POST['action'] === 'edit') {
            $id_user = filter_var($_POST['id_user'], FILTER_VALIDATE_INT);
            $nama_user = validateInput($_POST['nama_user']);
            $username = validateInput($_POST['username']);
            $level = validateInput($_POST['level']);

            // Cek username (kecuali untuk user yang diedit)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE username = ? AND id_user != ?");
            $stmt->execute([$username, $id_user]);
            if ($stmt->fetchColumn() > 0) {
                $notification = ['type' => 'error', 'message' => 'Username sudah digunakan!'];
            } else {
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE pengguna SET nama_user = ?, username = ?, password = ?, level = ? WHERE id_user = ?");
                    $stmt->execute([$nama_user, $username, $password, $level, $id_user]);
                } else {
                    $stmt = $pdo->prepare("UPDATE pengguna SET nama_user = ?, username = ?, level = ? WHERE id_user = ?");
                    $stmt->execute([$nama_user, $username, $level, $id_user]);
                }
                $notification = ['type' => 'success', 'message' => 'Data pengguna berhasil diperbarui.'];
            }
        } elseif ($_POST['action'] === 'delete') {
            $id_user = filter_var($_POST['id_user'], FILTER_VALIDATE_INT);
            if ($id_user == $_SESSION['pengguna']['id_user']) {
                $notification = ['type' => 'error', 'message' => 'Anda tidak bisa menghapus akun Anda sendiri.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM pengguna WHERE id_user = ?");
                $stmt->execute([$id_user]);
                $notification = ['type' => 'success', 'message' => 'Pengguna berhasil dihapus.'];
            }
        }
    } catch (PDOException $e) {
        $notification = ['type' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }
}

// Ambil data pengguna
$stmt = $pdo->query("SELECT * FROM pengguna ORDER BY nama_user");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">

    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 p-6 sm:p-8 lg:p-10">
            <div class="max-w-7xl mx-auto">
                <header class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-4">
                    <h1 class="text-3xl sm:text-4xl font-bold text-gray-800 bg-white py-3 px-6 rounded-2xl shadow-md border border-gray-100">
                        <?php if ($user_level === 'admin') : ?>Manajemen Pengguna<?php else : ?>Data Pengguna<?php endif; ?>
                    </h1>
                    <?php if ($user_level === 'admin') : ?>
                        <button onclick="openModal('add')" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-xl flex items-center gap-3 transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-plus-circle text-lg"></i>
                            <span class="font-medium">Tambah Pengguna</span>
                        </button>
                    <?php endif; ?>
                </header>

                <?php if ($notification) : ?>
                    <div class="mb-8 p-5 rounded-2xl border-2 backdrop-blur-sm shadow-md <?= $notification['type'] === 'success' ? 'bg-green-50/80 border-green-200 text-green-800' : 'bg-red-50/80 border-red-200 text-red-800' ?>">
                        <div class="flex items-center gap-4">
                            <i class="fas <?= $notification['type'] === 'success' ? 'fa-circle-check text-green-500' : 'fa-circle-exclamation text-red-500' ?> text-2xl"></i>
                            <p class="font-medium text-lg"><?= $notification['message'] ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">ID</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Nama</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Username</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Level</th>
                                    <?php if ($user_level === 'admin') : ?>
                                        <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($users as $user) : ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                                        <td class="px-6 py-4 text-base text-gray-600 font-medium"><?= $user['id_user'] ?></td>
                                        <td class="px-6 py-4 text-gray-800 font-medium text-base"><?= htmlspecialchars($user['nama_user']) ?></td>
                                        <td class="px-6 py-4 text-base text-gray-600"><?= htmlspecialchars($user['username']) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1.5 text-sm font-medium rounded-xl 
                                            <?php
                                            switch ($user['level']) {
                                                case 'admin':
                                                    echo 'bg-purple-100 text-purple-700 border border-purple-200';
                                                    break;
                                                case 'pemilik':
                                                    echo 'bg-blue-100 text-blue-700 border border-blue-200';
                                                    break;
                                                default:
                                                    echo 'bg-green-100 text-green-700 border border-green-200';
                                            }
                                            ?>">
                                                <?= ucfirst(htmlspecialchars($user['level'])) ?>
                                            </span>
                                        </td>
                                        <?php if ($user_level === 'admin') : ?>
                                            <td class="px-6 py-4">
                                                <div class="flex gap-4">
                                                    <button onclick="openModal('edit', <?= htmlspecialchars(json_encode($user)) ?>)" class="text-blue-600 hover:text-blue-800 bg-blue-50 p-2.5 rounded-lg hover:bg-blue-100 transition-colors">
                                                        <i class="fas fa-edit text-lg"></i>
                                                    </button>
                                                    <?php if ($user['id_user'] != $_SESSION['pengguna']['id_user']) : ?>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id_user" value="<?= $user['id_user'] ?>">
                                                            <button type="submit" class="text-red-600 hover:text-red-800 bg-red-50 p-2.5 rounded-lg hover:bg-red-100 transition-colors">
                                                                <i class="fas fa-trash text-lg"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php if ($user_level === 'admin') : ?>
        <div id="user-modal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex justify-center items-center z-50">
            <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl w-full max-w-lg p-10 border border-gray-100 transform transition-all">
                <div class="flex justify-between items-center mb-8">
                    <h2 id="modal-title" class="text-3xl font-bold text-gray-800 flex items-center gap-4"></h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl p-3 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form id="user-form" method="POST" class="space-y-6">
                    <input type="hidden" name="action" id="form-action">
                    <input type="hidden" name="id_user" id="form-id_user">

                    <div>
                        <label for="nama_user" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" id="form-nama_user" name="nama_user" required class="w-full px-4 py-2.5 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" id="form-username" name="username" required class="w-full px-4 py-2.5 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label for="level" class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                            <select id="form-level" name="level" required class="w-full px-4 py-2.5 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="admin">Admin</option>
                                <option value="kasir">Kasir</option>
                                <option value="pemilik">Pemilik</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="form-password" name="password" class="w-full px-4 py-2.5 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Kosongkan jika tidak ganti">
                    </div>
                    <div class="flex justify-end gap-4 pt-4">
                        <button type="button" onclick="closeModal()" class="px-8 py-3.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-300 font-medium flex items-center gap-3 text-lg">
                            <i class="fas fa-times"></i>
                            <span>Batal</span>
                        </button>
                        <button type="submit" class="px-8 py-3.5 text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 rounded-xl transition-all duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center gap-3 text-lg">
                            <i class="fas fa-save"></i>
                            <span>Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        const modal = document.getElementById('user-modal');
        const modalTitle = document.getElementById('modal-title');
        const userForm = document.getElementById('user-form');
        const formAction = document.getElementById('form-action');
        const formIdUser = document.getElementById('form-id_user');
        const formNamaUser = document.getElementById('form-nama_user');
        const formUsername = document.getElementById('form-username');
        const formPassword = document.getElementById('form-password');
        const formLevel = document.getElementById('form-level');

        function openModal(mode, userData = null) {
            userForm.reset();
            if (mode === 'add') {
                modalTitle.innerHTML = '<i class="fas fa-user-plus text-green-600"></i> <span>Tambah Pengguna</span>';
                formAction.value = 'add';
                formPassword.required = true;
                formPassword.placeholder = 'Wajib diisi';
            } else if (mode === 'edit' && userData) {
                modalTitle.innerHTML = '<i class="fas fa-user-edit text-blue-600"></i> <span>Edit Pengguna</span>';
                formAction.value = 'edit';
                formIdUser.value = userData.id_user;
                formNamaUser.value = userData.nama_user;
                formUsername.value = userData.username;
                formLevel.value = userData.level;
                formPassword.required = false;
                formPassword.placeholder = 'Kosongkan jika tidak ingin ganti';
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

</body>

</html>