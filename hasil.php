
<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once "koneksi.php";

if (
    !isset($_SESSION['id_siswa']) ||
    !isset($_SESSION['id_kelas'])
) {
    header("Location: login.php");
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];
$id_kelas = (int) $_SESSION['id_kelas'];
$id_ujian = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_ujian || $id_ujian < 1) {
    exit("ID ujian tidak valid.");
}

// Pastikan ujian milik kelas siswa
$stmt = mysqli_prepare(
    $conn,
    "SELECT nama_ujian
     FROM ujian
     WHERE id_ujian = ? AND id_kelas = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $id_ujian, $id_kelas);
mysqli_stmt_execute($stmt);
$ujian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$ujian) {
    exit("Ujian tidak ditemukan.");
}

// Jumlah seluruh soal
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM soal
     WHERE id_ujian = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id_ujian);
mysqli_stmt_execute($stmt);
$total = (int) mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
)['total'];

// Jumlah jawaban benar
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS benar
     FROM jawaban_siswa j
     JOIN soal s ON j.id_soal = s.id_soal
     WHERE j.id_siswa = ?
       AND j.id_ujian = ?
       AND s.id_ujian = ?
       AND j.jawaban_terpilih = s.jawaban_benar"
);
mysqli_stmt_bind_param(
    $stmt,
    "iii",
    $id_siswa,
    $id_ujian,
    $id_ujian
);
mysqli_stmt_execute($stmt);
$benar = (int) mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
)['benar'];

$nilai = $total > 0 ? round(($benar / $total) * 100, 2) : 0;

$namaUjian = htmlspecialchars(
    $ujian['nama_ujian'],
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Hasil Ujian</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 500px;">
        <div class="card-body text-center p-5">
            <h3>Ujian Selesai!</h3>
            <p class="text-muted"><?= $namaUjian ?></p>

            <hr>

            <p>Jawaban benar</p>
            <h4><?= $benar ?> dari <?= $total ?> soal</h4>

            <p class="mt-4 mb-1">Nilai kamu</p>
            <h1 class="display-3 fw-bold">
                <?= number_format($nilai, 2, ',', '.') ?>
            </h1>

            <a href="kelas.php" class="btn btn-primary mt-4">
                Kembali ke Halaman Kelas
            </a>
        </div>
    </div>
</div>

</body>
</html>
