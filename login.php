<?php
// Gọi kết nối CSDL và khởi động session
include 'config/db.php';
include 'includes/header.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten_dang_nhap = trim($_POST['ten_dang_nhap']);
    $mat_khau = trim($_POST['mat_khau']);
    $wallet_address = trim($_POST['wallet_address'] ?? ''); // Lấy địa chỉ ví từ form gửi lên

    // Sử dụng Prepared Statement để bảo mật, chống lỗi SQL Injection
    $stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE ten_dang_nhap = ?");
    $stmt->bind_param("s", $ten_dang_nhap);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Kiểm tra mật khẩu mã hóa
        if (password_verify($mat_khau, $row['mat_khau'])) {
            // Lưu thông tin phiên đăng nhập (Session)
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['ten_dang_nhap'] = $row['ten_dang_nhap'];
            $_SESSION['vai_tro'] = trim($row['vai_tro']);
            
            // Nếu người dùng có kết nối ví ở trang login, cập nhật luôn vào CSDL và Session
            if (!empty($wallet_address)) {
                $update_wallet = $conn->prepare("UPDATE nguoi_dung SET wallet_address = ? WHERE id = ?");
                $update_wallet->bind_param("si", $wallet_address, $row['id']);
                $update_wallet->execute();
                $_SESSION['wallet_address'] = $wallet_address;
            } else {
                $_SESSION['wallet_address'] = $row['wallet_address'];
            }

            // Phân quyền điều hướng sau khi đăng nhập thành công
            if ($_SESSION['vai_tro'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: user/index.php");
            }
            exit();
        } else {
            $error = "Mật khẩu không chính xác!";
        }
    } else {
        $error = "Tên đăng nhập không tồn tại!";
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow p-4">
                <h3 class="text-center mb-4">🔐 Đăng Nhập Hệ Thống</h3>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <!-- Input ẩn để lưu địa chỉ ví MetaMask khi người dùng bấm kết nối -->
                    <input type="hidden" name="wallet_address" id="wallet_address_input">

                    <div class="mb-3">
                        <label class="form-label">Tên Đăng Nhập:</label>
                        <input type="text" name="ten_dang_nhap" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật Khẩu:</label>
                        <input type="password" name="mat_khau" class="form-control" required>
                    </div>

                    <!-- Phần tích hợp nút kết nối MetaMask tại trang đăng nhập -->
                    <div class="mb-3 p-3 bg-light rounded border text-center">
                        <label class="form-label d-block fw-bold text-muted">Xác thực ví Blockchain (Tùy chọn):</label>
                        <button type="button" class="btn btn-outline-warning text-dark w-100 mb-2" onclick="connectMetaMaskForLogin()">
                            🦊 Kết Nối Ví MetaMask
                        </button>
                        <span id="wallet-status" class="small text-success fw-bold">Chưa kết nối ví</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Đăng Nhập</button>
                </form>

                <div class="text-center mt-3">
                    <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Nhúng các thư viện Web3 để hỗ trợ MetaMask -->
<script src="assets/js/ethers.umd.min.js"></script>
<script src="assets/js/contract_abi.js"></script>
<script>
    // Hàm kết nối MetaMask riêng cho trang Login
    let userWalletAddress = "";
    async function connectMetaMaskForLogin() {
        if (typeof window.ethereum !== 'undefined') {
            try {
                const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                userWalletAddress = accounts[0];
                
                // Hiển thị trạng thái rút gọn lên giao diện
                document.getElementById('wallet-status').innerText = "Đã kết nối: " + userWalletAddress.substring(0, 6) + "..." + userWalletAddress.slice(-4);
                
                // Gán địa chỉ ví vào input ẩn để gửi ngầm lên PHP khi bấm Đăng Nhập
                document.getElementById('wallet_address_input').value = userWalletAddress;
            } catch (error) {
                console.error("Người dùng từ chối kết nối ví:", error);
                alert("Bạn đã hủy kết nối ví MetaMask.");
            }
        } else {
            alert("Vui lòng cài đặt tiện ích mở rộng MetaMask trên trình duyệt!");
        }
    }
</script>

<?php include 'includes/footer.php'; ?>