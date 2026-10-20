
<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

require_once __DIR__ . '/../koneksi.php';

// 1. Periksa login siswa
if (
    !isset($_SESSION['id_siswa']) ||
    !isset($_SESSION['id_kelas'])
) {
    header("Location: login_siswa.php");
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];
$id_kelas = (int) $_SESSION['id_kelas'];

$id_ujian = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id_ujian || $id_ujian < 1) {
    header("Location: kelas_siswa.php");
    exit;
}

// 2. Ambil informasi ujian sesuai kelas siswa
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_ujian, nama_ujian
     FROM ujian
     WHERE id_ujian = ? AND id_kelas = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id_ujian,
    $id_kelas
);

mysqli_stmt_execute($stmt);

$ujian = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

mysqli_stmt_close($stmt);

if (!$ujian) {
    header("Location: kelas_siswa.php");
    exit;
}

// 3. Hitung jumlah seluruh soal
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

mysqli_stmt_close($stmt);

// 4. Hitung jumlah jawaban benar
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS benar
     FROM jawaban_siswa j
     INNER JOIN soal s
        ON j.id_soal = s.id_soal
     WHERE j.id_siswa = ?
       AND j.id_ujian = ?
       AND s.id_ujian = ?
       AND UPPER(TRIM(j.jawaban_terpilih))
           = UPPER(TRIM(s.jawaban_benar))"
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

mysqli_stmt_close($stmt);

// 5. Hitung nilai akhir
$nilai = $total > 0
    ? round(($benar / $total) * 100, 2)
    : 0;

// 6. Amankan teks nama ujian
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

    <title>Hasil Ujian - Ujian Online</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container min-vh-100 d-flex
            justify-content-center align-items-center py-5">

    <div class="card shadow-sm border-0 w-100"
         style="max-width: 500px;">

        <div class="card-body text-center p-4 p-md-5">

            <h3 class="mb-2">Ujian Selesai!</h3>

            <p class="text-muted mb-4">
                <?= $namaUjian ?>
            </p>

            <hr>

            <p class="mb-1">Jawaban benar</p>

            <h4>
                <?= $benar ?> dari <?= $total ?> soal
            </h4>

            <p class="mt-4 mb-1">Nilai kamu</p>

            <h1 class="display-3 fw-bold">
                <?= number_format($nilai, 2, ',', '.') ?>
            </h1>

            <?php if ($total === 0): ?>
                <p class="text-danger">
                    Ujian ini belum memiliki soal.
                </p>
            <?php else: ?>
                <p class="text-muted">
                    Nilai dihitung berdasarkan jumlah jawaban benar.
                </p>
            <?php endif; ?>

            <a href="kelas_siswa.php"
               class="btn btn-primary w-100 mt-3">
                Kembali ke Halaman Kelas
            </a>

        </div>
    </div>
</div>

</body>
</html>
