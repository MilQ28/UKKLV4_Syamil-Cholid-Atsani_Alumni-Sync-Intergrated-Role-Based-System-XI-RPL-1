<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Alumni — Alumni SMK</title>
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
    // PROSES TAMBAH DATA ALUMNI BARU
    // ==============================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Ambil data dari form dan bersihkan spasi yang tidak perlu dengan trim()
        $nis        = trim($_POST['nis']        ?? '');
        $nama       = trim($_POST['nama']       ?? '');
        $angkatan   = trim($_POST['angkatan']   ?? '');
        $jurusan    = trim($_POST['jurusan']    ?? '');
        $email      = trim($_POST['email']      ?? '');
        $no_hp      = trim($_POST['no_hp']      ?? '');
        $pekerjaan  = trim($_POST['pekerjaan']  ?? '');
        $perusahaan = trim($_POST['perusahaan'] ?? '');
        $alamat     = trim($_POST['alamat']     ?? '');

        // 2. Validasi input wajib (tidak boleh kosong)
        if (!$nis || !$nama || !$angkatan || !$jurusan || !$email || !$no_hp) {
            $error = 'Field wajib tidak boleh kosong.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // Cek format penulisan email
            $error = 'Format email tidak valid.';
        } else {
            // 3. Pengecekan Duplikasi Email
            // Cek apakah email yang dimasukkan sudah ada di tabel alumni
            $s = mysqli_prepare($conn, "SELECT id_alumni FROM alumni WHERE email=?");
            mysqli_stmt_bind_param($s, 's', $email);
            mysqli_stmt_execute($s);
            $sres = mysqli_stmt_get_result($s);
            mysqli_stmt_close($s);

            if (mysqli_fetch_assoc($sres)) {
                // Jika mysqli_fetch_assoc mengembalikan data, berarti email sudah ada!
                $error = 'Email sudah terdaftar.';
            } else {
                // 4. Proses Simpan Data
                // Jika email belum ada, kita bisa simpan data ke database
                $stmt = mysqli_prepare($conn, "INSERT INTO alumni (nis,nama,angkatan,jurusan,email,no_hp,pekerjaan,perusahaan,alamat) VALUES (?,?,?,?,?,?,?,?,?)");
                // 'ssissssss' artinya: string, string, integer, string, string, string, string, string, string
                mysqli_stmt_bind_param($stmt, 'ssissssss', $nis, $nama, $angkatan, $jurusan, $email, $no_hp, $pekerjaan, $perusahaan, $alamat);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $success = 'Data alumni berhasil ditambahkan.';
            }
        }
    }
    ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">Tambah Data Alumni</h1>
                <p class="page-sub">Input data alumni baru ke sistem</p>
            </div>
            <a href="dashboard_admin.php" class="btn-outline">

                Kembali
            </a>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="section-card">
            <form method="POST" class="auth-form">
                <div class="form-section-title">

                    Data Alumni
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>NIS <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="text" name="nis" required value="<?= htmlspecialchars($_POST['nis'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="text" name="nama" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Angkatan <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="number" name="angkatan" min="2000" max="<?= date('Y') ?>" required value="<?= htmlspecialchars($_POST['angkatan'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Jurusan <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <select name="jurusan" required>
                                <option value="">-- Pilih --</option>
                                <?php foreach ($jurusan_list as $j): ?>
                                    <option value="<?= htmlspecialchars($j) ?>" <?= (($_POST['jurusan'] ?? '') === $j) ? 'selected' : '' ?>><?= htmlspecialchars($j) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>No. HP <span class="req">*</span></label>
                        <div class="input-wrapper">

                            <input type="text" name="no_hp" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Pekerjaan</label>
                        <div class="input-wrapper">

                            <input type="text" name="pekerjaan" value="<?= htmlspecialchars($_POST['pekerjaan'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Perusahaan</label>
                        <div class="input-wrapper">

                            <input type="text" name="perusahaan" value="<?= htmlspecialchars($_POST['perusahaan'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <div class="input-wrapper">

                        <textarea name="alamat" rows="3"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-primary">

                    Simpan Data Alumni
                </button>
            </form>
        </div>
    </div>
</body>

</html>