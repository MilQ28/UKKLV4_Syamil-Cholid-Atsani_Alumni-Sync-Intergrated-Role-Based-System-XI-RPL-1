<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Alumni — Alumni SMK</title>
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
    requireAdmin(); // Wajib admin
    include 'navbar.php';

    // ==============================================================================
    // 1. MENGAMBIL DATA ALUMNI YANG MAU DI-EDIT
    // ==============================================================================
    // Ambil ID dari URL (contoh: edit.php?id=5)
    $id = (int)($_GET['id'] ?? 0);

    $stmt = mysqli_prepare($conn, "SELECT * FROM alumni WHERE id_alumni=?");
    mysqli_stmt_bind_param($stmt, 'i', $id); // 'i' = integer (angka)
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $alumni = mysqli_fetch_assoc($res); // Simpan data di variabel $alumni
    mysqli_stmt_close($stmt);

    // Jika data tidak ada di database (mungkin id salah)
    if (!$alumni) {
        echo '<div class="page-wrapper"><div class="alert alert-error">Data tidak ditemukan.</div></div>';
        exit; // Hentikan eksekusi
    }

    $error = $success = '';
    $jurusan_list = [
        'Rekayasa Perangkat Lunak',
        'Teknik Komputer dan Jaringan',
        'Teknik Jaringan Akses dan Telekomunikasi',
        'Animasi',
    ];

    // ==============================================================================
    // 2. PROSES UPDATE DATA SAAT FORM DI-SUBMIT
    // ==============================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ambil data baru dari form
        $nis        = trim($_POST['nis']        ?? '');
        $nama       = trim($_POST['nama']       ?? '');
        $angkatan   = trim($_POST['angkatan']   ?? '');
        $jurusan    = trim($_POST['jurusan']    ?? '');
        $email      = trim($_POST['email']      ?? '');
        $no_hp      = trim($_POST['no_hp']      ?? '');
        $pekerjaan  = trim($_POST['pekerjaan']  ?? '');
        $perusahaan = trim($_POST['perusahaan'] ?? '');
        $alamat     = trim($_POST['alamat']     ?? '');

        // Validasi data wajib
        if (!$nis || !$nama || !$angkatan || !$jurusan || !$email || !$no_hp) {
            $error = 'Field wajib tidak boleh kosong.';
        } else {
            // Cek apakah email yang baru dimasukkan sudah dipakai orang lain
            // id_alumni != ? artinya "cek email ini di data lain selain milik saya sendiri"
            $s = mysqli_prepare($conn, "SELECT id_alumni FROM alumni WHERE email=? AND id_alumni!=?");
            mysqli_stmt_bind_param($s, 'si', $email, $id);
            mysqli_stmt_execute($s);
            $sres = mysqli_stmt_get_result($s);
            mysqli_stmt_close($s);

            if (mysqli_fetch_assoc($sres)) {
                $error = 'Email sudah digunakan oleh alumni lain.';
            } else {
                // Jika email aman, lakukan UPDATE ke database
                $stmt = mysqli_prepare($conn, "UPDATE alumni SET nis=?,nama=?,angkatan=?,jurusan=?,email=?,no_hp=?,pekerjaan=?,perusahaan=?,alamat=? WHERE id_alumni=?");
                // s = string, i = integer
                mysqli_stmt_bind_param($stmt, 'ssissssssi', $nis, $nama, $angkatan, $jurusan, $email, $no_hp, $pekerjaan, $perusahaan, $alamat, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $success = 'Data berhasil diperbarui.';

                // Ambil ulang data terbaru dari database untuk ditampilkan di form (agar ter-refresh)
                $stmt = mysqli_prepare($conn, "SELECT * FROM alumni WHERE id_alumni=?");
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $alumni = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
            }
        }
    }
    ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">Edit Data Alumni</h1>
                <p class="page-sub">Perbarui data alumni: <?= htmlspecialchars($alumni['nama']) ?></p>
            </div>
            <a href="dashboard_admin.php" class="btn-outline">

                Kembali
            </a>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="section-card">
            <form method="POST" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>NIS <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="text" name="nis" required value="<?= htmlspecialchars($alumni['nis']) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="text" name="nama" required value="<?= htmlspecialchars($alumni['nama']) ?>">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Angkatan <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="number" name="angkatan" min="2000" max="<?= date('Y') ?>" required value="<?= $alumni['angkatan'] ?>">
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

                            <input type="email" name="email" required value="<?= htmlspecialchars($alumni['email']) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>No. HP <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="text" name="no_hp" value="<?= htmlspecialchars($alumni['no_hp']) ?>">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Pekerjaan</label>
                        <div class="input-wrapper">

                            <input type="text" name="pekerjaan" value="<?= htmlspecialchars($alumni['pekerjaan'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Perusahaan</label>
                        <div class="input-wrapper">

                            <input type="text" name="perusahaan" value="<?= htmlspecialchars($alumni['perusahaan'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <div class="input-wrapper">

                        <textarea name="alamat" rows="3"><?= htmlspecialchars($alumni['alamat'] ?? '') ?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary">

                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</body>

</html>