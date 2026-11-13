# Website quản lý điểm học tập sinh viên

PHP 8 / PDO / MySQL trên XAMPP, HTML/CSS/JavaScript, Bootstrap và Chart.js.

Ba nhóm điểm cố định: thường xuyên (nhiều bài, mỗi bài hệ số 1), giữa HP (một bài, hệ số 2), cuối HP (một bài, hệ số 3). Tổng kết làm tròn hai chữ số. Đạt khi tổng kết >= 5 **và** cuối HP >= 5.

Giảng viên lưu nháp rồi xác nhận lưu chính thức để khóa điểm. Admin sửa chính thức phải có lý do. Sinh viên chỉ xem điểm đã công bố của mình. Có quản lý danh mục/lớp/năm học, tra cứu, phân trang, CSV/in, thống kê và sao lưu/khôi phục.

## Chạy website

Bật Apache/MySQL trong XAMPP, mở http://localhost/BTL-GitHub/.

- **Cài mới:** import `database/schema.sql` rồi `database/sample_data.sql`. SQL cài mới xóa các bảng cũ cùng tên.
- **Nâng cấp giữ dữ liệu:** chạy `C:\xampp\php\php.exe database/upgrade_features.php`.
- Sao chép `config/db.example.php` thành `config/db.php`, rồi chỉnh kết nối MySQL và `BASE_URL`. File `config/db.php` được Git bỏ qua.
- Tài khoản mẫu: `admin`, `gv001`...`gv004`, `2251000001`...`2251000040`; mật khẩu `123456`. Lần đăng nhập đầu sẽ bị yêu cầu đổi mật khẩu (tối thiểu 8 ký tự, có chữ và số).
- Quên mật khẩu hoặc bị tạm khóa do nhập sai nhiều lần: `C:\xampp\php\php.exe database/dat_lai_mat_khau.php <tên đăng nhập>` (đặt lại `123456`), hoặc thêm `--chi-mo-khoa` để chỉ mở khóa.
- Điểm cũ sau nâng cấp ở trạng thái nháp cho đến khi được kiểm tra và lưu chính thức.

## Tài liệu bàn giao

- [Yêu cầu gốc](docs/YEU_CAU.md)
- [Chức năng và mã nguồn](docs/CHUC_NANG.md)
- [Phân tích, ERD, sơ đồ chức năng](docs/THIET_KE.md)
- [Cài đặt và hướng dẫn ba vai trò](docs/HUONG_DAN.md)
- [Báo cáo kiểm thử](docs/KIEM_THU.md)
- [Mẫu phân chia nhiệm vụ nhóm](docs/PHAN_CONG.md)
- [Giao diện EduScore: cách dùng, tệp đã đổi và kiểm thử](docs/GIAO_DIEN.md)

## Kiểm tra

```powershell
C:\xampp\php\php.exe database/test_grading.php
C:\xampp\php\php.exe database/test_features.php
powershell -ExecutionPolicy Bypass -File database/test_http.ps1
```

Kiểm thử tích hợp dùng database thử riêng và tự xóa sau khi chạy. Kiểm thử HTTP cần Apache/MySQL và các tài khoản mẫu (truyền `-BaseUrl` nếu thư mục khác `BTL-GitHub`); không thay đổi điểm, tạm đổi mật khẩu 3 tài khoản mẫu rồi tự trả về mặc định. Thư mục `database/` bị chặn truy cập web; chạy các script PHP bằng CLI.

tai khoan test:
tk: admin
mk: HoNgocDuy01

tk: gv001
mk: NguyenVanHung01

tk:250001
mk: DangMinhHuong01