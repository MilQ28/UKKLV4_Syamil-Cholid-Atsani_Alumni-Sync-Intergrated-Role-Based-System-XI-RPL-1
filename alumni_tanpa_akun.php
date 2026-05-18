<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alumni Belum Terdaftar — Alumni SMK</title>
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

$sql = "SELECT a.id_alumni, a.nis, a.nama, a.jurusan, a.angkatan, a.email 
        FROM alumni a 
        LEFT JOIN users u ON a.id_alumni = u.id_alumni 
        WHERE u.id_alumni IS NULL";

$params = [];
$types = '';

if ($search) {
    $sql .= " AND (a.nama LIKE ? OR a.nis LIKE ? OR a.jurusan LIKE ?)";
    $s = "%$search%";
    $params = [$s, $s, $s];
    $types = 'sss';
}

$sql .= " ORDER BY a.nama ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$alumni = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>

<div class="page-wrapper">
  <div class="page-header">
    <div>
      <h1 class="page-title">Alumni Belum Terdaftar</h1>
      <p class="page-sub">Daftar keseluruhan alumni yang belum memiliki akun pengguna</p>
    </div>
    <a href="dashboard_admin.php" class="btn-outline">
      
      Kembali
    </a>
  </div>

  <div class="section-card">
    <form method="GET" class="search-form">
      <div class="input-wrapper">
        
        <input type="text" name="search" placeholder="Cari nama, NIS, jurusan..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <button type="submit" class="btn-primary">Cari</button>
      <?php if ($search): ?><a href="alumni_tanpa_akun.php" class="btn-outline">Reset</a><?php endif; ?>
    </form>
  </div>

  <div class="section-card">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th><th>NIS</th><th>Nama Alumni</th><th>Jurusan</th><th>Angkatan</th><th>Status</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($alumni)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px">Tidak ada data alumni belum terdaftar.</td></tr>
          <?php else: ?>
          <?php foreach ($alumni as $i => $a): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><code><?= htmlspecialchars($a['nis'] ?? '—') ?></code></td>
            <td><strong><?= htmlspecialchars($a['nama'] ?? '—') ?></strong></td>
            <td><span class="tag"><?= htmlspecialchars($a['jurusan'] ?? '—') ?></span></td>
            <td><?= $a['angkatan'] ?? '—' ?></td>
            <td><span class="badge badge-amber">Belum Terdaftar</span></td>
            <td>
              <div class="action-btns">
                <a href="tambah_user.php?id_alumni=<?= $a['id_alumni'] ?>" class="btn-sm btn-primary">
                  
                  Buatkan Akun
                </a>
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