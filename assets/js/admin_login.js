document.addEventListener('DOMContentLoaded', () => {
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const loginForm = document.querySelector('#loginForm');

    // 1. Ẩn hiện mật khẩu
    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Đổi icon con mắt
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    // 2. Hiệu ứng khi submit
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const btn = document.querySelector('.btn-login');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
        btn.style.opacity = '0.7';
        
        // Giả lập gửi dữ liệu
        setTimeout(() => {
            alert('Đăng nhập thành công!');
            btn.innerHTML = 'Login';
            btn.style.opacity = '1';
        }, 2000);
    });
});