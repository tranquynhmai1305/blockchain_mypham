<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

if (!isset($_SESSION['userid'])) {
    header("Location: ../login.php");
    exit;
}

$userid = $_SESSION['userid'];
$sql = "SELECT * FROM khachhang WHERE id_users = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$khachhang = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Điểm thưởng Blockchain | Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Nhúng thư viện Ethers.js bản 5.7.2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/5.7.2/ethers.umd.min.js"></script>
    <!-- Nhúng file cấu hình contract -->
    <script src="../assets/js/contractConfig.js"></script>
</head>
<body class="bg-rose-50 min-h-screen p-6">
    <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-slate-800 mb-4">Tích điểm thưởng Blockchain (CLT)</h1>
        
        <div class="bg-rose-50 border border-rose-200 p-4 rounded-xl mb-6">
            <p class="text-xs text-slate-600">Địa chỉ ví liên kết:</p>
            <p id="userWallet" class="font-mono font-bold text-rose-700 text-sm"><?= htmlspecialchars($khachhang['wallet_address'] ?? 'Chưa liên kết ví') ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-slate-50 border p-4 rounded-xl">
                <span class="text-xs text-slate-500 uppercase font-semibold">Điểm trên CSDL (MySQL)</span>
                <p class="text-3xl font-extrabold text-slate-800 mt-1"><?= number_format($khachhang['diem_tich_luy'] ?? 0) ?> điểm</p>
            </div>
            <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl">
                <span class="text-xs text-amber-600 uppercase font-semibold">Điểm trên Smart Contract (Sepolia)</span>
                <p id="blockchainPoints" class="text-3xl font-extrabold text-amber-700 mt-1">Đang tải...</p>
                <p id="errorDetail" class="text-xs text-rose-500 mt-2 font-mono"></p>
            </div>
        </div>

        <button onclick="loadBlockchainBalance()" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-5 rounded-xl text-xs uppercase tracking-wider transition">
            Làm mới số dư Blockchain
        </button>
    </div>

    <script>
    async function loadBlockchainBalance() {
        const errorEl = document.getElementById("errorDetail");
        errorEl.innerText = "";

        if (typeof window.ethereum !== "undefined") {
            try {
                // Kiểm tra xem file cấu hình contractConfig.js đã load thành công chưa
                if (typeof CONTRACT_ADDRESS === 'undefined' || typeof CONTRACT_ABI === 'undefined') {
                    throw new Error("Không tìm thấy CONTRACT_ADDRESS hoặc CONTRACT_ABI. Kiểm tra lại file contractConfig.js!");
                }

                const provider = new ethers.providers.Web3Provider(window.ethereum);
                const accounts = await provider.send("eth_requestAccounts", []);
                const userAddress = accounts[0];
                
                document.getElementById("userWallet").innerText = userAddress;

                // Khởi tạo contract
                const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, provider);
                
                // Gọi hàm balanceOf trên Smart Contract
                const balance = await contract.balanceOf(userAddress);
                
                document.getElementById("blockchainPoints").innerText = balance.toString() + " CLT";
            } catch (error) {
                console.error("Lỗi chi tiết:", error);
                document.getElementById("blockchainPoints").innerText = "Lỗi kết nối ví";
                errorEl.innerText = error.message; // Hiển thị nguyên nhân lỗi cụ thể lên giao diện
            }
        } else {
            document.getElementById("blockchainPoints").innerText = "Chưa cài MetaMask";
            errorEl.innerText = "Trình duyệt không phát hiện thấy tiện ích ví MetaMask.";
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        loadBlockchainBalance();
    });
    </script>
</body>
</html>