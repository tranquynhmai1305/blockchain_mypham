<?php
require_once 'conn.php';

/**
 * QUẢN LÝ LOẠI SẢN PHẨM (loaisp)
 * Bảng: id_loai, tenloai, mota_loai
 */

// Lấy tất cả loại sản phẩm
function getAllLoai() {
    global $conn;
    $sql = "SELECT * FROM loaisp ORDER BY id_loai DESC";
    $result = mysqli_query($conn, $sql);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// Lấy 1 loại theo ID
function getLoaiById($id_loai) {
    global $conn;
    $sql = "SELECT * FROM loaisp WHERE id_loai = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $id_loai);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Thêm loại sản phẩm
function addLoaiSP($id_loai, $tenloai, $mota_loai) {
    global $conn;

    // 1. Kiểm tra trùng mã
    $check = mysqli_prepare($conn, "SELECT id_loai FROM loaisp WHERE id_loai = ?");
    mysqli_stmt_bind_param($check, "s", $id_loai);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        return "duplicate"; // báo trùng mã
    }

    // 2. Thêm mới
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO loaisp (id_loai, tenloai, mota_loai) VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "sss", $id_loai, $tenloai, $mota_loai);

    return mysqli_stmt_execute($stmt);
}


// Cập nhật loại sản phẩm
function updateLoaiSP($id_loai, $tenloai, $mota_loai) {
    global $conn;
    $sql = "UPDATE loaisp SET tenloai = ?, mota_loai = ? WHERE id_loai = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $tenloai, $mota_loai, $id_loai);
    return mysqli_stmt_execute($stmt);
}

// Xóa loại sản phẩm
function deleteLoaiSP($id_loai) {
    global $conn;
    $sql = "DELETE FROM loaisp WHERE id_loai = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $id_loai);
    return mysqli_stmt_execute($stmt);
}



/**
 * ============================
 * QUẢN LÝ THƯƠNG HIỆU (thuonghieu)
 * Bảng: id_thuonghieu, mota
 * ============================
 */

/* 1️ Lấy tất cả thương hiệu */
function getAllThuongHieu() {
    global $conn;
    $sql = "SELECT * FROM thuonghieu ORDER BY id_thuonghieu DESC";
    $result = mysqli_query($conn, $sql);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

/* 2️ Lấy 1 thương hiệu theo ID */
function getThuongHieuById($id_thuonghieu) {
    global $conn;
    $sql = "SELECT * FROM thuonghieu WHERE id_thuonghieu = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $id_thuonghieu);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function addThuongHieu($id, $ten, $mota, $hinh_thuonghieu) {
    global $conn;

    // 1. Kiểm tra trùng ID
    $check = $conn->prepare("SELECT id_thuonghieu FROM thuonghieu WHERE id_thuonghieu = ?");
    $check->bind_param("s", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        return "duplicate";
    }

    $stmt = $conn->prepare("INSERT INTO thuonghieu (id_thuonghieu, tenthuonghieu, mota, hinh_thuonghieu) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $id, $ten, $mota, $hinh_thuonghieu);

    return $stmt->execute();
}


/* 4️ Cập nhật thương hiệu */
function updateThuongHieu($id, $ten, $mota, $hinh_thuonghieu) {
    global $conn;
    $sql = "UPDATE thuonghieu 
            SET tenthuonghieu = ?, mota = ?, hinh_thuonghieu=?
            WHERE id_thuonghieu = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $ten, $mota, $hinh_thuonghieu, $id);
    return mysqli_stmt_execute($stmt);
}



/* 5 Xóa thương hiệu */
function deleteThuongHieu($id) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM thuonghieu WHERE id_thuonghieu = ?");
    $stmt->bind_param("s", $id);

    return $stmt->execute();
}



//phân quyền
function getAllAccountsAdmin($keyword = '') {
    global $conn;
    // Liên kết bảng users với khachhang để lấy Họ tên
    $sql = "SELECT u.*, kh.hoten 
            FROM users u 
            LEFT JOIN khachhang kh ON u.id = kh.id_users 
            WHERE 1=1";

    if ($keyword != '') {
        $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR kh.hoten LIKE ?)";
    }
    
    $sql .= " ORDER BY u.role ASC, u.id DESC";

    if ($keyword != '') {
        $stmt = mysqli_prepare($conn, $sql);
        $key = "%" . $keyword . "%";
        mysqli_stmt_bind_param($stmt, "sss", $key, $key, $key);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    } else {
        return mysqli_query($conn, $sql);
    }
}



//user
function getAllUsers($keyword = '') {
    global $conn;

    // THÊM kh.diachi vào sau kh.sdt
    $sql = "SELECT u.id AS user_id, kh.makh, kh.hoten, kh.email, kh.sdt, kh.diachi, u.trangthai, u.role,
                   COUNT(d.madh) AS so_don, 
                   IFNULL(SUM(d.tongtien), 0) AS tong_chi 
            FROM users u
            LEFT JOIN khachhang kh ON u.id = kh.id_users
            LEFT JOIN donhang d ON kh.makh = d.makh
            WHERE 1=1 "; 

    if ($keyword != '') {
        $sql .= " AND (kh.hoten LIKE ? OR kh.email LIKE ? OR kh.sdt LIKE ?) ";
    }

    $sql .= " GROUP BY u.id ORDER BY u.id DESC";

    if ($keyword != '') {
        $stmt = mysqli_prepare($conn, $sql);
        $key = "%" . $keyword . "%";
        mysqli_stmt_bind_param($stmt, "sss", $key, $key, $key);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }
    
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

// Thống kê nhanh cho các khối phía trên
function getUserStats() {
    global $conn;
    // Tổng số khách hàng (role=user)
    $q_total = mysqli_query($conn, "SELECT COUNT(id) as total FROM users WHERE role='user'");
    $total = mysqli_fetch_assoc($q_total)['total'];

    // Khách hàng chi tiêu nhiều nhất (Top 1)
    $q_top = mysqli_query($conn, "
        SELECT kh.hoten
        FROM khachhang kh
        JOIN donhang d ON kh.makh = d.makh
        GROUP BY kh.makh
        ORDER BY SUM(d.tongtien) DESC
        LIMIT 1
        ");
    $top_name = ($row = mysqli_fetch_assoc($q_top)) ? $row['hoten'] : "Chưa có dữ liệu";

    return ['total' => $total, 'top' => $top_name];
}


//SẢN PHẨM 
function getAllSanPham($keyword = '', $loai = '', $thuonghieu = '')
{
    global $conn;

    $sql = "
        SELECT 
            sp.masp,
            sp.tensp,
            sp.gia,
            sp.giakhuyenmai,
            sp.soluong,
            sp.xuatxu,
            sp.dungtichsp,
            sp.han_sudung,
            sp.ngaythemsp,
            sp.trangthai,
            l.tenloai,
            th.tenthuonghieu
        FROM sanpham sp
        JOIN loaisp l ON sp.id_loaisp = l.id_loai
        JOIN thuonghieu th ON sp.id_thuonghieu = th.id_thuonghieu
        WHERE 1
    ";

    if ($keyword != '') {
        $keyword = mysqli_real_escape_string($conn, $keyword);
        $sql .= " AND (
            sp.tensp LIKE '%$keyword%' OR
            sp.masp LIKE '%$keyword%' OR
            l.tenloai LIKE '%$keyword%' OR
            th.tenthuonghieu LIKE '%$keyword%'
        )";
    }

    if ($loai != '') {
        $loai = mysqli_real_escape_string($conn, $loai);
        $sql .= " AND sp.id_loaisp = '$loai'";
    }

    if ($thuonghieu != '') {
        $thuonghieu = mysqli_real_escape_string($conn, $thuonghieu);
        $sql .= " AND sp.id_thuonghieu = '$thuonghieu'";
    }

    $sql .= " ORDER BY sp.ngaythemsp DESC";

    return mysqli_query($conn, $sql);
}


function getSanPhamById($masp) {
    global $conn;

    $sql = "
        SELECT 
            sp.*,
            th.tenthuonghieu,
            l.tenloai,
            ha.duongdan AS hinh_sanpham
        FROM sanpham sp
        LEFT JOIN thuonghieu th 
            ON sp.id_thuonghieu = th.id_thuonghieu
        LEFT JOIN loaisp l 
            ON sp.id_loaisp = l.id_loai
        LEFT JOIN hinhanh_sanpham ha
            ON sp.masp = ha.masp
        WHERE sp.masp = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $masp);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}


//thêm sp 
function addSanPham($data) {
    global $conn;

    $check = mysqli_prepare($conn, "SELECT masp FROM sanpham WHERE masp = ?");
    mysqli_stmt_bind_param($check, "s", $data['masp']);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        return "duplicate";
    }

    $sql = "INSERT INTO sanpham 
    (masp, id_loaisp, tensp, gia, giakhuyenmai, mota, soluong,
    id_thuonghieu, xuatxu, dungtichsp, han_sudung, ngaythemsp)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sssddsissss",
        $data['masp'],          // s
        $data['id_loaisp'],     // s
        $data['tensp'],         // s
        $data['gia'],           // d
        $data['giakhuyenmai'],  // d
        $data['mota'],          // s  
        $data['soluong'],       // i
        $data['id_thuonghieu'], // s
        $data['xuatxu'],        // s
        $data['dungtichsp'],    // s
        $data['han_sudung']     // s
    );

    return mysqli_stmt_execute($stmt);
}

//cập nhật sp
function updateSanPham($masp, $data) {
    global $conn;

    $sql = "UPDATE sanpham SET
            id_loaisp = ?,
            tensp = ?,
            gia = ?,
            giakhuyenmai = ?,
            mota = ?,
            soluong = ?,
            id_thuonghieu = ?,
            xuatxu = ?,
            dungtichsp = ?,
            han_sudung = ?
            WHERE masp = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssddsisssss",
        $data['id_loaisp'],
        $data['tensp'],
        $data['gia'],
        $data['giakhuyenmai'],
        $data['mota'],          
        $data['soluong'],
        $data['id_thuonghieu'],
        $data['xuatxu'],
        $data['dungtichsp'],
        $data['han_sudung'],
        $masp
    );

    return mysqli_stmt_execute($stmt);
}

//xóa sp
function deleteSanPham($masp) {
    global $conn;

    // 1. Xóa ảnh vật lý trong thư mục uploads (nếu có)
    $sql_img = "SELECT duongdan FROM hinhanh_sanpham WHERE masp = ?";
    $stmt = mysqli_prepare($conn, $sql_img);
    mysqli_stmt_bind_param($stmt, "s", $masp);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/images/' . $row['duongdan'];
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // 2. XÓA ẢNH TRONG DB (BẢNG CON)
    $sql_del_img = "DELETE FROM hinhanh_sanpham WHERE masp = ?";
    $stmt = mysqli_prepare($conn, $sql_del_img);
    mysqli_stmt_bind_param($stmt, "s", $masp);
    mysqli_stmt_execute($stmt);

    // 3. XÓA SẢN PHẨM (BẢNG CHA)
    $sql_del_sp = "DELETE FROM sanpham WHERE masp = ?";
    $stmt = mysqli_prepare($conn, $sql_del_sp);
    mysqli_stmt_bind_param($stmt, "s", $masp);
    return mysqli_stmt_execute($stmt);
}


//lưu ảnh sp
function addHinhAnhSanPham($masp, $path) {
    global $conn;

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO hinhanh_sanpham (masp, duongdan) VALUES (?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "ss", $masp, $path);
    return mysqli_stmt_execute($stmt);
}

//hàm lấy ảnh 
function getHinhAnhBySanPham($masp) {
    global $conn;
    $sql = "SELECT id_hinhanh, duongdan, thutu 
            FROM hinhanh_sanpham 
            WHERE masp = ?
            ORDER BY thutu ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $masp);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}


//THỐNG KÊ 
// Tổng số sản phẩm
function countSanPham() {
    global $conn;
    $rs = mysqli_query($conn, "SELECT COUNT(*) AS total FROM sanpham");
    return mysqli_fetch_assoc($rs)['total'];
}

// Tổng doanh thu
function totalDoanhThu() {
    global $conn;
    $rs = mysqli_query($conn,
        "SELECT SUM(tongtien) AS total 
         FROM donhang 
         WHERE trangthai = 1"
    );
    return mysqli_fetch_assoc($rs)['total'] ?? 0;
}

// Doanh thu theo tháng trong năm hiện tại
function doanhThuTheoThang() {
    global $conn;
    $year = date('Y');

    $sql = "
        SELECT 
            MONTH(ngaydat) AS thang,
            SUM(tongtien) AS doanhthu
        FROM donhang
        WHERE trangthai = 1
        AND YEAR(ngaydat) = $year
        GROUP BY MONTH(ngaydat)
        ORDER BY thang
    ";

    $rs = mysqli_query($conn, $sql);

    $data = array_fill(1, 12, 0); // mặc định 12 tháng = 0
    while ($row = mysqli_fetch_assoc($rs)) {
        $data[$row['thang']] = $row['doanhthu'];
    }
    return $data;
}


function getThongKeThuongHieu() {
    global $conn;
    $sql = "SELECT th.tenthuonghieu, SUM(IFNULL(ct.soluong, 0)) as tong_ban
            FROM thuonghieu th
            LEFT JOIN sanpham sp ON th.id_thuonghieu = sp.id_thuonghieu
            LEFT JOIN chitiet_donhang ct ON sp.masp = ct.masp
            GROUP BY th.id_thuonghieu, th.tenthuonghieu
            ORDER BY tong_ban DESC";
    $result = mysqli_query($conn, $sql);
    
    $labels = [];
    $counts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['tenthuonghieu'];
        $counts[] = (int)$row['tong_ban'];
    }
    
    return ['labels' => $labels, 'counts' => $counts];
}

// 3. Thống kê Doanh thu và Số đơn theo 12 tháng (Dùng cho biểu đồ đường)
function getThongKeDoanhThu12Thang() {
    global $conn;
    $doanhThu = array_fill(0, 12, 0); 
    $soDonHang = array_fill(0, 12, 0);

    $year = date('Y'); // Năm hiện tại

    // Đã sửa: id_donhang -> madh theo bảng của bạn
    $sql = "SELECT MONTH(ngaydat) as thang, 
                   SUM(tongtien) as tong_dt, 
                   COUNT(madh) as tong_don 
            FROM donhang 
            WHERE YEAR(ngaydat) = '$year'
            GROUP BY MONTH(ngaydat)";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die("Lỗi SQL: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $index = (int)$row['thang'] - 1; // Tháng 1 ứng với mảng index 0
        $doanhThu[$index] = (float)$row['tong_dt'];
        $soDonHang[$index] = (int)$row['tong_don'];
    }

    return ['doanhthu' => $doanhThu, 'sodon' => $soDonHang];
}



//ĐƠN HÀNG
function getAllDonHang() {
    global $conn;
    $sql = "
        SELECT dh.*, kh.hoten, kh.sdt
        FROM donhang dh
        JOIN khachhang kh ON dh.makh = kh.makh
        ORDER BY dh.ngaydat DESC
    ";
    return mysqli_query($conn, $sql);
}

//lọc đơn 
function getAllDonHangFilter($keyword = '', $trangthai = '') {
    global $conn;

    $sql = "
        SELECT dh.*, kh.hoten, kh.sdt
        FROM donhang dh
        JOIN khachhang kh ON dh.makh = kh.makh
        WHERE 1
    ";

    if ($keyword != '') {
        $sql .= " AND (dh.madh LIKE '%$keyword%' 
                    OR kh.hoten LIKE '%$keyword%')";
    }

    if ($trangthai !== '') {
        $sql .= " AND dh.trangthai = '$trangthai'";
    }

    $sql .= " ORDER BY dh.ngaydat DESC";

    return mysqli_query($conn, $sql);
}

function getTrangThaiDonHang($tt) {
    switch ($tt) {
        case 1: return 'Chờ xác nhận';
        case 2: return 'Đang giao';
        case 3: return 'Hoàn thành';
        default: return 'Đã hủy';
    }
}


function updateTrangThaiDonHang($id, $trangthai) {
    global $conn;
    $stmt = $conn->prepare("UPDATE donhang SET trangthai = ? WHERE madh = ?");
    $stmt->bind_param("ii", $trangthai, $id);
    return $stmt->execute();
}

function getDonHangById($id) {
    global $conn;
    $sql = "
        SELECT dh.*, kh.hoten, kh.sdt
        FROM donhang dh
        JOIN khachhang kh ON dh.makh = kh.makh
        WHERE dh.madh = '$id'
    ";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

function getChiTietDonHang($id) {
    global $conn;
    $sql = "
        SELECT ct.*, sp.tensp
        FROM chitiet_donhang ct
        JOIN sanpham sp ON ct.masp = sp.masp
        WHERE ct.madh = '$id'
    ";
    return mysqli_query($conn, $sql);
}


function getDonHangByKeyword($keyword, $status = '') {
    global $conn;

    $sql = "
        SELECT d.*, k.hoten, k.sdt, k.email
        FROM donhang d
        JOIN khachhang k ON d.makh = k.makh
        WHERE (
            d.madh LIKE ?
            OR k.hoten LIKE ?
            OR k.sdt LIKE ?
            OR k.email LIKE ?
        )
    ";

    if ($status !== '') {
        $sql .= " AND d.trangthai = ?";
    }

    $stmt = mysqli_prepare($conn, $sql);
    $kw = "%$keyword%";

    if ($status !== '') {
        mysqli_stmt_bind_param($stmt, "ssssi", $kw, $kw, $kw, $kw, $status);
    } else {
        mysqli_stmt_bind_param($stmt, "ssss", $kw, $kw, $kw, $kw);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function getOrderDetail($madh) {
    global $conn;

    // 1. Lấy thông tin đơn hàng
    $sql_order = "SELECT * FROM donhang WHERE madh = '$madh'";
    $query_order = mysqli_query($conn, $sql_order);
    $order_info = mysqli_fetch_assoc($query_order);

    if (!$order_info) return null;

    // 2. Lấy danh sách sản phẩm
    $sql_items = "SELECT ct.*, sp.tensp, sp.masp
                  FROM chitiet_donhang ct
                  JOIN sanpham sp ON ct.masp = sp.masp
                  WHERE ct.madh = '$madh'";

    $query_items = mysqli_query($conn, $sql_items);

    $items = [];
    while ($row = mysqli_fetch_assoc($query_items)) {
        $items[] = $row;
    }

    return [
        'info' => $order_info,
        'items' => $items
    ];
}

function updateAnhDaiDienSanPham($masp, $path) {
    global $conn;

    // kiểm tra sản phẩm đã có ảnh chưa
    $check = $conn->prepare("SELECT id_hinhanh FROM hinhanh_sanpham WHERE masp=? LIMIT 1");
    $check->bind_param("s", $masp);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // update ảnh cũ
        $stmt = $conn->prepare("UPDATE hinhanh_sanpham SET duongdan=? WHERE masp=? LIMIT 1");
        $stmt->bind_param("ss", $path, $masp);
    } else {
        // thêm ảnh mới
        $stmt = $conn->prepare("INSERT INTO hinhanh_sanpham (masp, duongdan, thutu) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $masp, $path);
    }

    return $stmt->execute();
}

function logActivity($conn, $admin_id, $action, $module, $description) {
    // Lấy địa chỉ IP của người dùng
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if ($ip_address === '::1') {
        $ip_address = '127.0.0.1';
    }

    $sql = "INSERT INTO activity_logs (admin_id, action, module, description, ip_address) 
            VALUES (?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $admin_id, $action, $module, $description, $ip_address);
    $stmt->execute();
}

function save_log($conn, $admin_id, $module, $action, $description) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, module, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issss", $admin_id, $module, $action, $description, $ip);
    $stmt->execute();
    $stmt->close();
}


// Đếm tổng số khách hàng
function countKhachHang() {
    global $conn;
    $sql = "SELECT COUNT(*) AS total FROM khachhang";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}

// Đếm số đơn hàng đang chờ xử lý (trangthai = 1 tương ứng với 'Chờ xác nhận')
function countDonHangChoXuLy() {
    global $conn;
    $sql = "SELECT COUNT(*) AS total FROM donhang WHERE trangthai = 1";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}