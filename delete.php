<?php
session_start();
require 'auth.php';
require 'koneksi.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // Hapus foto jika ada
    $stmt = mysqli_prepare($conn, "SELECT foto_profil FROM alumni WHERE id_alumni=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $a = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($a && $a['foto_profil'] && file_exists("uploads/foto_profil/" . $a['foto_profil'])) {
        unlink("uploads/foto_profil/" . $a['foto_profil']);
    }

    // Set id_alumni di users menjadi null
    $stmt = mysqli_prepare($conn, "UPDATE users SET id_alumni=NULL WHERE id_alumni=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "DELETE FROM alumni WHERE id_alumni=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
header('Location: dashboard_admin.php');
exit;
