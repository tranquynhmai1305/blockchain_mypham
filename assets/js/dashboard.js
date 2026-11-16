// Đếm số chạy mượt (counter effect)
document.querySelectorAll('.stat p').forEach(el => {
    const target = parseInt(el.innerText.replace(/\D/g, ''));
    let count = 0;
    const step = target / 40;

    const interval = setInterval(() => {
        count += step;
        if (count >= target) {
            el.innerText = target.toLocaleString();
            clearInterval(interval);
        } else {
            el.innerText = Math.floor(count).toLocaleString();
        }
    }, 20);
});
