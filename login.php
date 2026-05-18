<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Portal Alumni SMK Telkom Lampung</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>

<body>
    <?php
    session_start();

    if (isset($_SESSION['user_id'])) {
        $role = $_SESSION['role'];
        header('Location: ' . ($role === 'user' ? 'dashboard_user.php' : 'dashboard_admin.php'));
        exit;
    }

    require 'koneksi.php';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username && $password) {
            $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user['password'])) {

                if ($user['status'] === 'pending') {
                    $error = 'Akun Anda sedang menunggu verifikasi admin.';
                } elseif ($user['status'] === 'rejected') {
                    $error = 'Akun Anda ditolak. Hubungi administrator.';
                } else {
                    $_SESSION['user_id']  = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role']     = $user['role'];
                    $_SESSION['id_alumni'] = $user['id_alumni'];

                    header('Location: ' . ($user['role'] === 'user' ? 'dashboard_user.php' : 'dashboard_admin.php'));
                    exit;
                }
            } else {
                $error = 'Username atau password salah.';
            }
        } else {
            $error = 'Harap isi semua kolom.';
        }
    }
    ?>

    <div class="auth-bg" style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background-image: url('assets/img/bg-sekolah.jpg'); background-size: cover; background-position: center;">
        <div class="auth-card" style="width: 100%; max-width: 400px; margin: 20px; background: rgba(255,255,255,0.95); padding: 30px; border-radius: 8px; box-shadow: var(--shadow);">
            <div class="auth-card-header" style="text-align: center; margin-bottom: 20px;">
                <div class="brand-logo" style="margin-bottom: 10px;">
                    <img src="assets/img/telkom-logo.jpg" alt="Logo SMK Telkom Lampung" style="width: 60px; height: 60px; object-fit: contain;">
                </div>
                <h1 class="brand-name" style="font-size: 1.5rem; margin-bottom: 5px; color: #151515;">Manajemen Data Alumni</h1>
                <p class="brand-tagline" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px; color: #5e5e5e;">SMK Telkom Lampung</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">

                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">

                        <input type="text" id="username" name="username" placeholder="Masukkan username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">

                        <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">
                    Masuk

                </button>
            </form>

            <div class="auth-footer">
                <p>Belum punya akun? <a href="register.php" style="color: #0095DA;">Daftar di sini</a></p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pw = document.getElementById('password');
            pw.type = pw.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>

</html>