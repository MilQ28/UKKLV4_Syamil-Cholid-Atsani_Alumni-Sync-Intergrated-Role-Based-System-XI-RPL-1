<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Portal Alumni SMK Telkom Lampung</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>

<body style="background-image: url(./assets/bg-sekolah-3.jpeg);">
    <?php
    session_start();
    require 'auth.php';
    require 'koneksi.php';
    requireAdmin(); // Pastikan hanya admin yang bisa buka halaman ini
    include 'navbar.php';

    // Statistik dashboard

    // Total alumni
    $res = mysqli_query($conn, "SELECT COUNT(*) FROM alumni");
    $totalAlumni = mysqli_fetch_row($res)[0];

    // Total user
    $res = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='user'");
    $totalUsers = mysqli_fetch_row($res)[0];

    // Akun pending
    $res = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE status='pending'");
    $pending = mysqli_fetch_row($res)[0];

    // Statistik jurusan
    $res = mysqli_query($conn, "SELECT jurusan, COUNT(*) as total FROM alumni GROUP BY jurusan ORDER BY total DESC LIMIT 5");
    $jurusanStat = mysqli_fetch_all($res, MYSQLI_ASSOC);

    // Statistik angkatan
    $res = mysqli_query($conn, "SELECT angkatan, COUNT(*) as total FROM alumni GROUP BY angkatan ORDER BY angkatan DESC LIMIT 6");
    $angkatanStat = mysqli_fetch_all($res, MYSQLI_ASSOC);

    // Alumni baru
    $res = mysqli_query($conn, "SELECT * FROM alumni ORDER BY created_at DESC LIMIT 8");
    $recentAlumni = mysqli_fetch_all($res, MYSQLI_ASSOC);

    // Akun pending (approval)
    $res = mysqli_query($conn, "SELECT u.*, a.nama, a.nis, a.jurusan, a.angkatan FROM users u LEFT JOIN alumni a ON u.id_alumni=a.id_alumni WHERE u.status='pending' ORDER BY u.created_at DESC");
    $pendingUsers = mysqli_fetch_all($res, MYSQLI_ASSOC);

    // Hitung alumni belum punya akun
    $resBelum = mysqli_query($conn, "SELECT COUNT(*) FROM alumni a LEFT JOIN users u ON a.id_alumni = u.id_alumni WHERE u.id_alumni IS NULL");
    $totalBelumTerdaftar = mysqli_fetch_row($resBelum)[0];

    // 9. Ambil 5 data alumni yang belum punya akun
    $resDataBelum = mysqli_query($conn, "SELECT a.id_alumni, a.nis, a.nama, a.jurusan, a.angkatan FROM alumni a LEFT JOIN users u ON a.id_alumni = u.id_alumni WHERE u.id_alumni IS NULL ORDER BY a.nama ASC LIMIT 5");
    $alumniBelumTerdaftar = mysqli_fetch_all($resDataBelum, MYSQLI_ASSOC);
    ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">Dashboard</h1>
            </div>
            <div class="header-actions">
                <a href="tambah.php" class="btn-primary" style="padding: 5px; border-radius: 5px;">

                    Tambah Alumni
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card stat-blue">
                <div class="stat-icon">

                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format($totalAlumni) ?></span>
                    <span class="stat-label">Total Alumni</span>
                </div>
            </div>
            <div class="stat-card stat-green">
                <div class="stat-icon">

                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format($totalUsers) ?></span>
                    <span class="stat-label">Pengguna Aktif</span>
                </div>
            </div>
            <div class="stat-card stat-amber <?= $pending > 0 ? 'stat-pulse' : '' ?>">
                <div class="stat-icon">

                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format($pending) ?></span>
                    <span class="stat-label">Menunggu Verifikasi</span>
                </div>
            </div>
            <div class="stat-card stat-purple">
                <div class="stat-icon">

                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= count($jurusanStat) ?></span>
                    <span class="stat-label">Jurusan Terdaftar</span>
                </div>
            </div>
        </div>

        <?php if ($pending > 0): ?>
            <!-- Pending Verifikasi -->
            <div class="section-card">
                <div class="section-head">
                    <h2>

                        Menunggu Verifikasi
                        <span class="badge badge-amber"><?= $pending ?></span>
                    </h2>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Jurusan</th>
                                <th>Angkatan</th>
                                <th>Username</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingUsers as $pu): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($pu['nis'] ?? '-') ?></code></td>
                                    <td><?= htmlspecialchars($pu['nama'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($pu['jurusan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($pu['angkatan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($pu['username']) ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="delete_user.php?action=approve&id=<?= $pu['user_id'] ?>" class="btn-sm btn-success" onclick="return confirm('Setujui pendaftaran ini?')">

                                                Setujui
                                            </a>
                                            <a href="delete_user.php?action=reject&id=<?= $pu['user_id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Tolak pendaftaran ini?')">

                                                Tolak
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($totalBelumTerdaftar > 0): ?>
            <!-- Alumni Belum Punya Akun -->
            <div class="section-card">
                <div class="section-head">
                    <h2>

                        Alumni Belum Memiliki Akun
                        <span class="badge badge-amber"><?= $totalBelumTerdaftar ?></span>
                    </h2>
                    <a href="alumni_tanpa_akun.php" class="btn-outline-sm">Lihat Semua</a>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Jurusan</th>
                                <th>Angkatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumniBelumTerdaftar as $abt): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($abt['nis'] ?? '-') ?></code></td>
                                    <td><?= htmlspecialchars($abt['nama'] ?? '-') ?></td>
                                    <td><span class="tag"><?= htmlspecialchars($abt['jurusan'] ?? '-') ?></span></td>
                                    <td><?= htmlspecialchars($abt['angkatan'] ?? '-') ?></td>
                                    <td><span class="badge badge-amber">Belum Terdaftar</span></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="tambah_user.php?id_alumni=<?= $abt['id_alumni'] ?>" class="btn-sm btn-primary">

                                                Buatkan Akun
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="two-col-grid">
            <!-- Jurusan Stats -->
            <div class="section-card">
                <div class="section-head">
                    <h2>

                        Alumni per Jurusan
                    </h2>
                </div>
                <div class="bar-chart">
                    <?php
                    $maxVal = max(array_column($jurusanStat, 'total') ?: [1]);
                    foreach ($jurusanStat as $js):
                        $pct = round(($js['total'] / $maxVal) * 100);
                    ?>
                        <div class="bar-item">
                            <div class="bar-label"><?= htmlspecialchars($js['jurusan']) ?></div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                            <div class="bar-val"><?= $js['total'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Angkatan Stats -->
            <div class="section-card">
                <div class="section-head">
                    <h2>

                        Alumni per Angkatan
                    </h2>
                </div>
                <div class="angkatan-grid">
                    <?php foreach ($angkatanStat as $as): ?>
                        <div class="angkatan-card">
                            <span class="angkatan-year"><?= $as['angkatan'] ?></span>
                            <span class="angkatan-count"><?= $as['total'] ?> alumni</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Alumni -->
        <div class="section-card">
            <div class="section-head">
                <h2>

                    Alumni Terbaru
                </h2>
                <a href="users.php" class="btn-outline-sm">Lihat Semua</a>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Jurusan</th>
                            <th>Angkatan</th>
                            <th>Pekerjaan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAlumni as $a): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($a['nis']) ?></code></td>
                                <td><?= htmlspecialchars($a['nama']) ?></td>
                                <td><span class="tag"><?= htmlspecialchars($a['jurusan']) ?></span></td>
                                <td><?= $a['angkatan'] ?></td>
                                <td><?= htmlspecialchars($a['pekerjaan'] ?: '—') ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit.php?id=<?= $a['id_alumni'] ?>" class="btn-sm btn-edit">

                                            Edit
                                        </a>
                                        <a href="delete.php?id=<?= $a['id_alumni'] ?>" class="btn-sm btn-danger" onclick="return confirm('Hapus data alumni ini?')">

                                            Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="assets/js/bg-slideshow-dashboard.js"></script>
</body>

</html>