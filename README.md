# Hệ thống quản lý điểm học tập sinh viên (Đề tài 02)

Công nghệ: HTML5, CSS3, JavaScript, Bootstrap 5, Chart.js · PHP 8 (PDO) · MySQL/MariaDB (XAMPP)

## 1. Cài đặt
1. Cài **XAMPP**, mở XAMPP Control Panel → **Start** Apache và MySQL.
2. Chép cả thư mục `BTL` vào `C:\xampp\htdocs\` → `C:\xampp\htdocs\BTL`.
3. Mở `http://localhost/phpmyadmin` → tab **Import** → chọn `database/quanlydiem.sql` → **Import**.
   (File tự tạo database `quanlydiem`, các bảng và dữ liệu mẫu.)
4. Truy cập `http://localhost/BTL/`.
5. Nếu MySQL có mật khẩu root, sửa `config/db.php` (`DB_USER`, `DB_PASS`).
6. Nếu đổi tên thư mục dự án, sửa `BASE_URL` trong `config/db.php` cho khớp (vd `/btl-quanlydiem/`).

> Máy cần có Internet để tải Bootstrap và Chart.js từ CDN.

## 2. Tài khoản mẫu (mật khẩu đều là `123456`)
| Vai trò | Tài khoản | Ghi chú |
|---|---|---|
| Quản trị viên | `admin` | Toàn quyền |
| Giảng viên | `gv001` … `gv004` | gv001 dạy 3 lớp: IT3010.20251.01/02, IT3040.20252.01 |
| Sinh viên | `2251000001` … `2251000039` | |
| Sinh viên bị khóa | `2251000040` | Dùng để test chức năng khóa tài khoản |

## 3. Cấu trúc thư mục
```
BTL/
├─ admin/                   Chỉ admin
│   ├─ users.php            Quản lý người dùng: thêm/sửa/xóa/khóa, phân quyền, reset mật khẩu
│   ├─ hocky.php            Học kỳ
│   ├─ monhoc.php           Môn học + cấu hình trọng số
│   ├─ sinhvien.php         Sinh viên (tự tạo tài khoản)
│   ├─ giangvien.php        Giảng viên (tự tạo tài khoản)
│   ├─ lophocphan.php       Lớp học phần
│   └─ lhp_sinhvien.php     Sinh viên trong lớp học phần
├─ giangvien/               Giảng viên + admin
│   ├─ nhap_diem.php        Nhập/sửa điểm, tự tính tổng kết, lưu lịch sử
│   ├─ lich_su.php          Lịch sử chỉnh sửa điểm
│   └─ in_bang_diem.php     In bảng điểm theo lớp học phần / lớp sinh hoạt
├─ sinhvien/
│   └─ bang_diem.php        Tra cứu bảng điểm cá nhân
├─ includes/
│   ├─ auth.php             Phiên đăng nhập, phân quyền, CSRF, thông báo
│   ├─ functions.php        Tính điểm, xét đạt, quy đổi điểm chữ, hàm tiện ích
│   └─ header.php, footer.php
├─ config/db.php            Kết nối CSDL (PDO) + BASE_URL
├─ assets/css/style.css     Giao diện + định dạng trang in
├─ assets/js/validate.js    Kiểm tra dữ liệu phía trình duyệt
├─ assets/js/thongke.js     Vẽ biểu đồ thống kê (Chart.js)
├─ database/quanlydiem.sql  Tạo CSDL + dữ liệu mẫu
├─ docs/                    Tài liệu nộp kèm (ERD, Use Case, kiểm thử...)
├─ index.php                Dashboard
├─ thongke.php              Thống kê + biểu đồ (admin, giảng viên)
├─ doi_mat_khau.php         Đổi mật khẩu (mọi vai trò)
└─ login.php, logout.php
```

## 4. Phân quyền
| Chức năng | Admin | Giảng viên | Sinh viên |
|---|:-:|:-:|:-:|
| Quản lý người dùng, danh mục | ✔ | | |
| Nhập / sửa điểm | ✔ mọi lớp | ✔ chỉ lớp mình dạy | |
| Lịch sử sửa điểm, thống kê, in | ✔ | ✔ lớp mình | |
| Xem bảng điểm cá nhân | ✔ (mọi SV) | | ✔ của mình |

## 5. Quy tắc tính điểm
- Điểm thành phần: Chuyên cần (CC), Kiểm tra (KT), Thực hành (TH), Thi kết thúc học phần.
- **Tổng kết = (CC×%CC + KT×%KT + TH×%TH + Thi×%Thi) / 100**, làm tròn 1 chữ số.
  Trọng số cấu hình riêng cho từng môn (tổng = 100%, môn không có thực hành thì đặt 0%).
- **Đạt** khi tổng kết ≥ điểm đạt của môn (mặc định 4.0); chưa đủ điểm thành phần thì chưa xét.
- Đổi trọng số → hệ thống tự tính lại điểm tổng kết của toàn bộ sinh viên học môn đó.
- Quy đổi: A ≥ 8.5 · B+ ≥ 8.0 · B ≥ 7.0 · C+ ≥ 6.5 · C ≥ 5.5 · D+ ≥ 5.0 · D ≥ 4.0 · F < 4.0.

## 6. Bảo mật đã áp dụng
- Mật khẩu băm bằng `password_hash()` / `password_verify()` (bcrypt).
- Toàn bộ truy vấn dùng **PDO Prepared Statements** → chống SQL Injection.
- `htmlspecialchars()` khi in dữ liệu → chống XSS.
- Token **CSRF** cho mọi form POST.
- `session_regenerate_id()` khi đăng nhập, tự hết phiên sau 30 phút, kiểm tra trạng thái tài khoản ở mọi request
  (khóa tài khoản → người đó bị đăng xuất ngay).
- Kiểm tra quyền ở phía server cho từng trang và từng lớp học phần (giảng viên không sửa được lớp khác dù đổi URL/ID).
- Giới hạn đăng nhập sai: sai 5 lần phải chờ 60 giây.
- Ràng buộc CSDL: khóa ngoại, `UNIQUE`, `CHECK` điểm 0–10, tổng trọng số = 100.
