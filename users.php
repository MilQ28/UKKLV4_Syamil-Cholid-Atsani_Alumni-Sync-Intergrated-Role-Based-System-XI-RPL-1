<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna — Alumni SMK</title>
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

    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');

    $where  = ["u.role = 'user'"];
    $params = [];
    $types  = '';

    if ($search) {
        $where[] = "(u.username LIKE ? OR a.nama LIKE ? OR a.nis LIKE ?)";
        $s = "%$search%";
        $params = array_merge($params, [$s, $s, $s]);
        $types .= 'sss';
    }
    if ($status) {
        $where[] = "u.status = ?";
        $params[] = $status;
        $types .= 's';
    }

    $sql = "SELECT u.*, a.nama, a.nis, a.jurusan, a.angkatan, a.email as email_alumni
        FROM users u
        LEFT JOIN alumni a ON u.id_alumni = a.id_alumni
        WHERE " . implode(" AND ", $where) . "
        ORDER BY u.created_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $users = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">Manajemen Pengguna</h1>
                <p class="page-sub">Kelola akun alumni yang terdaftar</p>
            </div>
            <div class="header-actions">
                <a href="dashboard_admin.php" class="btn-outline" style="margin:5px; text-decoration: none;">

                    Kembali
                </a>
                <a href="tambah_user.php" class="btn-primary" style="padding: 5px; text-decoration: none;">

                    Tambah Pengguna
                </a>
            </div>
        </div>

        <div class="section-card">
            <form method="GET" class="search-form" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="input-wrapper">

                    <input type="text" name="search" placeholder="Cari username, nama, NIS..." value="<?= htmlspecialchars($search) ?>" style="width: 1500px;">
                </div>
                <div class="input-wrapper">

                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status === 'pending'  ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="padding: 10px; width: 150px;">Filter</button>
                <?php if ($search || $status): ?><a href="users.php" class="btn-outline">Reset</a><?php endif; ?>
            </form>
        </div>

        <div class="section-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Nama Alumni</th>
                            <th>NIS</th>
                            <th>Jurusan</th>
                            <th>Angkatan</th>
                            <th>Status</th>
                            <th>Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px">Tidak ada data pengguna.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $i => $u): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['nama'] ?? '—') ?></td>
                                    <td><code><?= htmlspecialchars($u['nis'] ?? '—') ?></code></td>
                                    <td><?= htmlspecialchars($u['jurusan'] ?? '—') ?></td>
                                    <td><?= $u['angkatan'] ?? '—' ?></td>
                                    <td>
                                        <?php
                                        $badges = ['pending' => 'badge-amber', 'approved' => 'badge-green', 'rejected' => 'badge-red'];
                                        $labels = ['pending' => 'Menunggu', 'approved' => 'Aktif', 'rejected' => 'Ditolak'];
                                        $st = $u['status'];
                                        ?>
                                        <span class="badge <?= $badges[$st] ?? '' ?>"><?= $labels[$st] ?? $st ?></span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <?php if ($u['status'] === 'pending'): ?>
                                                <a href="delete_user.php?action=approve&id=<?= $u['user_id'] ?>" class="btn-sm btn-success" onclick="return confirm('Setujui akun ini?')">

                                                    Setujui
                                                </a>
                                                <a href="delete_user.php?action=reject&id=<?= $u['user_id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Tolak akun ini?')">

                                                    Tolak
                                                </a>
                                            <?php elseif ($u['id_alumni']): ?>
                                                <a href="profile.php?id=<?= $u['id_alumni'] ?>" class="btn-sm btn-edit">

                                                    Lihat
                                                </a>
                                            <?php endif; ?>
                                            <?php if (isSuperAdmin()): ?>
                                                <a href="delete_user.php?action=delete&id=<?= $u['user_id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Hapus pengguna ini secara permanen?')">

                                                    Hapus
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>