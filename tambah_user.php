<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna — Alumni SMK</title>
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

    $error = $success = '';
    $jurusan_list = [
        'Rekayasa Perangkat Lunak',
        'Teknik Komputer dan Jaringan',
        'Teknik Jaringan Akses dan Telekomunikasi',
        'Animasi',
    ];

    // ==============================================================================
    // 1. SIAPKAN DATA UNTUK FORM (DATA ALUMNI TANPA AKUN)
    // ==============================================================================
    // Cari alumni yang ID-nya belum ada di tabel `users`
    // Tujuannya agar admin bisa membuatkan akun untuk alumni yang belum punya akun
    $res = mysqli_query($conn, "SELECT a.id_alumni, a.nama, a.nis FROM alumni a WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.id_alumni=a.id_alumni) ORDER BY a.nama");
    $alumniTanpaUser = mysqli_fetch_all($res, MYSQLI_ASSOC);

    // ==============================================================================
    // 2. PROSES TAMBAH AKUN PENGGUNA
    // ==============================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ambil data form
        $username  = trim($_POST['username']  ?? '');
        $password  = trim($_POST['password']  ?? '');
        $role      = trim($_POST['role']      ?? 'user');
        $id_alumni = trim($_POST['id_alumni'] ?? '');

        // Keamanan: Pastikan hanya superadmin yang bisa bikin akun admin lain
        // Jika dia admin biasa, dia cuma boleh bikin akun 'user'
        $allowedRoles = isSuperAdmin() ? ['user', 'admin'] : ['user'];
        if (!in_array($role, $allowedRoles)) $role = 'user'; // Paksa jadi 'user' jika melanggar

        // Validasi input
        if (!$username || !$password) {
            $error = 'Username dan password wajib diisi.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } else {
            // Cek apakah username sudah ada di database
            $s = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username=?");
            mysqli_stmt_bind_param($s, 's', $username);
            mysqli_stmt_execute($s);
            $sres = mysqli_stmt_get_result($s);
            mysqli_stmt_close($s);

            if (mysqli_fetch_assoc($sres)) {
                $error = 'Username sudah digunakan.';
            } else {
                // Enkripsi password menggunakan fungsi bawaan PHP agar aman tidak mudah dibajak
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // Ubah ID alumni jadi angka (integer), atau biarkan null jika tidak dipilih
                $alId   = ($id_alumni !== '') ? (int)$id_alumni : null;

                // Masukkan data akun baru ke database. Statusnya otomatis 'approved' karena admin yang buat
                $stmt = mysqli_prepare($conn, "INSERT INTO users (username,password,role,id_alumni,status) VALUES (?,?,?,?,'approved')");
                mysqli_stmt_bind_param($stmt, 'ssss', $username, $hashed, $role, $alId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $success = 'Pengguna berhasil ditambahkan.';
            }
        }
    }
    ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">Tambah Pengguna</h1>
                <p class="page-sub">Buat akun pengguna baru</p>
            </div>
            <a href="users.php" class="btn-outline">

                Kembali
            </a>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="section-card">
            <form method="POST" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Password <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="text" name="password" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Role</label>
                        <div class="input-wrapper">

                            <select name="role">
                                <option value="user">User (Alumni)</option>
                                <?php if (isSuperAdmin()): ?>
                                    <option value="admin">Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Hubungkan ke Alumni</label>
                        <div class="input-wrapper">

                            <?php $selectedId = $_GET['id_alumni'] ?? ''; ?>
                            <select name="id_alumni">
                                <option value="">-- Tidak dihubungkan --</option>
                                <?php foreach ($alumniTanpaUser as $a): ?>
                                    <option value="<?= $a['id_alumni'] ?>" <?= ($selectedId == $a['id_alumni']) ? 'selected' : '' ?>><?= htmlspecialchars($a['nama']) ?> (<?= htmlspecialchars($a['nis']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary">

                    Buat Pengguna
                </button>
            </form>
        </div>
    </div>
</body>

</html>