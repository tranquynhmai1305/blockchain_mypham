<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP
$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['masp'])) {

    $masp = trim($_POST['masp']);

    // 2. THU THẬP VÀ CHUẨN HÓA DỮ LIỆU INPUT
    $data = [
        'id_loaisp'     => (int)($_POST['id_loaisp'] ?? 0),
        'tensp'         => trim($_POST['tensp'] ?? ''),
        'gia'           => (float)($_POST['gia'] ?? 0),
        'giakhuyenmai'  => (float)($_POST['giakhuyenmai'] ?? 0),
        'mota'          => trim($_POST['mota'] ?? ''),
        'soluong'       => (int)($_POST['soluong'] ?? 0),
        'id_thuonghieu' => (int)($_POST['id_thuonghieu'] ?? 0),
        'xuatxu'        => trim($_POST['xuatxu'] ?? ''),
        'dungtichsp'    => trim($_POST['dungtichsp'] ?? ''),
        'han_sudung'    => trim($_POST['han_sudung'] ?? '')
    ];

    // 3. CẬP NHẬT THÔNG TIN SẢN PHẨM
    $updated = updateSanPham($masp, $data);

    if ($updated !== false) {
        // 4. GHI NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG)
$userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
$description = "Cập nhật sản phẩm: " . $data['tensp'] . " (Mã: " . $masp . ")";

if (function_exists('save_log')) {
    save_log($conn, $userId, 'SanPham', 'UPDATE', $description);
}

        // 5. XỬ LÝ UPLOAD ẢNH MỚI (CÓ KIỂM TRA BẢO MẬT EXTENSION)
        if (isset($_FILES['hinhanh']) && !empty($_FILES['hinhanh']['name'][0])) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/images/';

            // Tạo thư mục nếu chưa tồn tại
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($_FILES['hinhanh']['name'] as $key => $name) {
                if ($_FILES['hinhanh']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                    // Chỉ cho phép upload đuôi file hình ảnh an toàn
                    if (in_array($ext, $allowedExtensions)) {
                        $filename = time() . '_' . $key . '_' . uniqid() . '.' . $ext;
                        $uploadPath = $uploadDir . $filename;

                        if (move_uploaded_file($_FILES['hinhanh']['tmp_name'][$key], $uploadPath)) {
                            // Lưu vào CSDL theo định dạng đường dẫn tương đối 'images/filename'
                            addHinhAnhSanPham($masp, 'images/' . $filename);
                        }
                    }
                }
            }
        }
        
        $_SESSION['success'] = "Cập nhật sản phẩm mã $masp thành công!";
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra khi cập nhật sản phẩm!";
    }

    header("Location: index.php");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>