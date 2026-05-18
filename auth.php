<?php
// ==============================================================================
// AUTENTIKASI & HAK AKSES (auth.php)
// File ini berisi fungsi-fungsi untuk membatasi halaman mana saja yang boleh
// diakses oleh tamu, pengguna biasa, atau admin.
// ==============================================================================

/**
 * Fungsi untuk memaksa pengunjung login terlebih dahulu.
 * Jika belum login (tidak ada session 'user_id'), maka akan dilempar ke halaman login.php.
 */
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php'); // Redirect ke login
        exit; // Hentikan eksekusi script lebih lanjut
    }
}

/**
 * Fungsi untuk membatasi akses khusus Admin (atau Superadmin).
 * Jika yang akses adalah user biasa, maka dilempar ke dashboard_user.php.
 */
function requireAdmin()
{
    requireLogin(); // Cek dulu apakah dia sudah login

    // in_array mengecek apakah 'role' ada di dalam daftar ['admin', 'superadmin']
    if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
        header('Location: dashboard_user.php');
        exit;
    }
}

/**
 * Fungsi untuk membatasi akses sangat rahasia khusus Superadmin saja.
 */
function requireSuperAdmin()
{
    requireLogin(); // Cek dulu apakah dia sudah login
    if ($_SESSION['role'] !== 'superadmin') {
        header('Location: dashboard_admin.php');
        exit;
    }
}

/**
 * Fungsi bantuan untuk mengecek apakah user saat ini adalah admin/superadmin.
 * Hanya mengembalikan nilai true (benar) atau false (salah), tidak melakukan redirect.
 */
function isAdmin()
{
    // Tanda ?? '' artinya "jika tidak ada nilainya, anggap kosong" agar tidak terjadi error
    return in_array($_SESSION['role'] ?? '', ['admin', 'superadmin']);
}

/**
 * Fungsi bantuan mengecek apakah user saat ini superadmin.
 * Mengembalikan true/false.
 */
function isSuperAdmin()
{
    return ($_SESSION['role'] ?? '') === 'superadmin';
}
