<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil — Alumni SMK</title>
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
    requireLogin();
    include 'navbar.php';

    $error   = '';
    $success = '';
    $id_alumni = $_SESSION['id_alumni'];

    if (!$id_alumni && !isAdmin()) {
        echo '<div class="page-wrapper"><div class="alert alert-error">Anda tidak memiliki data alumni yang terhubung.</div></div>';
        include 'footer.php';
        exit;
    }

    // Admin bisa lihat profil alumni by ?id=
    $target_id = $id_alumni;
    if (isAdmin() && isset($_GET['id'])) {
        $target_id = (int)$_GET['id'];
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM alumni WHERE id_alumni = ?");
    mysqli_stmt_bind_param($stmt, 'i', $target_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $alumni = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$alumni) {
        echo '<div class="page-wrapper"><div class="alert alert-error">Data alumni tidak ditemukan.</div></div>';
        exit;
    }

    $canEdit = ($target_id == $id_alumni) || isAdmin();

    $jurusan_list = [
        'Rekayasa Perangkat Lunak',
        'Teknik Komputer dan Jaringan',
        'Teknik Jaringan Akses dan Telekomunikasi',
        'Animasi',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
        // Handle foto upload
        if (!empty($_FILES['foto']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal 2MB.';
            } else {
                $filename = 'foto_' . $target_id . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/foto_profil/$filename");
                if ($alumni['foto_profil'] && file_exists("uploads/foto_profil/" . $alumni['foto_profil'])) {
                    unlink("uploads/foto_profil/" . $alumni['foto_profil']);
                }
                $stmt = mysqli_prepare($conn, "UPDATE alumni SET foto_profil=? WHERE id_alumni=?");
                mysqli_stmt_bind_param($stmt, 'si', $filename, $target_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $alumni['foto_profil'] = $filename;
            }
        }

        if (!$error) {
            $nama       = trim($_POST['nama']       ?? '');
            $angkatan   = trim($_POST['angkatan']   ?? '');
            $jurusan    = trim($_POST['jurusan']    ?? '');
            $email      = trim($_POST['email']      ?? '');
            $no_hp      = trim($_POST['no_hp']      ?? '');
            $pekerjaan  = trim($_POST['pekerjaan']  ?? '');
            $perusahaan = trim($_POST['perusahaan'] ?? '');
            $alamat     = trim($_POST['alamat']     ?? '');

            if (!$nama || !$angkatan || !$jurusan || !$email || !$no_hp) {
                $error = 'Field wajib tidak boleh kosong.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Format email tidak valid.';
            } else {
                // Cek email duplikat (selain dirinya)
                $s = mysqli_prepare($conn, "SELECT id_alumni FROM alumni WHERE email=? AND id_alumni!=?");
                mysqli_stmt_bind_param($s, 'si', $email, $target_id);
                mysqli_stmt_execute($s);
                $sres = mysqli_stmt_get_result($s);
                mysqli_stmt_close($s);

                if (mysqli_fetch_assoc($sres)) {
                    $error = 'Email sudah digunakan alumni lain.';
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE alumni SET nama=?,angkatan=?,jurusan=?,email=?,no_hp=?,pekerjaan=?,perusahaan=?,alamat=? WHERE id_alumni=?");
                    mysqli_stmt_bind_param($stmt, 'sissssssi', $nama, $angkatan, $jurusan, $email, $no_hp, $pekerjaan, $perusahaan, $alamat, $target_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $success = 'Profil berhasil diperbarui.';
                    header('Location: ' . (isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php'));
                    exit;

                    $stmt = mysqli_prepare($conn, "SELECT * FROM alumni WHERE id_alumni=?");
                    mysqli_stmt_bind_param($stmt, 'i', $target_id);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    $alumni = mysqli_fetch_assoc($res);
                    mysqli_stmt_close($stmt);
                }
            }
        }

        // Ganti password
        if (!$error && !empty($_POST['new_password'])) {
            $new_pw  = $_POST['new_password'];
            $conf_pw = $_POST['confirm_new_password'] ?? '';
            if (strlen($new_pw) < 6) {
                $error = 'Password baru minimal 6 karakter.';
            } elseif ($new_pw !== $conf_pw) {
                $error = 'Konfirmasi password tidak cocok.';
            } else {
                $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id_alumni=?");
                mysqli_stmt_bind_param($stmt, 'si', $hashed, $target_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $success = 'Profil dan password berhasil diperbarui.';
                header('Location: ' . (isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php'));
                exit;
            }
        }
    }
    ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">Profil Alumni</h1>
                <p class="page-sub">Data pribadi dan informasi karir</p>
            </div>
            <?php if (isAdmin()): ?>
                <a href="<?= isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php' ?>" class="btn-outline">

                    Kembali
                </a>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">

                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">

                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <?php if ($canEdit): ?>
            <div class="profile-main">
                <div class="section-card">
                    <div class="section-head">
                        <h2>Profil & Edit Data</h2>
                    </div>

                    <!-- Info Profil Terintegrasi -->
                    <div class="profile-info-merged" style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                        <div class="profile-avatar-wrap" style="position: relative; width: 100px; height: 100px; margin: 0 auto 15px;">
                            <?php if ($alumni['foto_profil'] && file_exists("uploads/foto_profil/" . $alumni['foto_profil'])): ?>
                                <img src="uploads/foto_profil/<?= htmlspecialchars($alumni['foto_profil']) ?>" alt="Foto Profil" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <img src="assets/img/default-avatar.png" alt="Foto Profil" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                            <?php endif; ?>
                            <?php if ($canEdit): ?>
                                <label for="fotoInput" class="foto-overlay" style="position: absolute; bottom: 0; right: 0; background: var(--primary); color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">Ganti Foto</label>
                            <?php endif; ?>
                        </div>
                        <h3 style="margin-bottom: 5px; color: var(--text-color);"><?= htmlspecialchars($alumni['nama']) ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem;"><?= htmlspecialchars($alumni['jurusan']) ?> · Angkatan <?= $alumni['angkatan'] ?></p>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="auth-form">
                        <input type="file" id="fotoInput" name="foto" accept="image/*" style="display:none" onchange="previewFoto(this)">

                        <div class="form-section-title">

                            Data Pribadi
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Lengkap <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <input type="text" name="nama" value="<?= htmlspecialchars($alumni['nama']) ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>NIS (tidak bisa diubah)</label>
                                <div class="input-wrapper">

                                    <input type="text" value="<?= htmlspecialchars($alumni['nis']) ?>" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Angkatan <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <input type="number" name="angkatan" value="<?= $alumni['angkatan'] ?>" min="2000" max="<?= date('Y') ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Jurusan <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <select name="jurusan" required>
                                        <?php foreach ($jurusan_list as $j): ?>
                                            <option value="<?= htmlspecialchars($j) ?>" <?= $alumni['jurusan'] === $j ? 'selected' : '' ?>><?= htmlspecialchars($j) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Email <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <input type="email" name="email" value="<?= htmlspecialchars($alumni['email']) ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>No. HP <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <input type="text" name="no_hp" value="<?= htmlspecialchars($alumni['no_hp']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section-title">

                            Informasi Karir
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Pekerjaan</label>
                                <div class="input-wrapper">

                                    <input type="text" name="pekerjaan" value="<?= htmlspecialchars($alumni['pekerjaan'] ?? '') ?>" placeholder="Profesi Anda">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Perusahaan</label>
                                <div class="input-wrapper">

                                    <input type="text" name="perusahaan" value="<?= htmlspecialchars($alumni['perusahaan'] ?? '') ?>" placeholder="Nama perusahaan">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Alamat</label>
                            <div class="input-wrapper">

                                <textarea name="alamat" rows="3" placeholder="Alamat lengkap"><?= htmlspecialchars($alumni['alamat'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="form-section-title">

                            Ganti Password (opsional)
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Password Baru</label>
                                <div class="input-wrapper">

                                    <input type="password" name="new_password" placeholder="Kosongkan jika tidak ingin ganti">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Konfirmasi Password Baru</label>
                                <div class="input-wrapper">

                                    <input type="password" name="confirm_new_password" placeholder="Ulangi password baru">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">

                            Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function previewFoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const avatarEl = document.querySelector('.profile-avatar, .profile-avatar-placeholder');
                    if (avatarEl) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'profile-avatar';
                        avatarEl.replaceWith(img);
                    }
                };
                reader.readAsDataURL(input.files[0]);
                input.form.submit();
            }
        }
    </script>
</body>

</html>