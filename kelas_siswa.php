
<?php
date_default_timezone_set('Asia/Jakarta');

session_start();

// Koneksi database berada di folder utama CBT_WEB.
require_once __DIR__ . '/../koneksi.php';

// Pastikan siswa sudah login.
if (
    !isset($_SESSION['id_siswa']) ||
    !isset($_SESSION['nama']) ||
    !isset($_SESSION['id_kelas'])
) {
    header('Location: login_siswa.php');
    exit;
}

// Data siswa.
$nama = htmlspecialchars(
    $_SESSION['nama'],
    ENT_QUOTES,
    'UTF-8'
);

$id_kelas = (int) $_SESSION['id_kelas'];

// Ambil ujian berdasarkan kelas siswa.
$query = "SELECT id_ujian, nama_ujian, durasi_waktu, tanggal_ujian
          FROM ujian
          WHERE id_kelas = ?
          ORDER BY tanggal_ujian ASC";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat mengambil daftar ujian.');
}

mysqli_stmt_bind_param($stmt, 'i', $id_kelas);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// Waktu saat ini.
$sekarang = new DateTime();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Daftar Ujian - CBT</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-5">

    <!-- Sapaan siswa -->
    <div class="alert alert-success">
        <h4 class="alert-heading">
            Selamat Datang, <?= $nama ?>!
        </h4>

        <p class="mb-0">
            Silakan periksa jadwal dan pilih ujian yang tersedia.
        </p>
    </div>

    <!-- Daftar ujian -->
    <div class="card mt-4 shadow-sm">
        <div class="card-header fw-bold">
            Jadwal dan Daftar Ujian
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Nama Ujian</th>
                            <th>Tanggal dan Jam</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($ujian = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $tanggal = new DateTime(
                                $ujian['tanggal_ujian']
                            );

                            $durasi = (int) $ujian['durasi_waktu'];

                            $waktuSelesai = clone $tanggal;
                            $waktuSelesai->modify("+{$durasi} minutes");

                            if ($sekarang < $tanggal) {
                                $status = 'Belum dimulai';
                                $badge = 'bg-warning text-dark';
                                $bisaMulai = false;
                            } elseif ($sekarang < $waktuSelesai) {
                                $status = 'Sedang berlangsung';
                                $badge = 'bg-success';
                                $bisaMulai = true;
                            } else {
                                $status = 'Selesai';
                                $badge = 'bg-secondary';
                                $bisaMulai = false;
                            }
                            ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= htmlspecialchars(
                                        $ujian['nama_ujian'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= $tanggal->format('d-m-Y H:i') ?>
                                </td>

                                <td>
                                    <?= $durasi ?> menit
                                </td>

                                <td>
                                    <span class="badge <?= $badge ?>">
                                        <?= $status ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($bisaMulai): ?>
                                        <a
                                            href="ujian_siswa.php?id=<?= (int) $ujian['id_ujian'] ?>"
                                            class="btn btn-primary btn-sm"
                                        >
                                            Mulai Tes
                                        </a>
                                    <?php else: ?>
                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-sm"
                                            disabled
                                        >
                                            <?= $status === 'Selesai'
                                                ? 'Ujian Berakhir'
                                                : 'Belum Tersedia' ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Belum ada ujian untuk kelas kamu.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    <!-- Tombol keluar -->
    <div class="mt-3 text-end">
        <a href="logout_siswa.php" class="btn btn-outline-danger">
            Keluar
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
