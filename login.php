<?php
session_start();

$error = "";

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'kosong') {
        $error = "Username dan password wajib diisi.";
    } elseif ($_GET['error'] === 'gagal') {
        $error = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ujian Online</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container min-vh-100 d-flex justify-content-center align-items-center">
    <div class="card shadow-sm border-0" style="width: 100%; max-width: 420px;">
        <div class="card-body p-4">

            <h3 class="text-center mb-2">Ujian Online</h3>
            <p class="text-center text-muted mb-4">
                Silakan masuk menggunakan akun Anda
            </p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="proses_login.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username / NIS</label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        placeholder="Masukkan username"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    MASUK RUANG UJIAN
                </button>
            </form>

        </div>
    </div>
</div>

</body>
</html>
```
