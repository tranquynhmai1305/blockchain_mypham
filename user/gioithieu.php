<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thương Hiệu - Triple Beauty</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts - Cập nhật hỗ trợ tiếng Việt đầy đủ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Merriweather:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Thiết lập font chữ mặc định hỗ trợ tiếng Việt tốt hơn */
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            background-color: #f5e9e9; 
            color: #2d4739; 
        }
        .font-serif { 
            font-family: 'Merriweather', Georgia, serif; 
        }
        
        /* Boshop Style Colors */
        .bg-boshop { background-color: #a31d37; }
        .text-boshop { color: #a31d37; }
        .bg-clay { background-color: #f4f1ea; }
        
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        .fade-in { animation: fadeIn 1.5s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .img-zoom { transition: transform 0.5s ease; }
        .img-zoom:hover { transform: scale(1.05); }
    </style>
</head>
<body class="overflow-x-hidden text-gray-800">

   <!-- HEADER -->
    <header class="sticky top-0 z-50 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-rose-600 to-pink-600 py-3 text-white">
            <div class="container mx-auto px-4 flex items-center justify-between">
                <a href="index.php" class="flex flex-col leading-none">
                    <span class="text-2xl md:text-3xl font-black italic tracking-tighter uppercase">Triple Beauty</span>
                    <span class="text-[9px] font-medium tracking-widest uppercase opacity-90">Cosmetics & Skincare</span>
                </a>

                <form action="timkiem.php" method="GET" class="hidden md:flex flex-1 mx-8 relative max-w-xl">
                    <input type="text" 
                        name="keyword"
                        placeholder="Tìm kiếm mỹ phẩm, thương hiệu, danh mục..." 
                        class="w-full bg-white text-gray-800 text-sm rounded-full py-2.5 pl-6 pr-12 focus:ring-2 focus:ring-pink-300 outline-none shadow-inner transition">
                    <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 w-8 h-8 bg-rose-600 rounded-full flex items-center justify-center text-white hover:bg-rose-700 transition">
                        <i class="fa fa-search text-xs"></i>
                    </button>
                </form>

                <div class="flex items-center space-x-5 text-xs font-semibold">
                    <div class="hidden lg:flex items-center gap-2 cursor-pointer hover:opacity-80 transition bg-white/10 px-3 py-1.5 rounded-lg">
                        <i class="fa-solid fa-store text-sm"></i>
                        <div class="flex flex-col leading-tight text-[10px]">
                            <span>HỆ THỐNG</span>
                            <span class="font-bold">CỬA HÀNG</span>
                        </div>
                    </div>

                    <?php if(isset($_SESSION['username'])): ?>
                    <div class="flex flex-col items-start">
                        <span class="text-[11px] opacity-80">Xin chào,</span>
                        <span class="font-bold truncate max-w-[100px]"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        <a href="logout.php" class="text-[10px] text-pink-200 hover:underline">Đăng xuất</a>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center gap-1.5 font-bold">
                        <a href="login.php" class="hover:text-pink-200 transition">Đăng nhập</a>
                        <span class="opacity-50">/</span>
                        <a href="dangky.php" class="hover:text-pink-200 transition">Đăng ký</a>
                    </div>
                    <?php endif; ?>

                   
                </div>
            </div>
        </div>

        <!-- NAVIGATION -->
        <nav class="border-b bg-white shadow-xs overflow-x-auto no-scrollbar">
            <div class="container mx-auto px-4 flex space-x-8 text-xs font-bold uppercase tracking-wider whitespace-nowrap text-gray-600 py-3">
                <a href="gioithieu.php" class="text-rose-600 border-b-2 border-rose-600 pb-1">Giới thiệu</a>
                <a href="index.php" class="hover:text-rose-600 transition"> <i class="fa-solid fa-house"></i> Trang chủ</a>
                <a href="donhangcuatoi.php" class="hover:text-rose-600 transition">Đơn hàng của tôi</a>
                <a href="sanpham_damua.php" class="hover:text-rose-600 transition">Lịch sử đơn mua</a>
                <a href="voucher.php" class="hover:text-rose-600 transition">Voucher</a>
                <a href="profile.php" class="hover:text-rose-600 transition">Hồ sơ</a>
                <a href="yeuthich.php" class="hover:text-rose-600 transition">Yêu thích</a>
            </div>
        </nav>
    </header>

    <!-- Hero Content -->
    <section class="relative py-24 flex items-center justify-center text-center px-4 overflow-hidden bg-white">
        <div class="fade-in max-w-4xl relative z-10">
            <h2 class="text-5xl md:text-7xl font-black italic text-boshop mb-6 uppercase tracking-tighter">Triple Beauty</h2>
            <p class="text-2xl md:text-3xl text-gray-400 italic mb-8 font-serif leading-relaxed">"Sắc đẹp khởi nguồn từ lòng tự trọng"</p>
            <div class="w-24 h-1.5 bg-boshop mx-auto mb-10"></div>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto leading-relaxed font-light">
                Tại Triple Beauty, chúng tôi không định nghĩa vẻ đẹp qua những quy chuẩn khắt khe. Chúng tôi tôn vinh sự khác biệt và giúp bạn tỏa sáng theo cách riêng của mình.
            </p>
        </div>
        <!-- Decorative elements -->
        <div class="absolute top-0 left-0 w-64 h-64 bg-boshop opacity-[0.03] rounded-full -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-boshop opacity-[0.02] rounded-full translate-x-1/3 translate-y-1/3"></div>
    </section>

    <!-- Vision & Mission -->
    <section class="py-24 bg-white px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-2 gap-16 items-center mb-24">
                <div class="order-2 md:order-1">
                    <div class="overflow-hidden rounded-2xl shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1556228720-195a672e8a03?q=80&w=1200" alt="Showroom mỹ phẩm sang trọng" class="w-full h-[500px] object-cover img-zoom">
                    </div>
                </div>
                <div class="order-1 md:order-2">
                    <span class="text-boshop font-bold tracking-[0.2em] uppercase text-sm mb-4 block">Tầm nhìn & Sứ mệnh</span>
                    <h2 class="text-4xl font-black mb-8 leading-tight font-serif italic text-gray-900">Trở thành điểm tựa vững chắc cho vẻ đẹp Việt</h2>
                    <p class="text-gray-600 leading-relaxed mb-6 text-lg">
                        Triple Beauty khát khao xây dựng một hệ sinh thái làm đẹp toàn diện, nơi khách hàng không chỉ tìm thấy mỹ phẩm tốt nhất mà còn tìm thấy chính mình.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-check-circle text-boshop mt-1"></i>
                            <span class="text-gray-700"><strong>Sứ mệnh:</strong> Đưa mỹ phẩm chính hãng chất lượng quốc tế đến gần hơn với mọi người Việt.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-check-circle text-boshop mt-1"></i>
                            <span class="text-gray-700"><strong>Tầm nhìn:</strong> Hệ thống bán lẻ mỹ phẩm uy tín số 1 về trải nghiệm khách hàng tại Việt Nam.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Philosophy Details -->
    <section class="py-24 bg-clay px-4">
        <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-16 items-center">
            <div>
                <h2 class="text-3xl md:text-5xl font-black mb-8 border-l-8 border-boshop pl-8 font-serif uppercase tracking-tight leading-tight">
                    Làm đẹp là một hành trình tử tế
                </h2>
                <p class="text-gray-800 leading-relaxed mb-8 italic text-xl font-serif font-medium">
                    "Sản phẩm của chúng tôi có thể hết, nhưng sự tử tế với khách hàng thì luôn đong đầy."
                </p>
                <div class="space-y-6 text-gray-600 text-lg leading-relaxed">
                    <p>
                        Chúng tôi hiểu rằng làn da là tài sản quý giá nhất của bạn. Đó là lý do Triple Beauty không bao giờ thỏa hiệp với hàng kém chất lượng. 
                    </p>
                    <p>
                        Triết lý <strong>"Tử tế từ tâm"</strong> được áp dụng từ khâu chọn lọc thương hiệu đối tác đến cách các nhân viên tư vấn tận tình cho từng tình trạng da cụ thể.
                    </p>
                </div>
            </div>
            <div class="relative grid grid-cols-2 gap-4">
                <div class="space-y-4">
                    <img src="images/chup-anh-my-pham (3).jpg" alt="Mỹ phẩm thiên nhiên" class="rounded-2xl shadow-lg h-64 w-full object-cover">
                    <img src="https://images.unsplash.com/photo-1512496015851-a90fb38ba796?q=80&w=800" alt="Skincare routine" class="rounded-2xl shadow-lg h-48 w-full object-cover">
                </div>
                <div class="pt-8 space-y-4">
                    <img src="https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?q=80&w=800" alt="Makeup artist" class="rounded-2xl shadow-lg h-48 w-full object-cover">
                    <img src="https://images.unsplash.com/photo-1556229010-6c3f2c9ca5f8?q=80&w=800" alt="Beauty tools" class="rounded-2xl shadow-lg h-64 w-full object-cover">
                </div>
                <div class="absolute -z-10 bg-boshop/10 w-full h-full rounded-full scale-110 blur-3xl"></div>
            </div>
        </div>
    </section>

    <!-- Quality Commitment -->
    <section class="py-24 px-4 bg-white overflow-hidden relative">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row items-center gap-12 bg-boshop rounded-[2rem] p-10 md:p-20 text-white shadow-2xl relative z-10">
                <div class="md:w-1/2">
                    <h2 class="text-4xl font-black mb-6 italic tracking-tight font-serif uppercase">Cam kết 3 "Thật"</h2>
                    <p class="text-white/80 text-lg mb-10">Niềm tin của bạn là tài sản lớn nhất của Triple Beauty. Chúng tôi cam kết bằng uy tín của một thương hiệu hàng đầu.</p>
                    <div class="space-y-8">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center text-2xl border border-white/20">01</div>
                            <div>
                                <h4 class="font-bold text-xl uppercase italic">Hàng Thật</h4>
                                <p class="text-white/60 text-sm">100% sản phẩm có nguồn gốc rõ ràng, đầy đủ hóa đơn.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center text-2xl border border-white/20">02</div>
                            <div>
                                <h4 class="font-bold text-xl uppercase italic">Giá trị Thật</h4>
                                <p class="text-white/60 text-sm">Chăm sóc khách hàng bằng kiến thức chuyên môn thật.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center text-2xl border border-white/20">03</div>
                            <div>
                                <h4 class="font-bold text-xl uppercase italic">Tâm Thật</h4>
                                <p class="text-white/60 text-sm">Luôn đặt lợi ích sức khỏe làn da khách hàng lên hàng đầu.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="md:w-1/2 relative">
                    <div class="bg-white/5 p-4 rounded-3xl backdrop-blur-md border border-white/10">
                        <img src="https://images.unsplash.com/photo-1612817288484-6f916006741a?q=80&w=1200" alt="Chuyên gia tư vấn" class="rounded-2xl shadow-inner w-full h-[450px] object-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 px-4 bg-white">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row items-stretch rounded-[2rem] overflow-hidden shadow-xl border border-gray-100">
            
            <div class="md:w-1/3 relative overflow-hidden bg-[#f4f1ea] flex items-center justify-center p-8">
                <img src="https://images.unsplash.com/photo-1556228578-0d85b1a4d571?q=80&w=800" 
                     alt="Mỹ phẩm Triple Beauty" 
                     class="w-full h-full object-cover rounded-xl img-zoom">
                <div class="absolute inset-0 bg-black/5 pointer-events-none"></div>
            </div>

            <div class="md:w-2/3 bg-boshop p-10 md:p-16 flex flex-col justify-center text-white relative">
                <div class="absolute right-10 top-10 opacity-10 text-9xl">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                
                <h2 class="text-3xl md:text-5xl font-black italic mb-6 uppercase tracking-tighter leading-tight">
                    Uy tín tạo nên thương hiệu
                </h2>
                
                <div class="w-20 h-1.5 bg-white mb-8"></div>
                
                <p class="text-lg md:text-xl leading-relaxed font-light text-white/90 mb-6">
                    Chúng tôi hợp tác trực tiếp với hơn <span class="font-bold text-yellow-400 text-2xl">200</span> nhãn hàng mỹ phẩm nổi tiếng thế giới để đảm bảo mỗi sản phẩm đến tay bạn là sự an tâm tuyệt đối.
                </p>
                
                <p class="text-lg md:text-xl leading-relaxed italic font-serif">
                    "Triple Beauty không chỉ là nơi mua sắm, mà là nơi bạn gửi gắm niềm tin cho nhan sắc của mình."
                </p>
                
                <div class="mt-10 flex flex-wrap gap-6 items-center opacity-60">
                    <span class="text-[10px] font-bold tracking-widest uppercase border border-white/30 px-3 py-1 rounded">Authentic Only</span>
                    <span class="text-[10px] font-bold tracking-widest uppercase border border-white/30 px-3 py-1 rounded">Global Brands</span>
                    <span class="text-[10px] font-bold tracking-widest uppercase border border-white/30 px-3 py-1 rounded">Direct Partnership</span>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Values Grid -->
    <section class="py-24 px-4 bg-[#fcfcfc]">
        <div class="max-w-6xl mx-auto text-center mb-16">
            <h2 class="text-4xl font-black mb-4 italic text-boshop uppercase tracking-tighter">Giá trị cốt lõi</h2>
            <div class="w-20 h-1 bg-boshop mx-auto"></div>
        </div>
        <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-10">
            <div class="bg-white p-10 rounded-3xl shadow-sm border-t-8 border-boshop hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 group">
                <div class="text-5xl text-boshop mb-8 group-hover:scale-110 transition-transform"><i class="fa-solid fa-leaf"></i></div>
                <h3 class="text-2xl font-black mb-4 uppercase tracking-tighter">Sự Lành Tính</h3>
                <p class="text-gray-500 text-md leading-relaxed">
                    Mỹ phẩm phải an toàn như chính sự nâng niu mà bạn dành cho bản thân. Chúng tôi ưu tiên các thành phần hữu cơ, thân thiện với môi trường.
                </p>
            </div>
            <div class="bg-white p-10 rounded-3xl shadow-sm border-t-8 border-boshop hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 group">
                <div class="text-5xl text-boshop mb-8 group-hover:scale-110 transition-transform"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <h3 class="text-2xl font-black mb-4 uppercase tracking-tighter">Sự Hiệu Quả</h3>
                <p class="text-gray-500 text-md leading-relaxed">
                    Chúng tôi không bán lời hứa suông. Mỗi sản phẩm được chọn lọc dựa trên kết quả thực chứng và sự tin dùng của hàng triệu khách hàng thế giới.
                </p>
            </div>
            <div class="bg-white p-10 rounded-3xl shadow-sm border-t-8 border-boshop hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 group">
                <div class="text-5xl text-boshop mb-8 group-hover:scale-110 transition-transform"><i class="fa-solid fa-face-smile-beam"></i></div>
                <h3 class="text-2xl font-black mb-4 uppercase tracking-tighter">Sự Tự Tin</h3>
                <p class="text-gray-500 text-md leading-relaxed">
                    Mục tiêu cuối cùng là giúp phụ nữ Việt Nam tự tin làm chủ cuộc sống, bắt đầu từ việc thấu hiểu và yêu chiều diện mạo của chính mình.
                </p>
            </div>
        </div>
    </section>

    <!-- Experts Section -->
    <section class="py-24 px-4 bg-white">
        <div class="max-w-6xl mx-auto text-center mb-16">
            <span class="text-boshop font-bold tracking-[0.2em] uppercase text-sm mb-4 block">Đội ngũ của chúng tôi</span>
            <h2 class="text-4xl font-black mb-4 font-serif text-gray-900">Những người kiến tạo vẻ đẹp</h2>
            <p class="text-gray-500 max-w-xl mx-auto">Chúng tôi là tập hợp những con người đam mê làm đẹp và luôn sẵn sàng lắng nghe làn da của bạn.</p>
        </div>
        <div class="max-w-5xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="text-center group">
                <div class="mb-4 overflow-hidden rounded-full aspect-square border-4 border-clay group-hover:border-boshop transition-colors">
                    <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?q=80&w=400" alt="Team member" class="w-full h-full object-cover">
                </div>
                <h4 class="font-bold text-gray-900 uppercase">Minh Anh</h4>
                <p class="text-xs text-boshop font-medium uppercase tracking-widest">Skin Specialist</p>
            </div>
            <div class="text-center group">
                <div class="mb-4 overflow-hidden rounded-full aspect-square border-4 border-clay group-hover:border-boshop transition-colors">
                    <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=400" alt="Team member" class="w-full h-full object-cover">
                </div>
                <h4 class="font-bold text-gray-900 uppercase">Thảo Vy</h4>
                <p class="text-xs text-boshop font-medium uppercase tracking-widest">Makeup Artist</p>
            </div>
            <div class="text-center group">
                <div class="mb-4 overflow-hidden rounded-full aspect-square border-4 border-clay group-hover:border-boshop transition-colors">
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400" alt="Team member" class="w-full h-full object-cover">
                </div>
                <h4 class="font-bold text-gray-900 uppercase">Lê Hà</h4>
                <p class="text-xs text-boshop font-medium uppercase tracking-widest">Product Curator</p>
            </div>
            <div class="text-center group">
                <div class="mb-4 overflow-hidden rounded-full aspect-square border-4 border-clay group-hover:border-boshop transition-colors">
                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=400" alt="Team member" class="w-full h-full object-cover">
                </div>
                <h4 class="font-bold text-gray-900 uppercase">Ngọc Trinh</h4>
                <p class="text-xs text-boshop font-medium uppercase tracking-widest">Beauty Consultant</p>
            </div>
        </div>
    </section>

    <!-- Quote -->
    <section class="py-40 bg-boshop text-white text-center px-4 relative overflow-hidden">
        <div class="max-w-4xl mx-auto relative z-10">
            <i class="fa-solid fa-quote-left text-7xl text-white opacity-20 mb-10"></i>
            <h2 class="text-3xl md:text-5xl font-serif italic leading-[1.4] mb-10">
                "Vẻ đẹp không nằm ở má hồng của người thiếu nữ mà nằm trong ánh mắt kẻ si tình, và Triple Beauty sẽ giúp bạn trở thành 'kẻ si tình' với chính bản thân mình."
            </h2>
            <div class="flex items-center justify-center gap-4">
                <div class="w-12 h-[1px] bg-white/40"></div>
                <p class="uppercase tracking-[0.4em] text-white/90 font-black text-sm">Triple Beauty Philosophy</p>
                <div class="w-12 h-[1px] bg-white/40"></div>
            </div>
        </div>
        <!-- Abstract light -->
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-white opacity-[0.05] rounded-full blur-[100px] -translate-y-1/2 translate-x-1/2"></div>
    </section>

    <!-- Footer -->
    <footer class="py-16 bg-white text-center border-t border-gray-100">
        <div class="mb-6">
            <h2 class="text-3xl font-black italic text-boshop uppercase tracking-tighter">Triple Beauty</h2>
            <p class="text-gray-400 text-sm mt-2 italic">Hệ thống mỹ phẩm chính hãng & Chăm sóc da chuyên sâu</p>
        </div>
        <div class="flex justify-center space-x-6 mb-8 text-gray-400">
            <a href="#" class="hover:text-boshop transition-colors"><i class="fa-brands fa-facebook-f text-xl"></i></a>
            <a href="#" class="hover:text-boshop transition-colors"><i class="fa-brands fa-instagram text-xl"></i></a>
            <a href="#" class="hover:text-boshop transition-colors"><i class="fa-brands fa-tiktok text-xl"></i></a>
        </div>
        <p class="text-[10px] text-gray-400 font-bold tracking-[0.2em] uppercase">© 2024 TRIPLE BEAUTY - ALL RIGHTS RESERVED</p>
    </footer>

</body>
</html>