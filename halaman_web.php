<?php
session_start();

if (!isset($_SESSION['nama'])) {
    header("Location: login.php");
    exit;
}

$nama = htmlspecialchars(
    $_SESSION['nama'],
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Halaman Kelas | CBT</title>

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
                Silakan pilih ujian yang tersedia untuk memulai.
            </p>
        </div>

        <!-- Daftar ujian -->
        <div class="card mt-4">

            <div class="card-header fw-bold">
                Daftar Ujian Aktif
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Mata Pelajaran</th>
                                <th>Durasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <!-- Data contoh sementara -->
                            <tr>
                                <td>1</td>
                                <td>Ujian Matematika Dasar</td>
                                <td>90 Menit</td>
                                <td>
                                    <a
                                        href="ujian.php"
                                        class="btn btn-primary btn-sm"
                                    >
                                        Mulai Tes
                                    </a>
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>

            </div>
        </div>

        <!-- Tombol keluar -->
        <div class="mt-3 text-end">
            <a href="logout.php" class="btn btn-outline-danger">
                Keluar
            </a>
        </div>

    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
```
