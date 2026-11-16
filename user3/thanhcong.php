
<?php
//Sau khi đặt hàng thành công ở file xulythanhtoan.php, thay vì chuyển thẳng về donhangcuatoi.php, 
//bạn có thể chuyển hướng khách hàng sang trang thanhcong.php?madh=.... 
//Tại đây, website sẽ dùng Ethers.js kết nối ví và tiến hành phân phối điểm CLT trên Blockchain.
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

if (!isset($_SESSION['userid']) || !isset($_GET['madh'])) {
    header("Location: giohang.php");
    exit;
}

$madh = $_GET['madh'];
$userid = $_SESSION['userid'];

// Lấy thông tin đơn hàng và ví của khách hàng
$sql = "SELECT d.*, k.wallet_address FROM donhang d JOIN khachhang k ON d.makh = k.makh WHERE d.madh = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $madh);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$donhang = mysqli_fetch_assoc($result);

if (!$donhang) {
    die("Không tìm thấy đơn hàng.");
}

// Tính số điểm thưởng quy đổi (Ví dụ: 10.000đ = 1 điểm)
$so_diem = floor($donhang['tongtien'] / 10000);
$wallet_khach = $donhang['wallet_address'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác nhận điểm Blockchain | Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/5.7.2/ethers.umd.min.js"></script>
    <!-- Nhúng file cấu hình contract chứa CONTRACT_ADDRESS và CONTRACT_ABI -->
    <script src="../assets/js/contractConfig.js"></script>
</head>
<body class="bg-rose-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">✓</div>
        <h1 class="text-2xl font-bold text-slate-800 mb-2">Đặt hàng thành công!</h1>
        <p class="text-sm text-slate-600 mb-6">Mã đơn hàng: <span class="font-semibold text-slate-800"><?= $madh ?></span></p>

        <div class="bg-slate-50 border p-4 rounded-xl mb-6 text-left">
            <p class="text-xs text-slate-500">Ví nhận điểm (Customer):</p>
            <p class="font-mono text-xs font-bold text-rose-600 truncate"><?= $wallet_khach ?: 'Chưa liên kết ví' ?></p>
            <p class="text-xs text-slate-500 mt-2">Điểm thưởng quy đổi:</p>
            <p class="text-lg font-extrabold text-amber-600"><?= $so_diem ?> CLT</p>
        </div>

        <?php if (!empty($wallet_khach)): ?>
            <button id="btnDistribute" onclick="distributeTokenOnChain()" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-3 rounded-xl transition text-sm uppercase tracking-wider mb-3">
                Xác nhận ghi điểm lên Blockchain
            </button>
        <?php else: ?>
            <p class="text-xs text-rose-500 mb-3">Khách hàng chưa liên kết ví MetaMask nên không thể nhận điểm Smart Contract.</p>
        <?php endif; ?>

        <a href="donhangcuatoi.php" class="text-xs text-slate-500 hover:underline">Bỏ qua, về danh sách đơn hàng</a>

        <p id="statusLog" class="text-xs text-slate-500 mt-4 font-mono"></p>
    </div>

    <script>
        const customerWallet = "<?= $wallet_khach ?>";
        const orderCode = "<?= $madh ?>";
        const pointsToAward = <?= $so_diem ?>;

        async function distributeTokenOnChain() {
            const statusLog = document.getElementById("statusLog");
            const btn = document.getElementById("btnDistribute");

            if (typeof window.ethereum === "undefined") {
                alert("Vui lòng cài đặt ví MetaMask!");
                return;
            }

            try {
                btn.disabled = true;
                btn.innerText = "Đang kết nối ví...";
                statusLog.innerText = "Đang yêu cầu kết nối ví MetaMask...";

                const provider = new ethers.providers.Web3Provider(window.ethereum);
                await provider.send("eth_requestAccounts", []);
                const signer = provider.getSigner();

                statusLog.innerText = "Đang khởi tạo Smart Contract...";
                const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);

                statusLog.innerText = "Đang gọi hàm awardPoints() trên Sepolia. Vui lòng xác nhận giao dịch trên ví...";
                
                // Gọi hàm awardPoints(address recipient, uint256 points, string orderCode)
                const tx = await contract.awardPoints(customerWallet, pointsToAward, orderCode);
                
                statusLog.innerText = "Giao dịch đã gửi đi (TxHash: " + tx.hash + "). Đang chờ xác nhận từ mạng lưới...";
                
                // Đợi blockchain đào xong block xác nhận giao dịch
                await tx.wait();

                statusLog.innerText = "Thành công! Điểm đã được ghi lên Blockchain.";
                btn.innerText = "Hoàn tất";
                btn.classList.remove("bg-rose-600", "hover:bg-rose-700");
                btn.classList.add("bg-emerald-600");

                // Gửi TxHash ngầm về server PHP để cập nhật vào bảng diem_thuong
                fetch('update_txhash.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `madh=${orderCode}&tx_hash=${tx.hash}`
                }).then(() => {
                    setTimeout(() => {
                        window.location.href = "donhangcuatoi.php";
                    }, 2000);
                });

            } catch (error) {
                console.error(error);
                statusLog.innerText = "Lỗi: " + (error.reason || error.message);
                btn.disabled = false;
                btn.innerText = "Thử lại xác nhận Blockchain";
            }
        }
    </script>
</body>
</html>