document.addEventListener("DOMContentLoaded", function () {
    const connectBtn = document.getElementById("connectWalletBtn");
    const walletAddressSpan = document.getElementById("walletAddressDisplay");

    if (connectBtn) {
        connectBtn.addEventListener("click", async () => {
            if (typeof window.ethereum !== "undefined") {
                try {
                    // Yêu cầu người dùng kết nối MetaMask
                    const accounts = await window.ethereum.request({ method: "eth_requestAccounts" });
                    const userWallet = accounts[0];

                    walletAddressSpan.innerText = userWallet;
                    connectBtn.innerText = "Đã kết nối";
                    connectBtn.classList.remove("btn-primary");
                    connectBtn.classList.add("btn-success");

                    // Gửi địa chỉ ví lên server PHP để lưu vào bảng khachhang
                    fetch("update_wallet.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "wallet_address=" + encodeURIComponent(userWallet)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Đã cập nhật ví vào CSDL thành công!");
                        }
                    })
                    .catch(error => console.error("Lỗi cập nhật ví:", error));

                } catch (error) {
                    console.error("Người dùng từ chối kết nối:", error);
                    alert("Bạn đã hủy kết nối ví MetaMask.");
                }
            } else {
                alert("Vui lòng cài đặt tiện ích mở rộng MetaMask trên trình duyệt!");
            }
        });
    }
});