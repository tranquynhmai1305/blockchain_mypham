<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoten = trim($_POST['hoten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $diachi = trim($_POST['diachi'] ?? '');
    $ngaysinh = trim($_POST['ngaysinh'] ?? ''); 
    $password = trim($_POST['matkhau'] ?? '');
    $repassword = trim($_POST['rematkhau'] ?? '');

    // Dùng email làm username
    $username = $email;

    if ($hoten === '' || $email === '' || $password === '') {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } elseif ($password !== $repassword) {
        $error = "Mật khẩu nhập lại không khớp!";
    } else {
        // Kiểm tra email đã tồn tại
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email đã được sử dụng!";
        } else {
            // 1️⃣ Insert vào users
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, role)
                VALUES (?, ?, ?, 'user')
            ");
            $stmt->bind_param("sss", $username, $email, $password);
            $stmt->execute();

            $id_user = $conn->insert_id;

            // 2️⃣ Insert vào khachhang
            $ngaysinh_val = ($ngaysinh === '') ? null : $ngaysinh;

            $stmt2 = $conn->prepare("
                INSERT INTO khachhang (hoten, email, sdt, diachi, ngaysinh, id_users)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt2->bind_param("sssssi", $hoten, $email, $sdt, $diachi, $ngaysinh_val, $id_user);
            $stmt2->execute();

            $success = "Đăng ký thành công!";

            header("Location: /Shopbanhang/user/login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản | Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Nền nghệ thuật chuyển màu kết hợp các đóm sáng lung linh */
        body {
            background-color: #fff1f2;
            background-image: 
                radial-gradient(at 10% 20%, rgba(244, 63, 94, 0.25) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(236, 72, 153, 0.2) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(255, 228, 230, 0.5) 0px, transparent 60%);
            position: relative;
            overflow-x: hidden;
        }

        /* Hiệu ứng lưới mờ trang trí nền */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: linear-gradient(to right, rgba(225, 29, 72, 0.03) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(225, 29, 72, 0.03) 1px, transparent 1px);
            background-size: 24px 24px;
            z-index: 0;
        }

        .main-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            box-shadow: 0 25px 50px -12px rgba(225, 29, 72, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .input-box {
            transition: all 0.25s ease;
        }
        .input-box:focus-within {
            border-color: #e11d48;
            box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.12);
            background-color: #ffffff !important;
            transform: translateY(-1px);
        }
        .btn-effect {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-effect:hover {
            box-shadow: 0 10px 20px -5px rgba(225, 29, 72, 0.4);
            transform: translateY(-1px);
        }
        .btn-effect:active {
            transform: translateY(1px);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 md:p-6">

    <!-- Main Container (Bo góc gọn gàng: rounded-2xl) -->
    <div class="main-container w-full max-w-4xl rounded-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">
        
        <!-- Left Side: Banner / Image -->
        <div class="relative hidden md:flex flex-col justify-between p-10 bg-gradient-to-br from-rose-600 via-rose-500 to-pink-500 text-white overflow-hidden">
            <!-- Hiệu ứng ánh sáng trang trí bên trong card -->
            <div class="absolute -top-12 -left-12 w-64 h-64 rounded-full bg-white/15 blur-xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -right-12 w-64 h-64 rounded-full bg-black/10 blur-xl pointer-events-none"></div>

            <!-- Top Logo/Brand -->
            <div class="relative z-10 flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-lg bg-white/20 backdrop-blur-md flex items-center justify-center shadow-inner">
                    <i class="fa-solid fa-spa text-white text-base"></i>
                </div>
                <span class="font-bold tracking-wider text-sm">TRIPLE BEAUTY</span>
            </div>

            <!-- Middle Text / Catchphrase -->
            <div class="relative z-10 my-auto py-8">
                <span class="inline-block px-3 py-1 rounded-md bg-white/20 backdrop-blur-md text-[11px] font-semibold tracking-wider uppercase mb-4">Trở thành thành viên</span>
                <h1 class="text-3xl font-extrabold tracking-tight leading-snug mb-3">Bắt đầu hành trình làm đẹp cùng chúng tôi</h1>
                <p class="text-rose-100 text-xs leading-relaxed font-light">Đăng ký tài khoản ngay để tích điểm đổi quà, nhận thông tin ưu đãi độc quyền và mua sắm dễ dàng hơn.</p>
            </div>

            <!-- Bottom Copyright / Note -->
            <div class="relative z-10 text-[11px] text-rose-100/70 font-medium">
                &copy; 2026 Triple Beauty. All rights reserved.
            </div>
        </div>

        <!-- Right Side: Register Form -->
        <div class="p-8 md:p-10 flex flex-col justify-center bg-white/80 max-h-[90vh] overflow-y-auto">
            
            <div class="mb-5">
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Tạo tài khoản mới</h2>
                <p class="text-slate-400 text-xs mt-1">Vui lòng điền thông tin bên dưới để đăng ký</p>
            </div>

            <!-- Error Message -->
            <?php if($error): ?>
                <div class="bg-rose-50 text-rose-600 text-xs font-semibold p-3 rounded-xl mb-4 text-center border border-rose-100 flex items-center justify-center gap-2 animate-pulse">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if($success): ?>
                <div class="bg-emerald-50 text-emerald-600 text-xs font-semibold p-3 rounded-xl mb-4 text-center border border-emerald-100 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="" class="space-y-3.5">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Họ và tên</label>
                    <div class="input-box relative flex items-center bg-slate-50/70 border border-slate-200 rounded-xl overflow-hidden">
                        <span class="absolute left-3.5 text-slate-400 text-xs"><i class="fa fa-user"></i></span>
                        <input name="hoten" required placeholder="Nguyễn Văn A"
                            class="w-full bg-transparent py-2.5 pl-10 pr-3 text-xs md:text-sm text-slate-700 outline-none placeholder:text-slate-300">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Email</label>
                    <div class="input-box relative flex items-center bg-slate-50/70 border border-slate-200 rounded-xl overflow-hidden">
                        <span class="absolute left-3.5 text-slate-400 text-xs"><i class="fa fa-envelope"></i></span>
                        <input name="email" type="email" required placeholder="email@example.com"
                            class="w-full bg-transparent py-2.5 pl-10 pr-3 text-xs md:text-sm text-slate-700 outline-none placeholder:text-slate-300">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Số điện thoại</label>
                        <div class="input-box relative flex items-center bg-slate-50/70 border border-slate-200 rounded-xl overflow-hidden">
                            <span class="absolute left-3.5 text-slate-400 text-xs"><i class="fa fa-phone"></i></span>
                            <input name="sdt" type="tel" maxlength="10" pattern="[0-9]{10}" required placeholder="0912345678"
                                class="w-full bg-transparent py-2.5 pl-10 pr-3 text-xs md:text-sm text-slate-700 outline-none placeholder:text-slate-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Ngày sinh</label>
                        <div class="input-box relative flex items-center bg-slate-50/70 border border-slate-200 rounded-xl overflow-hidden">
                            <input name="ngaysinh" type="date"
                                class="w-full bg-transparent py-2.5 px-3 text-xs md:text-sm text-slate-700 outline-none">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Địa chỉ</label>
                    <div class="input-box relative flex items-center bg-slate-50/70 border border-slate-200 rounded-xl overflow-hidden">
                        <span class="absolute left-3.5 text-slate-400 text-xs"><i class="fa fa-location-dot"></i></span>
                        <input name="diachi" required placeholder="Số nhà, tên đường, phường/xã..."
                            class="w-full bg-transparent py-2.5 pl-10 pr-3 text-xs md:text-sm text-slate-700 outline-none placeholder:text-slate-300">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Mật khẩu</label>
                    <div class="input-box relative flex items-center bg-slate-50/70 border border-slate-200 rounded-xl overflow-hidden">
                        <span class="absolute left-3.5 text-slate-400 text-xs"><i class="fa fa-lock"></i></span>
                        <input name="matkhau" type="password" id="matkhau" required placeholder="••••••••"
                            class="w-full bg-transparent py-2.5 pl-10 pr-10 text-xs md:text-sm text-slate-700 outline-none placeholder:text-slate-300">
                        <button type="button" onclick="togglePassword('matkhau', 'eye-1')" class="absolute right-3.5 text-slate-400 hover:text-rose-600 transition text-xs">
                            <i id="eye-1" class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nhập lại mật khẩu</label>
                    <div class="input-box relative flex items-center bg-slate-50/70 border border-slate-200 rounded-xl overflow-hidden">
                        <span class="absolute left-3.5 text-slate-400 text-xs"><i class="fa fa-lock"></i></span>
                        <input name="rematkhau" type="password" id="rematkhau" required placeholder="••••••••"
                            class="w-full bg-transparent py-2.5 pl-10 pr-10 text-xs md:text-sm text-slate-700 outline-none placeholder:text-slate-300">
                        <button type="button" onclick="togglePassword('rematkhau', 'eye-2')" class="absolute right-3.5 text-slate-400 hover:text-rose-600 transition text-xs">
                            <i id="eye-2" class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="btn-effect w-full bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white py-3 rounded-xl font-bold text-xs tracking-wider uppercase mt-1">
                    Đăng ký
                </button>
            </form>

            <!-- Footer link -->
            <p class="text-center text-xs text-slate-400 mt-5">
                Đã có tài khoản? 
                <a href="login.php" class="text-rose-600 font-bold hover:underline ml-1">Đăng nhập</a>
            </p>
        </div>

    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>