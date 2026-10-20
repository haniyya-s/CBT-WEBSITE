
<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

// Koneksi database berada di folder utama CBT_WEB.
require_once __DIR__ . '/../koneksi.php';

// Pastikan siswa sudah login.
if (
    !isset($_SESSION['id_siswa']) ||
    !isset($_SESSION['id_kelas'])
) {
    header('Location: login_siswa.php');
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];
$id_kelas = (int) $_SESSION['id_kelas'];

$id_ujian = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

// Pastikan permintaan berasal dari alur penyelesaian ujian.
if (
    !$id_ujian ||
    empty($_SESSION['siap_diproses'][$id_ujian])
) {
    header('Location: kelas_siswa.php');
    exit;
}

// Periksa apakah ujian sesuai dengan kelas siswa.
$query = "SELECT id_ujian, nama_ujian, durasi_waktu, tanggal_ujian
          FROM ujian
          WHERE id_ujian = ? AND id_kelas = ?
          LIMIT 1";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memeriksa ujian.');
}

mysqli_stmt_bind_param($stmt, 'ii', $id_ujian, $id_kelas);
mysqli_stmt_execute($stmt);

$ujian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$ujian) {
    http_response_code(404);
    exit('Ujian tidak ditemukan.');
}

// Ambil jawaban sementara dari session.
$jawaban = $_SESSION['jawaban_ujian'][$id_ujian] ?? [];

if (!is_array($jawaban)) {
    exit('Data jawaban tidak valid.');
}

// Ambil seluruh soal dan kunci jawaban untuk ujian ini.
$query = "SELECT id_soal, jawaban_benar
          FROM soal
          WHERE id_ujian = ?
          ORDER BY id_soal ASC";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat mengambil data soal.');
}

mysqli_stmt_bind_param($stmt, 'i', $id_ujian);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$kunci = [];

while ($baris = mysqli_fetch_assoc($result)) {
    $kunci[(int) $baris['id_soal']] =
        strtoupper(trim($baris['jawaban_benar']));
}

mysqli_stmt_close($stmt);

if (empty($kunci)) {
    exit('Soal untuk ujian ini tidak ditemukan.');
}

// Pastikan seluruh soal telah dijawab dengan pilihan yang valid.
foreach ($kunci as $id_soal => $jawabanBenar) {
    if (
        !isset($jawaban[$id_soal]) ||
        !is_string($jawaban[$id_soal]) ||
        !in_array(
            strtoupper($jawaban[$id_soal]),
            ['A', 'B', 'C', 'D'],
            true
        )
    ) {
        exit(
            'Masih ada soal yang belum dijawab. '
            . '<a href="ujian_siswa.php?id='
            . (int) $id_ujian
            . '">Kembali ke ujian</a>'
        );
    }
}

// Pastikan tidak ada jawaban untuk soal di luar ujian ini.
foreach ($jawaban as $id_soal => $pilihan) {
    if (!isset($kunci[(int) $id_soal])) {
        exit('Terdapat jawaban yang tidak sesuai dengan ujian.');
    }
}

try {
    // Mulai transaksi penyimpanan.
    mysqli_begin_transaction($conn);

    // Hapus jawaban sebelumnya untuk percobaan ulang.
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM jawaban_siswa
         WHERE id_siswa = ? AND id_ujian = ?"
    );

    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan penghapusan jawaban.');
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ii',
        $id_siswa,
        $id_ujian
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException('Gagal menghapus jawaban sebelumnya.');
    }

    mysqli_stmt_close($stmt);

    // Siapkan query untuk menyimpan jawaban baru.
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO jawaban_siswa
            (id_siswa, id_ujian, id_soal, jawaban_terpilih)
         VALUES (?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan penyimpanan jawaban.');
    }

    foreach ($jawaban as $id_soal => $pilihan) {
        $id_soal = (int) $id_soal;
        $pilihan = strtoupper($pilihan);

        mysqli_stmt_bind_param(
            $stmt,
            'iiis',
            $id_siswa,
            $id_ujian,
            $id_soal,
            $pilihan
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException('Gagal menyimpan salah satu jawaban.');
        }
    }

    mysqli_stmt_close($stmt);

    // Simpan seluruh perubahan jika semua query berhasil.
    mysqli_commit($conn);

    // Hapus data sementara setelah penyimpanan berhasil.
    unset($_SESSION['jawaban_ujian'][$id_ujian]);
    unset($_SESSION['siap_diproses'][$id_ujian]);

    // Arahkan siswa ke halaman hasil.
    header('Location: hasil_siswa.php?id=' . $id_ujian);
    exit;

} catch (Throwable $e) {
    // Batalkan perubahan jika proses gagal.
    mysqli_rollback($conn);

    http_response_code(500);

    exit(
        'Gagal menyimpan jawaban. '
        . 'Silakan hubungi pengelola ujian.'
    );
}
?>
