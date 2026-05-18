<?php
session_start();
require 'auth.php';
require 'koneksi.php';
requireAdmin();

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

switch ($action) {
    case 'approve':
        $stmt = mysqli_prepare($conn, "UPDATE users SET status='approved' WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'dashboard_admin.php'));
        break;

    case 'reject':
        $stmt = mysqli_prepare($conn, "UPDATE users SET status='rejected' WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'users.php'));
        break;

    case 'delete':
        if (!isSuperAdmin()) {
            header('Location: users.php');
            exit;
        }
        // Cari id_alumni terhubung
        $stmt = mysqli_prepare($conn, "SELECT id_alumni FROM users WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $u = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // Jangan hapus alumni datanya, hanya putus relasi
        header('Location: users.php');
        break;

    default:
        header('Location: dashboard_admin.php');
}
exit;
