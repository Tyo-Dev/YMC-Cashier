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
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Proses Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_level === 'admin') {
    try {
        if ($_POST['action'] === 'add') {
            $nama_user = validateInput($_POST['nama_user']);
            $username = validateInput($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $level = validateInput($_POST['level']);

            // Cek username sudah ada atau belum
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

            // Cek username sudah ada atau belum (kecuali untuk user yang sedang diedit)
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
$users = getPengguna($pdo);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="bg-slate-100 ">

    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="ml-20 w-full min-h-screen p-8 bg-white">
            <header class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-slate-800"><?php if ($user_level === 'admin'): ?>Manajemen Pengguna<?php else: ?>Data Pengguna<?php endif; ?></h1>
                <?php if ($user_level === 'admin'): ?>
                    <button onclick="openModal('add')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg inline-flex items-center gap-2 transition-colors">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Pengguna</span>
                    </button>
                <?php endif; ?>
            </header>

            <?php if ($notification): ?>
                <div class="<?= $notification['type'] === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?> border px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($notification['message']) ?></span>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="p-4 text-sm font-semibold text-slate-600">No</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Nama</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Username</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Level</th>
                            <?php if ($user_level === 'admin'): ?>
                                <th class="p-4 text-sm font-semibold text-slate-600">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="<?= ($user_level === 'admin' ? '5' : '4') ?>" class="p-4 text-center text-slate-500">
                                    Tidak ada data pengguna
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1;
                            foreach ($users as $user): ?>
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="p-4 text-slate-600"><?= $no++ ?></td>
                                    <td class="p-4 text-slate-600"><?= htmlspecialchars($user['nama_user']) ?></td>
                                    <td class="p-4 text-slate-600"><?= htmlspecialchars($user['username']) ?></td>
                                    <td class="p-4 text-slate-600">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?= $user['level'] === 'admin' ? 'bg-purple-100 text-purple-700' : ($user['level'] === 'pemilik' ? 'bg-blue-100 text-blue-700' :
                                            'bg-green-100 text-green-700') ?>">
                                            <?= ucfirst(htmlspecialchars($user['level'])) ?>
                                        </span>
                                    </td>
                                    <?php if ($user_level === 'admin'): ?>
                                        <td class="p-4">
                                            <div class="flex gap-2">
                                                <button onclick="openModal('edit', <?= htmlspecialchars(json_encode($user)) ?>)"
                                                    class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($user['id_user'] != $_SESSION['pengguna']['id_user']): ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_user" value="<?= $user['id_user'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <?php if ($user_level === 'admin'): ?>
        <div id="user-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">
                <button onclick="closeModal()" class="absolute top-4 right-4 text-slate-500 hover:text-slate-800 text-xl">&times;</button>
                <h2 id="modal-title" class="text-2xl font-bold text-slate-800 mb-6"></h2>

                <form id="user-form" method="POST" class="space-y-4">
                    <input type="hidden" name="action" id="form-action">
                    <input type="hidden" name="id_user" id="form-id_user">

                    <div>
                        <label for="nama_user" class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" id="form-nama_user" name="nama_user" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                        <input type="text" id="form-username" name="username" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input type="password" id="form-password" name="password" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Kosongkan jika tidak ingin ganti">
                    </div>
                    <div>
                        <label for="level" class="block text-sm font-medium text-slate-700 mb-1">Level</label>
                        <select id="form-level" name="level" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="admin">Admin</option>
                            <option value="kasir">Kasir</option>
                            <option value="pemilik">Pemilik</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-4 pt-4">
                        <button type="button" onclick="closeModal()" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold py-2 px-4 rounded-lg">Batal</button>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">Simpan</button>
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
                modalTitle.innerText = 'Tambah Pengguna Baru';
                formAction.value = 'add';
                formPassword.required = true;
                formPassword.placeholder = 'Wajib diisi';
            } else if (mode === 'edit' && userData) {
                modalTitle.innerText = 'Edit Data Pengguna';
                formAction.value = 'edit';
                formIdUser.value = userData.id_user;
                formNamaUser.value = userData.nama_user;
                formUsername.value = userData.username;
                formLevel.value = userData.level;
                formPassword.required = false;
                formPassword.placeholder = 'Kosongkan jika tidak ingin ganti';
            }
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        // Menutup modal jika user klik di luar area modal
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

</body>

</html>