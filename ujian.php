
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

// Periksa ujian sesuai kelas siswa
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_ujian, nama_ujian, durasi_waktu, tanggal_ujian
     FROM ujian
     WHERE id_ujian = ? AND id_kelas = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $id_ujian, $id_kelas);
mysqli_stmt_execute($stmt);
$ujian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$ujian) {
    exit("Ujian tidak ditemukan.");
}

// Periksa jadwal ujian
$mulai = new DateTime($ujian['tanggal_ujian']);
$selesai = clone $mulai;
$selesai->modify("+" . (int) $ujian['durasi_waktu'] . " minutes");
$sekarang = new DateTime();

if ($sekarang < $mulai) {
    exit("Ujian belum dimulai.");
}

if ($sekarang >= $selesai) {
    exit("Waktu ujian sudah berakhir.");
}

// Ambil soal ujian
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_soal, teks_soal, opsi_a, opsi_b, opsi_c, opsi_d
     FROM soal
     WHERE id_ujian = ?
     ORDER BY id_soal ASC"
);
mysqli_stmt_bind_param($stmt, "i", $id_ujian);
mysqli_stmt_execute($stmt);

$daftarSoal = mysqli_fetch_all(
    mysqli_stmt_get_result($stmt),
    MYSQLI_ASSOC
);

if (empty($daftarSoal)) {
    exit("Belum ada soal untuk ujian ini.");
}

$jumlahSoal = count($daftarSoal);

// Siapkan jawaban sementara di session
if (!isset($_SESSION['jawaban_ujian'][$id_ujian])) {
    $_SESSION['jawaban_ujian'][$id_ujian] = [];
}

// Proses navigasi dan penyelesaian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_soal_post = filter_input(
        INPUT_POST,
        'id_soal',
        FILTER_VALIDATE_INT
    );

    // Pastikan soal termasuk ujian ini
    $soalValid = false;

    foreach ($daftarSoal as $item) {
        if ((int) $item['id_soal'] === (int) $id_soal_post) {
            $soalValid = true;
            break;
        }
    }

    if ($soalValid && isset($_POST['jawaban'])) {
        $pilihan = strtoupper($_POST['jawaban']);

        if (in_array($pilihan, ['A', 'B', 'C', 'D'], true)) {
            $_SESSION['jawaban_ujian'][$id_ujian][$id_soal_post]
                = $pilihan;
        }
    }

    $aksi = $_POST['aksi'] ?? 'berikutnya';
    $nomorPost = (int) ($_POST['nomor'] ?? 1);

    if ($aksi === 'selesai') {
        // Pastikan semua soal sudah dijawab
        foreach ($daftarSoal as $item) {
            $idCek = (int) $item['id_soal'];

            if (!isset(
                $_SESSION['jawaban_ujian'][$id_ujian][$idCek]
            )) {
                exit(
                    "Masih ada soal yang belum dijawab. " .
                    "<a href='ujian.php?id=" . $id_ujian .
                    "&nomor=" . $nomorPost . "'>Kembali ke ujian</a>"
                );
            }
        }

        $_SESSION['siap_diproses'][$id_ujian] = true;

        header("Location: proses_selesai.php?id=" . $id_ujian);
        exit;
    }

    if ($aksi === 'sebelumnya') {
        $nomorPost--;
    } else {
        $nomorPost++;
    }

    $nomorPost = max(1, min($jumlahSoal, $nomorPost));

    header(
        "Location: ujian.php?id=" . $id_ujian .
        "&nomor=" . $nomorPost
    );
    exit;
}

// Tentukan soal aktif
$nomor = filter_input(INPUT_GET, 'nomor', FILTER_VALIDATE_INT);
$nomor = max(1, min($jumlahSoal, $nomor ?: 1));

$soal = $daftarSoal[$nomor - 1];
$id_soal = (int) $soal['id_soal'];

$jawabanSaatIni =
    $_SESSION['jawaban_ujian'][$id_ujian][$id_soal] ?? '';

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

    <title><?= $namaUjian ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-1"><?= $namaUjian ?></h4>

            <span class="text-muted">
                Soal <?= $nomor ?> dari <?= $jumlahSoal ?>
            </span>
        </div>

        <div class="card-body">
            <form method="POST" id="formUjian">
                <input
                    type="hidden"
                    name="id_soal"
                    value="<?= $id_soal ?>"
                >

                <input
                    type="hidden"
                    name="nomor"
                    value="<?= $nomor ?>"
                >

                <h5 class="mb-4">
                    <?= nl2br(htmlspecialchars(
                        $soal['teks_soal'],
                        ENT_QUOTES,
                        'UTF-8'
                    )) ?>
                </h5>

                <?php foreach (['A', 'B', 'C', 'D'] as $huruf): ?>
                    <?php $kolom = 'opsi_' . strtolower($huruf); ?>

                    <label class="border rounded p-3 mb-2 d-block">
                        <input
                            type="radio"
                            name="jawaban"
                            value="<?= $huruf ?>"
                            <?= $jawabanSaatIni === $huruf
                                ? 'checked'
                                : '' ?>
                        >

                        <strong><?= $huruf ?>.</strong>
                        <?= htmlspecialchars(
                            $soal[$kolom],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </label>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between mt-4">
                    <?php if ($nomor > 1): ?>
                        <button
                            type="submit"
                            name="aksi"
                            value="sebelumnya"
                            class="btn btn-outline-secondary"
                            formnovalidate
                        >
                            Sebelumnya
                        </button>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>

                    <?php if ($nomor < $jumlahSoal): ?>
                        <button
                            type="submit"
                            name="aksi"
                            value="berikutnya"
                            id="tombolBerikutnya"
                            class="btn btn-primary"
                            formnovalidate
                            disabled
                        >
                            Berikutnya
                        </button>
                    <?php else: ?>
                        <button
                            type="submit"
                            name="aksi"
                            value="selesai"
                            id="tombolSelesai"
                            class="btn btn-success"
                            formnovalidate
                            disabled
                            onclick="return confirm(
                                'Yakin ingin menyelesaikan ujian?'
                            );"
                        >
                            Selesaikan Ujian
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const pilihanJawaban = document.querySelectorAll(
    'input[name="jawaban"]'
);

const tombolBerikutnya = document.getElementById(
    'tombolBerikutnya'
);

const tombolSelesai = document.getElementById(
    'tombolSelesai'
);

function periksaJawaban() {
    const sudahMenjawab = Array.from(pilihanJawaban).some(
        pilihan => pilihan.checked
    );

    if (tombolBerikutnya) {
        tombolBerikutnya.disabled = !sudahMenjawab;
    }

    if (tombolSelesai) {
        tombolSelesai.disabled = !sudahMenjawab;
    }
}

pilihanJawaban.forEach(pilihan => {
    pilihan.addEventListener('change', periksaJawaban);
});

periksaJawaban();
</script>

</body>
</html>
