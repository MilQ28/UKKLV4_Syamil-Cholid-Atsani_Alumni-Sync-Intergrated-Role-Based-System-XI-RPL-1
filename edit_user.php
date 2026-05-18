<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengguna — Alumni SMK</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>

<body>
    <?php
    session_start();
    require 'auth.php';
    require 'koneksi.php';
    requireAdmin();
    include 'navbar.php';

    $id = (int)($_GET['id'] ?? 0);

    $stmt = mysqli_prepare($conn, "SELECT u.*, a.nama FROM users u LEFT JOIN alumni a ON u.id_alumni=a.id_alumni WHERE u.user_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$user) {
        echo '<div class="page-wrapper"><div class="alert alert-error">Pengguna tidak ditemukan.</div></div>';
        exit;
    }

    $error = $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_pw = trim($_POST['new_password'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $role   = trim($_POST['role']   ?? '');

        $allowedRoles = isSuperAdmin() ? ['user', 'admin', 'superadmin'] : ['user', 'admin'];
        if (!in_array($role, $allowedRoles)) $role = $user['role'];

        $stmt = mysqli_prepare($conn, "UPDATE users SET role=?, status=? WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, 'ssi', $role, $status, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($new_pw) {
            if (strlen($new_pw) < 6) {
                $error = 'Password minimal 6 karakter.';
            } else {
                $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE user_id=?");
                mysqli_stmt_bind_param($stmt, 'si', $hashed, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        if (!$error) {
            $success = 'Data pengguna diperbarui.';
            // Refresh data user
            $stmt = mysqli_prepare($conn, "SELECT u.*, a.nama FROM users u LEFT JOIN alumni a ON u.id_alumni=a.id_alumni WHERE u.user_id=?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
        }
    }
    ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">Edit Pengguna</h1>
                <p class="page-sub"><?= htmlspecialchars($user['username']) ?></p>
            </div>
            <a href="users.php" class="btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="section-card">
            <form method="POST" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username</label>
                        <div class="input-wrapper"><input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled></div>
                    </div>
                    <div class="form-group">
                        <label>Alumni Terhubung</label>
                        <div class="input-wrapper"><input type="text" value="<?= htmlspecialchars($user['nama'] ?? 'Tidak ada') ?>" disabled></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Role</label>
                        <div class="input-wrapper">
                            <select name="role">
                                <?php
                                $roles = isSuperAdmin() ? ['user', 'admin', 'superadmin'] : ['user', 'admin'];
                                foreach ($roles as $r): ?>
                                    <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status Akun</label>
                        <div class="input-wrapper">
                            <select name="status">
                                <option value="pending" <?= $user['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="approved" <?= $user['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="rejected" <?= $user['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Reset Password (kosongkan jika tidak ingin ubah)</label>
                    <div class="input-wrapper">

                        <input type="text" name="new_password" placeholder="Password baru">
                    </div>
                </div>
                <button type="submit" class="btn-primary">

                    Simpan
                </button>
            </form>
        </div>
    </div>
</body>

</html>