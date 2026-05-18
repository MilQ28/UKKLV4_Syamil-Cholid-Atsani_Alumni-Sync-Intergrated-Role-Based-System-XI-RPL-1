<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — Portal Alumni SMK Telkom Lampung</title>
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
        header('Location: index.php');
        exit;
    }
    require 'koneksi.php';

    $error   = '';
    $success = '';

    $jurusan_list = [
        'Rekayasa Perangkat Lunak',
        'Teknik Komputer dan Jaringan',
        'Teknik Jaringan Akses dan Telekomunikasi',
        'Animasi',
    ];
    // ==============================================================================
    // PROSES PENDAFTARAN ALUMNI
    // ==============================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Ambil data dasar akun
        $jenis_daftar = $_POST['jenis_daftar'] ?? 'baru'; // Cek apakah dia alumni baru atau sudah ada datanya
        $username   = trim($_POST['username']   ?? '');
        $password   = trim($_POST['password']   ?? '');
        $confirm_pw = trim($_POST['confirm_pw'] ?? '');

        // 2. Validasi Input Dasar
        if (!$username || !$password) {
            $error = 'Username dan password wajib diisi.';
        } elseif ($password !== $confirm_pw) {
            $error = 'Password dan konfirmasi password tidak cocok.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } else {
            // 3. Cek apakah username sudah ada yang pakai
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);

            if (mysqli_fetch_assoc($res)) {
                $error = 'Username sudah digunakan.';
            } else {
                // 4. Proses berdasarkan jenis pendaftaran
                if ($jenis_daftar === 'lama') {
                    // JIKA ALUMNI LAMA (Datanya sudah diinputkan oleh Admin sebelumnya)
                    $id_alumni_pilihan = $_POST['id_alumni_pilihan'] ?? '';
                    if (!$id_alumni_pilihan) {
                        $error = 'Silakan pilih nama Anda dari daftar alumni.';
                    } else {
                        // Pastikan nama yang dipilih benar-benar valid dan belum punya akun
                        $stmtCek = mysqli_prepare($conn, "SELECT id_alumni FROM alumni WHERE id_alumni = ? AND id_alumni NOT IN (SELECT id_alumni FROM users WHERE id_alumni IS NOT NULL)");
                        mysqli_stmt_bind_param($stmtCek, 'i', $id_alumni_pilihan);
                        mysqli_stmt_execute($stmtCek);
                        $resCek = mysqli_stmt_get_result($stmtCek);
                        if (!mysqli_fetch_assoc($resCek)) {
                            $error = 'Data alumni tidak valid atau sudah memiliki akun.';
                        }
                        mysqli_stmt_close($stmtCek);

                        // Jika lolos pengecekan, buatkan akun user-nya
                        if (!$error) {
                            $hashed = password_hash($password, PASSWORD_DEFAULT); // Enkripsi password
                            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (username, password, role, id_alumni, status) VALUES (?,?,'user',?,'pending')");
                            mysqli_stmt_bind_param($stmt2, 'ssi', $username, $hashed, $id_alumni_pilihan);
                            if (mysqli_stmt_execute($stmt2)) {
                                $success = true; // Berhasil!
                            } else {
                                $error = 'Terjadi kesalahan saat membuat akun. Silakan coba lagi.';
                            }
                            mysqli_stmt_close($stmt2);
                        }
                    }
                } else {
                    // JIKA ALUMNI BARU (Datanya belum ada di sistem sama sekali)
                    // Ambil semua data biodata dari form
                    $nis        = trim($_POST['nis']        ?? '');
                    $nama       = trim($_POST['nama']       ?? '');
                    $angkatan   = trim($_POST['angkatan']   ?? '');
                    $jurusan    = trim($_POST['jurusan']    ?? '');
                    $email      = trim($_POST['email']      ?? '');
                    $no_hp      = trim($_POST['no_hp']      ?? '');
                    $pekerjaan  = trim($_POST['pekerjaan']  ?? '');
                    $perusahaan = trim($_POST['perusahaan'] ?? '');
                    $alamat     = trim($_POST['alamat']     ?? '');

                    // Validasi data biodata
                    if (!$nis || !$nama || !$angkatan || !$jurusan || !$email || !$no_hp) {
                        $error = 'Harap lengkapi semua field alumni yang wajib diisi.';
                    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error = 'Format email tidak valid.';
                    } elseif ($angkatan < 2000 || $angkatan > date('Y')) {
                        $error = 'Tahun angkatan tidak valid.';
                    } else {
                        // Cek apakah email sudah pernah didaftarkan
                        $stmt = mysqli_prepare($conn, "SELECT id_alumni FROM alumni WHERE email = ?");
                        mysqli_stmt_bind_param($stmt, 's', $email);
                        mysqli_stmt_execute($stmt);
                        $res = mysqli_stmt_get_result($stmt);
                        mysqli_stmt_close($stmt);

                        if (mysqli_fetch_assoc($res)) {
                            $error = 'Email ini sudah terdaftar di data alumni.';
                        } else {
                            // Jika aman, masukkan data biodata ke tabel `alumni`
                            $stmt = mysqli_prepare($conn, "INSERT INTO alumni (nis, nama, angkatan, jurusan, email, no_hp, pekerjaan, perusahaan, alamat) VALUES (?,?,?,?,?,?,?,?,?)");
                            mysqli_stmt_bind_param($stmt, 'ssissssss', $nis, $nama, $angkatan, $jurusan, $email, $no_hp, $pekerjaan, $perusahaan, $alamat);

                            if (mysqli_stmt_execute($stmt)) {
                                // Ambil ID alumni yang baru saja dibuat
                                $id_alumni = mysqli_insert_id($conn);
                                mysqli_stmt_close($stmt);

                                // Lalu buatkan akun user-nya di tabel `users` (terhubung dengan id_alumni tadi)
                                $hashed = password_hash($password, PASSWORD_DEFAULT);
                                $stmt2 = mysqli_prepare($conn, "INSERT INTO users (username, password, role, id_alumni, status) VALUES (?,?,'user',?,'pending')");
                                mysqli_stmt_bind_param($stmt2, 'ssi', $username, $hashed, $id_alumni);

                                if (mysqli_stmt_execute($stmt2)) {
                                    $success = true; // Pendaftaran selesai!
                                } else {
                                    $error = 'Terjadi kesalahan saat membuat akun.';
                                }
                                mysqli_stmt_close($stmt2);
                            } else {
                                $error = 'Terjadi kesalahan. Silakan coba lagi.';
                                mysqli_stmt_close($stmt);
                            }
                        }
                    }
                }
            }
        }
    }

    // Ambil data alumni yang belum memiliki akun untuk pilihan dropdown
    $resAlumni = mysqli_query($conn, "SELECT id_alumni, nis, nama FROM alumni WHERE id_alumni NOT IN (SELECT id_alumni FROM users WHERE id_alumni IS NOT NULL) ORDER BY nama ASC");
    $alumniTanpaAkun = mysqli_fetch_all($resAlumni, MYSQLI_ASSOC);
    ?>
    <div class="auth-bg register-bg" style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background-image: url('assets/img/bg-sekolah.jpg'); background-size: cover; background-position: center;">
        <div class="auth-card auth-card-wide" style="width: 100%; max-width: 1000px; margin: 20px; background: rgba(255,255,255,0.95); padding: 30px; border-radius: 8px; box-shadow: var(--shadow); margin:50px;">
            <div class="auth-card-header" style="text-align: center; margin-bottom: 20px;">
                <div class="brand-logo" style="margin-bottom: 10px;">
                    <img src="assets/img/telkom-logo.jpg" alt="Logo SMK Telkom Lampung" style="width: 60px; height: 60px; object-fit: contain;">
                </div>
                <h1 class="brand-name" style="font-size: 1.5rem; margin-bottom: 5px; color: #C0000C;">SMK Telkom Lampung</h1>
                <p class="brand-tagline" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Daftarkan akun alumni Anda</p>
            </div>
            <?php if ($success): ?>
                <div class="success-screen">
                    <div class="success-icon">

                    </div>
                    <h2>Pendaftaran Berhasil!</h2>
                    <p>Data alumni Anda telah berhasil didaftarkan. Akun Anda sedang <strong>menunggu verifikasi</strong> dari administrator sekolah.</p>
                    <p class="success-note">Anda akan mendapat konfirmasi setelah akun diverifikasi. Harap bersabar.</p>
                    <a href="login.php" class="btn-primary">Kembali ke Login</a>
                </div>

            <?php else: ?>
                <div style="margin-bottom: 20px;">
                    <a href="login.php" style="display:inline-flex; align-items:center; gap:6px; color:var(--text-muted); text-decoration:none; font-size:14px; font-weight:500; transition:color .2s;">

                        Kembali ke Login
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">

                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form" id="registerForm">

                    <div class="form-section-title" style="border-bottom:none; margin-bottom:4px;">
                        Pilih Jenis Pendaftaran
                    </div>
                    <div class="type-selector-grid">
                        <label class="type-card" style="display: flex;">
                            <input type="radio" name="jenis_daftar" value="baru" checked onchange="toggleForm()" style="margin-right: 5px;">
                            <div class="type-content">

                                <span class="type-title">Saya Alumni Baru, </span>
                                <span class="type-desc">Isi form data lengkap.</span>
                            </div>
                        </label>
                        <label class="type-card" id="cardLama" style="display: flex;">
                            <input type="radio" name="jenis_daftar" value="lama" onchange="toggleForm()" <?= (isset($_POST['jenis_daftar']) && $_POST['jenis_daftar'] == 'lama') ? 'checked' : '' ?> style="margin-right: 5px;">
                            <div class="type-content">

                                <span class="type-title">Saya Sudah Terdaftar,</span>
                                <span class="type-desc">cukup tautkan nama Anda.</span>
                            </div>
                        </label>
                    </div>

                    <div class="form-section-title">

                        Informasi Akun
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username <span class="req">*</span></label>
                            <div class="input-wrapper">

                                <input type="text" name="username" placeholder="username unik Anda" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password <span class="req">*</span></label>
                            <div class="input-wrapper">

                                <input type="password" name="password" placeholder="Min. 6 karakter" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password <span class="req">*</span></label>
                            <div class="input-wrapper">

                                <input type="password" name="confirm_pw" placeholder="Ulangi password" required>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Alumni Sudah Terdaftar -->
                    <div id="sectionAlumniLama" class="form-section-animated" style="display:none;">
                        <div class="form-section-title">

                            Pilih Data Alumni Anda
                        </div>
                        <div class="form-group">
                            <label>Nama Alumni <span class="req">*</span></label>
                            <div class="input-wrapper">

                                <select name="id_alumni_pilihan" id="id_alumni_pilihan">
                                    <option value="">-- Cari Nama Anda --</option>
                                    <?php foreach ($alumniTanpaAkun as $a): ?>
                                        <option value="<?= $a['id_alumni'] ?>" <?= (isset($_POST['id_alumni_pilihan']) && $_POST['id_alumni_pilihan'] == $a['id_alumni']) ? 'selected' : '' ?>><?= htmlspecialchars($a['nama']) ?> (<?= htmlspecialchars($a['nis']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Alumni Baru -->
                    <div id="sectionAlumniBaru" class="form-section-animated">
                        <div class="form-section-title">

                            Data Alumni (untuk Verifikasi)
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>NIS / No. Induk <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <input type="text" name="nis" placeholder="Nomor Induk Siswa" required value="<?= htmlspecialchars($_POST['nis'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Nama Lengkap <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <input type="text" name="nama" placeholder="Nama sesuai ijazah" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Angkatan / Tahun Lulus <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <input type="number" name="angkatan" placeholder="Contoh: 2022" min="2000" max="<?= date('Y') ?>" required value="<?= htmlspecialchars($_POST['angkatan'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Jurusan <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <select name="jurusan" required>
                                        <option value="">-- Pilih Jurusan --</option>
                                        <?php foreach ($jurusan_list as $j): ?>
                                            <option value="<?= htmlspecialchars($j) ?>" <?= (($_POST['jurusan'] ?? '') === $j) ? 'selected' : '' ?>><?= htmlspecialchars($j) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Aktif <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <input type="email" name="email" placeholder="email@aktif.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>No. HP / WhatsApp <span class="req">*</span></label>
                                <div class="input-wrapper">

                                    <input type="text" name="no_hp" placeholder="08xxxxxxxxxx" required value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-section-title">

                            Informasi Karir (Opsional)
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Pekerjaan Saat Ini</label>
                                <div class="input-wrapper">

                                    <input type="text" name="pekerjaan" placeholder="Profesi / jabatan Anda" value="<?= htmlspecialchars($_POST['pekerjaan'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Perusahaan / Instansi</label>
                                <div class="input-wrapper">

                                    <input type="text" name="perusahaan" placeholder="Nama perusahaan / instansi" value="<?= htmlspecialchars($_POST['perusahaan'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Alamat Sekarang</label>
                            <div class="input-wrapper">

                                <textarea name="alamat" placeholder="Jl. ..." rows="3"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div> <!-- End sectionAlumniBaru -->


                    <div class="info-box">

                        <p>Pendaftaran Anda akan diverifikasi oleh admin sekolah. Akun baru dapat digunakan setelah disetujui.</p>
                    </div>

                    <button type="submit" class="btn-primary btn-full">
                        Kirim Pendaftaran

                    </button>
                </form>

                <div class="auth-footer">
                    <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function toggleForm() {
            const jenis = document.querySelector('input[name="jenis_daftar"]:checked').value;
            const sBaru = document.getElementById('sectionAlumniBaru');
            const sLama = document.getElementById('sectionAlumniLama');

            const baruInputs = sBaru.querySelectorAll('input, select, textarea');
            const lamaSelect = document.getElementById('id_alumni_pilihan');

            if (jenis === 'lama') {
                sBaru.style.display = 'none';
                sLama.style.display = 'block';
                baruInputs.forEach(el => {
                    if (el.name !== 'pekerjaan' && el.name !== 'perusahaan' && el.name !== 'alamat') {
                        el.removeAttribute('required');
                    }
                });
                lamaSelect.setAttribute('required', 'required');
            } else {
                sBaru.style.display = 'block';
                sLama.style.display = 'none';
                baruInputs.forEach(el => {
                    if (el.name !== 'pekerjaan' && el.name !== 'perusahaan' && el.name !== 'alamat') {
                        el.setAttribute('required', 'required');
                    }
                });
                lamaSelect.removeAttribute('required');
            }
        }
        // Initialize form state
        toggleForm();
    </script>
</body>

</html>