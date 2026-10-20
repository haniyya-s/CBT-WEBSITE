
<?php
session_start();

// Jika siswa sudah login, arahkan ke halaman kelas.
if (isset($_SESSION['id_siswa'])) {
    header("Location: siswa/kelas_siswa.php");
    exit;
}

// Jika guru sudah login, arahkan ke dashboard.
if (isset($_SESSION['id_guru'])) {
    header("Location: guru/dashboard_guru.php");
    exit;
}

$error = "";
$jenisLogin = "siswa";

// Menentukan tab berdasarkan parameter URL.
if (isset($_GET['jenis']) && $_GET['jenis'] === 'guru') {
    $jenisLogin = "guru";
}

// Menampilkan pesan error.
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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login - Ujian Online</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f5f7fb;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 14px;
            overflow: hidden;
        }

        .login-tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
        }

        .login-tab {
            flex: 1;
            padding: 15px 12px;
            text-align: center;
            text-decoration: none;
            color: #6c757d;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            margin-bottom: -1px;
            transition: color 0.2s;
        }

        .login-tab:hover {
            color: #0d6efd;
        }

        .login-tab.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }

        .login-icon {
            font-size: 44px;
            margin-bottom: 8px;
        }

        .form-control {
            padding: 10px 12px;
        }

        .btn-login {
            padding: 11px;
        }
    </style>
</head>

<body>

<div class="container min-vh-100 d-flex justify-content-center align-items-center py-4">

    <div class="card login-card shadow-sm">

        <!-- TAB SISWA DAN GURU -->
        <div class="login-tabs">
            <a
                href="#"
                class="login-tab active"
                id="tabSiswa"
                role="button"
                aria-current="page"
            >
                Siswa
            </a>

            <a
                href="#"
                class="login-tab"
                id="tabGuru"
                role="button"
            >
                Guru
            </a>
        </div>

        <div class="card-body p-4">

            <!-- HEADER -->
            <div class="text-center mb-4">
                <div class="login-icon">🎓</div>

                <h3 class="fw-bold mb-2">Ujian Online</h3>

                <p class="text-muted mb-0">
                    Silakan masuk menggunakan akun Anda
                </p>
            </div>

            <!-- PESAN ERROR -->
            <?php if ($error !== ""): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- FORM LOGIN -->
            <form id="formLogin" action="siswa/proses_login_siswa.php" method="POST">

                <div class="mb-3">
                    <label for="username" class="form-label" id="labelUsername">
                        Username / NIS
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        placeholder="Masukkan username atau NIS"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">
                        Password
                    </label>

                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-login w-100"
                    id="tombolLogin"
                >
                    MASUK RUANG UJIAN
                </button>

            </form>

        </div>
    </div>

</div>

<script>
    const tabSiswa = document.getElementById("tabSiswa");
    const tabGuru = document.getElementById("tabGuru");
    const formLogin = document.getElementById("formLogin");
    const labelUsername = document.getElementById("labelUsername");
    const username = document.getElementById("username");
    const tombolLogin = document.getElementById("tombolLogin");

    function pilihJenisLogin(jenis) {
        const siswaAktif = jenis === "siswa";

        // Pindahkan garis bawah tab aktif.
        tabSiswa.classList.toggle("active", siswaAktif);
        tabGuru.classList.toggle("active", !siswaAktif);

        if (siswaAktif) {
            tabSiswa.setAttribute("aria-current", "page");
            tabGuru.removeAttribute("aria-current");

            formLogin.action = "siswa/proses_login_siswa.php";
            labelUsername.textContent = "Username / NIS";
            username.placeholder = "Masukkan username atau NIS";
            tombolLogin.textContent = "MASUK RUANG UJIAN";
        } else {
            tabGuru.setAttribute("aria-current", "page");
            tabSiswa.removeAttribute("aria-current");

            formLogin.action = "guru/proses_login_guru.php";
            labelUsername.textContent = "Username";
            username.placeholder = "Masukkan username";
            tombolLogin.textContent = "MASUK SEBAGAI GURU";
        }
    }

    tabSiswa.addEventListener("click", function (event) {
        event.preventDefault();
        pilihJenisLogin("siswa");
    });

    tabGuru.addEventListener("click", function (event) {
        event.preventDefault();
        pilihJenisLogin("guru");
    });

    // Membuka tab yang sesuai jika kembali dari pesan error.
    pilihJenisLogin(<?= json_encode($jenisLogin) ?>);
</script>

</body>
</html>
