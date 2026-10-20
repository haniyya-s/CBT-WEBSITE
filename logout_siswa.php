
<?php
session_start();

// Hapus data session siswa saja.
unset(
    $_SESSION['id_siswa'],
    $_SESSION['nama'],
    $_SESSION['username'],
    $_SESSION['id_kelas'],
    $_SESSION['siap_diproses']
);

// Kembali ke halaman login gabungan.
header("Location: ../login.php");
exit;
?>
