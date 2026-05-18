<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda — Portal Alumni SMK Telkom Lampung</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <link rel="stylesheet" href="assets/css/components.css?v=2">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=2">
    <link rel="stylesheet" href="assets/css/responsive.css?v=2">
</head>

<body>
    <?php
    session_start();
    require 'auth.php';
    require 'koneksi.php';
    requireLogin(); // Wajib login untuk akses halaman ini

    // Jika yang login adalah admin, lempar ke dashboard admin
    if (isAdmin()) {
        header('Location: dashboard_admin.php');
        exit;
    }
    include 'navbar.php';

    // Data profil user saat ini
    $id_alumni = $_SESSION['id_alumni']; // Ambil ID alumni dari session saat login
    $myData = null;

    if ($id_alumni) {
        // Ambil data detail alumni dari database berdasarkan ID
        $stmt = mysqli_prepare($conn, "SELECT * FROM alumni WHERE id_alumni = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id_alumni);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $myData = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    // Pencarian & Filter
    $search  = trim($_GET['search'] ?? '');   // Kata kunci pencarian
    $jurusan = trim($_GET['jurusan'] ?? ''); // Filter berdasarkan jurusan

    $where   = []; // Array untuk menyimpan kondisi query (WHERE)
    $params  = []; // Array untuk menyimpan nilai parameter pencarian
    $types   = ''; // Tipe data parameter (s = string)

    // Jika user mengetikkan sesuatu di kotak pencarian
    if ($search) {
        $where[] = "(nama LIKE ? OR nis LIKE ? OR pekerjaan LIKE ?)";
        $s = "%$search%"; // Tambahkan % agar bisa mencari kata yang mengandung huruf tersebut
        $params = array_merge($params, [$s, $s, $s]);
        $types .= 'sss'; // Tiga parameter string
    }

    // Jika user memilih jurusan dari dropdown
    if ($jurusan) {
        $where[] = "jurusan = ?";
        $params[] = $jurusan;
        $types .= 's'; // Satu parameter string
    }

    // 3. Susun query SQL akhir
    $sql = "SELECT * FROM alumni" . ($where ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);

    // Jika ada parameter pencarian, pasangkan parameternya ke query
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $alumniList = mysqli_fetch_all($res, MYSQLI_ASSOC); // Ambil semua hasil dalam bentuk array
    mysqli_stmt_close($stmt);

    // 4. Ambil daftar semua jurusan yang ada untuk diisi ke dalam dropdown filter
    $res2 = mysqli_query($conn, "SELECT DISTINCT jurusan FROM alumni ORDER BY jurusan");
    $jurusanList = [];
    while ($row = mysqli_fetch_assoc($res2)) {
        $jurusanList[] = $row['jurusan'];
    }
    ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">Data Alumni SMK</h1>
                <p class="page-sub">Temukan informasi alumni sekolah kami</p>
            </div>
            <?php if ($myData): ?>
                <a href="profile.php" class="btn-primary">

                    Profil Saya
                </a>
            <?php endif; ?>
        </div>

        <?php if ($myData): ?>
            <div class="my-profile-banner" style="background-color: #ffffff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
                <div class="profile-banner-inner">
                    <div class="profile-banner-avatar">
                        <?php if ($myData['foto_profil'] && file_exists("uploads/foto_profil/" . $myData['foto_profil'])): ?>
                            <img src="uploads/foto_profil/<?= htmlspecialchars($myData['foto_profil']) ?>" alt="Foto" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <img src="assets/img/default-avatar.png" alt="Foto Profil" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    <div class="profile-banner-info">
                        <h3><?= htmlspecialchars($myData['nama']) ?></h3>
                        <p><?= htmlspecialchars($myData['jurusan']) ?> · Angkatan <?= $myData['angkatan'] ?></p>
                        <?php if ($myData['pekerjaan']): ?>
                            <p class="banner-job">

                                <?= htmlspecialchars($myData['pekerjaan']) ?><?= $myData['perusahaan'] ? ' di ' . htmlspecialchars($myData['perusahaan']) : '' ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <a href="profile.php" class="btn-outline">Edit Profil Saya</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search & Filter -->
        <div class="section-card" style="background-color: #ffffff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
            <form method="GET" class="search-form" style="display: flex; width: 100%; justify-content: space-between; align-items: center;">
                <div class="input-wrapper">

                    <input type="text" name="search" placeholder="Cari nama, NIS, pekerjaan..." value="<?= htmlspecialchars($search) ?>" style="width:1500px">
                </div>
                <div class="input-wrapper">

                    <select name="jurusan">
                        <option value="">Semua Jurusan</option>
                        <?php foreach ($jurusanList as $j): ?>
                            <option value="<?= htmlspecialchars($j) ?>" <?= $jurusan === $j ? 'selected' : '' ?>><?= htmlspecialchars($j) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="padding: 5px; border-radius: 4px; font-size: 14px; width:100px;">Cari</button>
                <?php if ($search || $jurusan): ?><a href="dashboard_user.php" class="btn-outline">Reset</a><?php endif; ?>
            </form>
        </div>

        <!-- Alumni Grid -->
        <div class="alumni-grid">
            <?php if (empty($alumniList)): ?>
                <div class="empty-state">

                    <p>Tidak ada alumni yang ditemukan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($alumniList as $a): ?>
                    <div class="alumni-card <?= ($a['id_alumni'] == $id_alumni) ? 'my-card' : '' ?>" style="background-color: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                        <div class="alumni-card-top">
                            <div class="alumni-avatar">
                                <?php if ($a['foto_profil'] && file_exists("uploads/foto_profil/" . $a['foto_profil'])): ?>
                                    <img src="uploads/foto_profil/<?= htmlspecialchars($a['foto_profil']) ?>" alt="Foto" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="avatar-letter" style="width: 50px; height: 50px; border-radius: 50%; background-color: #eee; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #C0000C; font-size: 1.2rem;">
                                        <?= strtoupper(substr($a['nama'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($a['id_alumni'] == $id_alumni): ?>
                                <span class="my-badge">Saya</span>
                            <?php endif; ?>
                        </div>
                        <div class="alumni-card-body">
                            <h4><?= htmlspecialchars($a['nama']) ?></h4>
                            <p class="alumni-nis"><code><?= htmlspecialchars($a['nis']) ?></code></p>
                            <span class="tag"><?= htmlspecialchars($a['jurusan']) ?></span>
                            <div class="alumni-meta">
                                <span>

                                    <?= $a['angkatan'] ?>
                                </span>
                                <?php if ($a['pekerjaan']): ?>
                                    <span>

                                        <?= htmlspecialchars($a['pekerjaan']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($a['id_alumni'] == $id_alumni): ?>
                                <a href="profile.php" class="btn-sm btn-edit" style="margin-top:10px;display:inline-flex">

                                    Edit Data Saya
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="assets/js/bg-slideshow-dashboard.js"></script>
</body>

</html>