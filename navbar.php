<?php
// navbar.php - included on every page
$current = basename($_SERVER['PHP_SELF']);
include 'koneksi.php';
?>
<nav class="navbar">
    <div class="nav-brand">
        <!-- Logo SMK Telkom Lampung -->
        <img src="assets/img/telkom-logo.jpg" alt="Logo SMK Telkom Lampung">
        <span>SMK Telkom Lampung</span>
        <span style="font-size: small; margin-left: 100px; font-weight:700; color: #cecece;">Dashboard <?= ucfirst($_SESSION['role']) ?></span>
        <span style="font-size: small; margin-left: 100px; font-weight:700; color: #cecece;">Manajemen Data Alumni</span>
    </div>

    <div class="nav-links">
        <?php if (isAdmin()): ?>
            <a href="dashboard_admin.php" class="nav-link <?= $current === 'dashboard_admin.php' ? 'active' : '' ?>">

                Dashboard
            </a>
            <a href="users.php" class="nav-link <?= $current === 'users.php' ? 'active' : '' ?>">

                Pengguna
            </a>
            <a href="tambah_user.php" class="nav-link <?= $current === 'tambah_user.php' ? 'active' : '' ?>">

                Tambah Alumni
            </a>
            <a href="tambah.php" class="nav-link <?= $current === 'tambah.php' ? 'active' : '' ?>">

                Tambah Data
            </a>
        <?php else: ?>
            <a href="dashboard_user.php" class="nav-link <?= $current === 'dashboard_user.php' ? 'active' : '' ?>">

                Beranda
            </a>
        <?php endif; ?>
    </div>

    <div class="nav-user">
        <a href="profile.php" class="nav-profile <?= $current === 'profile.php' ? 'active' : '' ?>">
            <p class="page-sub">Halo, <span style="font-weight:bold"><?= htmlspecialchars($_SESSION['username']) ?></span></p>
            <?php
            $foto = '';
            if (isset($_SESSION['id_alumni'])) {
                $s = mysqli_prepare($conn, "SELECT foto_profil, nama FROM alumni WHERE id_alumni = ?");
                mysqli_stmt_bind_param($s, 'i', $_SESSION['id_alumni']);
                mysqli_stmt_execute($s);
                $res = mysqli_stmt_get_result($s);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($s);
                $foto = $row['foto_profil'] ?? '';
                $namaAlumni = $row['nama'] ?? $_SESSION['username'];
            } else {
                $namaAlumni = $_SESSION['username'];
            }
            if ($foto && file_exists("uploads/foto_profil/$foto")):
            ?>
                <img src="uploads/foto_profil/<?= htmlspecialchars($foto) ?>" alt="Foto Profil" class="avatar-img">
            <?php else: ?>
                <img src="assets/img/default-avatar.png" alt="Foto Profil" class="avatar-img">
            <?php endif; ?>
            <div class="nav-user-info">
                <span class="nav-username"><?= htmlspecialchars($namaAlumni) ?></span>
                <span class="nav-role"><?= ucfirst($_SESSION['role']) ?></span>
            </div>
        </a>
        <a href="logout.php" class="nav-logout" title="Logout">Logout</a>
    </div>
</nav>