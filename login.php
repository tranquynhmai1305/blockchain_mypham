<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $auth_key = trim($_POST['auth_key'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $wallet_address = trim($_POST['wallet_address'] ?? ''); // Lấy địa chỉ ví gửi từ JS

    if ($auth_key === '' || $password === '') {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'user' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $auth_key, $auth_key);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($password === $user['password']) {
                $temp_cart = $_SESSION['giohang'] ?? [];
                $temp_redirect = $_SESSION['redirect_after_login'] ?? '';

                session_regenerate_id(true);

                $_SESSION['giohang']   = $temp_cart;
                $_SESSION['userid']    = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];

                // Lấy makh tương ứng với id_users để cập nhật ví MetaMask nếu có
                $sql_kh = "SELECT makh FROM khachhang WHERE id_users = ?";
                $stmt_kh = $conn->prepare($sql_kh);
                $stmt_kh->bind_param("i", $user['id']);
                $stmt_kh->execute();
                $res_kh = $stmt_kh->get_result();
                
                if ($res_kh && $res_kh->num_rows > 0) {
                    $row_kh = $res_kh->fetch_assoc();
                    $_SESSION['makh'] = $row_kh['makh'];

                    // Nếu người dùng có kết nối ví MetaMask, cập nhật vào bảng khachhang
                    if (!empty($wallet_address)) {
                        $sql_up_wallet = "UPDATE khachhang SET wallet_address = ? WHERE makh = ?";
                        $stmt_wallet = $conn->prepare($sql_up_wallet);
                        $stmt_wallet->bind_param("si", $wallet_address, $row_kh['makh']);
                        $stmt_wallet->execute();
                    }
                }

                if (!empty($temp_redirect)) {
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $temp_redirect");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $error = "Sai mật khẩu!";
            }
        } else {
            $error = "Tài khoản không tồn tại!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #fff1f2;
            background-image: 
                radial-gradient(at 10% 20%, rgba(244, 63, 94, 0.25) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(236, 72, 153, 0.2) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(255, 228, 230, 0.5) 0px, transparent 60%);
            position: relative;
            overflow-x: hidden;
        }
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

    <div class="main-container w-full max-w-4xl rounded-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">
        
        <!-- Left Side: Banner -->
        <div class="relative hidden md:flex flex-col justify-between p-10 bg-gradient-to-br from-rose-600 via-rose-500 to-pink-500 text-white overflow-hidden">
            <div class="absolute -top-12 -left-12 w-64 h-64 rounded-full bg-white/15 blur-xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -right-12 w-64 h-64 rounded-full bg-black/10 blur-xl pointer-events-none"></div>

            <div class="relative z-10 flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-lg bg-white/20 backdrop-blur-md flex items-center justify-center shadow-inner">
                    <i class="fa-solid fa-spa text-white text-base"></i>
                </div>
                <span class="font-bold tracking-wider text-sm">TRIPLE BEAUTY</span>
            </div>

            <div class="relative z-10 my-auto py-8">
                <span class="inline-block px-3 py-1 rounded-md bg-white/20 backdrop-blur-md text-[11px] font-semibold tracking-wider uppercase mb-4">Chính hãng & Cao cấp</span>
                <h1 class="text-3xl font-extrabold tracking-tight leading-snug mb-3">Tỏa sáng vẻ đẹp tự nhiên của bạn</h1>
                <p class="text-rose-100 text-xs leading-relaxed font-light">Đăng nhập kết hợp ví Web3 để khám phá dòng mỹ phẩm độc quyền và tích điểm thưởng Blockchain.</p>
            </div>

            <div class="relative z-10 text-[11px] text-rose-100/70 font-medium">
                &copy; 2026 Triple Beauty. All rights reserved.
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="p-8 md:p-10 flex flex-col justify-center bg-white/80">
            
            <div class="mb-6">
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Chào mừng trở lại</h2>
                <p class="text-slate-400 text-xs mt-1">Đăng nhập tài khoản & kết nối ví MetaMask</p>
            </div>

            <!-- Error Message -->
            <?php if($error): ?>
                <div class="bg-rose-50 text-rose-600 text-xs font-semibold p-3 rounded-xl mb-5 text-center border border-rose-100 flex items-center justify-center gap-2 animate-pulse">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" id="loginForm" class="space-y-4">
                <!-- Input ẩn lưu địa chỉ ví MetaMask -->
                <input type="hidden" name="wallet_address" id="wallet_address" value="">

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tài khoản / Email</label>
                    <div class="input-box relative flex items-center bg-slate-50/70 border border-slate-200 rounded-xl overflow-hidden">
                        <span class="absolute left-3.5 text-slate-400 text-sm">
                            <i class="fa fa-user"></i>
                        </span>
                        <input type="text" name="auth_key" required
                            class="w-full bg-transparent py-3 pl-10 pr-3 text-xs md:text-sm text-slate-700 outline-none placeholder:text-slate-300"
                            placeholder="Nhập tên đăng nhập hoặc email">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Mật khẩu</label>
                        <a href="#" class="text-[11px] font-semibold text-rose-600 hover:underline">Quên mật khẩu?</a>
                    </div>
                    <div class="input-box relative flex items-center bg-slate-50/70 border border-slate-200 rounded-xl overflow-hidden">
                        <span class="absolute left-3.5 text-slate-400 text-sm">
                            <i class="fa fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" required
                            class="w-full bg-transparent py-3 pl-10 pr-10 text-xs md:text-sm text-slate-700 outline-none placeholder:text-slate-300"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword()" class="absolute right-3.5 text-slate-400 hover:text-rose-600 transition text-sm">
                            <i id="eye-icon" class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Nút kết nối MetaMask tùy chọn trực tiếp tại trang Login -->
                <div class="pt-1">
                    <button type="button" id="connectMetaMaskBtn" onclick="connectWallet()"
                        class="w-full bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-300 py-2.5 rounded-xl font-bold text-xs tracking-wider uppercase flex items-center justify-center gap-2 transition">
                        <i class="fa-brands fa-ethereum text-amber-600"></i>
                        <span id="walletBtnText">Kết nối ví MetaMask</span>
                    </button>
                    <p id="walletStatus" class="text-[10px] text-slate-400 text-center mt-1">Trạng thái ví: Chưa kết nối</p>
                </div>

                <button type="submit"
                    class="btn-effect w-full bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white py-3.5 rounded-xl font-bold text-xs tracking-wider uppercase mt-2">
                    Đăng nhập hệ thống
                </button>
            </form>

            <p class="text-center text-xs text-slate-400 mt-6">
                Bạn chưa có tài khoản? 
                <a href="dangky.php" class="text-rose-600 font-bold hover:underline ml-1">Đăng ký ngay</a>
            </p>
        </div>

    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById("password");
            const icon = document.getElementById("eye-icon");
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

        // Hàm xử lý kết nối MetaMask khi người dùng bấm nút
       async function connectWallet() {
            if (typeof window.ethereum !== "undefined") {
                try {
                    // Ép MetaMask bật popup cho phép người dùng chọn lại tài khoản ví muốn kết nối
                    await window.ethereum.request({
                        method: "wallet_requestPermissions",
                        params: [{ eth_accounts: {} }]
                    });

                    // Sau khi cấp quyền, lấy danh sách tài khoản mới chọn
                    const accounts = await window.ethereum.request({ method: "eth_requestAccounts" });
                    const userWallet = accounts[0];

                    // Gán địa chỉ ví vào input ẩn để gửi lên server khi submit form
                    document.getElementById("wallet_address").value = userWallet;
                    
                    // Cập nhật giao diện nút bấm thành công
                    const btnText = document.getElementById("walletBtnText");
                    const statusText = document.getElementById("walletStatus");
                    const btn = document.getElementById("connectMetaMaskBtn");

                    btn.classList.remove("bg-amber-50", "text-amber-700", "border-amber-300");
                    btn.classList.add("bg-emerald-50", "text-emerald-700", "border-emerald-300");
                    btnText.innerText = "Đã liên kết ví mới";
                    statusText.innerText = "Đã kết nối: " + userWallet.substring(0, 6) + "..." + userWallet.substring(userWallet.length - 4);
                    statusText.classList.remove("text-slate-400");
                    statusText.classList.add("text-emerald-600", "font-semibold");

                } catch (error) {
                    console.error("Người dùng từ chối kết nối ví:", error);
                    alert("Bạn đã hủy yêu cầu kết nối ví MetaMask.");
                }
            } else {
                alert("Vui lòng cài đặt tiện ích mở rộng MetaMask trên trình duyệt của bạn!");
            }
        }
    </script>
</body>
</html>