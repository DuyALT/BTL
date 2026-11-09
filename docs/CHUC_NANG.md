# Mô tả chức năng và vị trí mã nguồn

| Nhóm theo yêu cầu | Chức năng | Trang / thành phần |
|---|---|---|
| 1–3 | Đăng nhập, đăng xuất, phiên, khóa tài khoản, phân quyền, đổi/reset mật khẩu, CRUD tài khoản | `login.php`, `logout.php`, `doi_mat_khau.php`, `admin/users.php`, `includes/auth.php` |
| 4 | Hồ sơ SV: mã, họ tên, ngày sinh, giới tính, lớp, khóa, email, trạng thái; tìm/lọc và bảng điểm | `admin/sinhvien.php`, `sinhvien/tong_quan.php` |
| 5 | CRUD lớp hành chính, tìm mã/tên, sĩ số và liên kết danh sách sinh viên | `admin/lop.php` |
| 6 | CRUD giảng viên, tìm kiếm, tài khoản tự tạo, xem học phần được phân công | `admin/giangvien.php`, `admin/lophocphan.php?gv=...` |
| 7–9 | CRUD môn, học phần, phân công giảng viên, năm học, học kỳ và trạng thái | `admin/monhoc.php`, `admin/lophocphan.php`, `admin/namhoc.php`, `admin/hocky.php` |
| 10 | Thêm từng SV/cả lớp, chống trùng và vượt sĩ số; xóa khi chưa chốt | `admin/lhp_sinhvien.php` |
| 11–14 | Ba nhóm điểm, nhiều bài TX, kiểm tra 0–10, tính hai chữ số, điều kiện đạt kép | `includes/functions.php`, `giangvien/nhap_diem.php` |
| 15 | Lưu nháp, xem trước, xác nhận lưu chính thức; khóa giảng viên phía server; kiểm tra đủ điểm | `includes/grade-service.php` |
| 16–17 | Admin sửa chính thức có lý do, transaction, nhật ký trước/sau từng phần và toàn bộ danh sách TX | `includes/grade-service.php`, `giangvien/lich_su.php` |
| 18–20 | Tra cứu mã/tên SV, lớp, môn, học phần, học kỳ, năm, kết quả; sắp mã/tên/điểm; phân trang | `tra_cuu.php`, `sinhvien/bang_diem.php` |
| 21 | In cá nhân, theo học phần, tổng hợp lớp; CSV mở bằng Excel; giới hạn theo quyền | `giangvien/in_bang_diem.php`, `tra_cuu.php`, `includes/csv.php` |
| 22–24 | TB, cao/thấp nhất, đạt/trượt, tỷ lệ; theo học phần/môn/lớp/năm/kỳ; biểu đồ cột, tròn, phân bố; in/CSV | `thongke.php`, `assets/js/thongke.js` |
| 25 | Dashboard Admin/GV, tiến độ/chốt, biểu đồ, liên kết nhanh; dashboard SV/hồ sơ/học phần | `index.php`, `sinhvien/tong_quan.php` |
| 26 | Tiếng Việt, logo học viện, responsive, tìm/lọc, phân trang, flash, xác nhận | `includes/header.php`, `assets/css/style.css`, `includes/pagination.php` |
| 27–28 | password_hash/verify, Session, quyền mỗi request, CSRF, escape HTML, prepared statements, khóa ngoại, transaction | `includes/auth.php`, `includes/grade-service.php`, bộ SQL |
| 29 | Admin sao lưu toàn bộ JSON, kiểm tra tệp và khôi phục transaction, bản trước khôi phục được bảo vệ | `admin/saoluu.php`, `includes/backup.php`, `database/.htaccess` |

Quy tắc xóa: lớp hành chính có sinh viên, môn/học kỳ có học phần, học phần có sinh viên, sinh viên có đăng ký không được xóa. Có thể khóa tài khoản hoặc cập nhật trạng thái học tập. Roster học phần đã chốt không thêm/xóa được, kể cả Admin; Admin chỉ sửa điểm kèm lý do.

Phân quyền dữ liệu: mọi truy vấn tra cứu/xuất đều bổ sung phạm vi người dùng. Giáo viên chỉ thấy lớp mình phụ trách; sinh viên chỉ thấy điểm chính thức của mình. Bản in nháp của Admin/GV được ghi rõ là bản nháp. Bảng tổng hợp lớp và thống kê chỉ lấy điểm chính thức.

Lịch sử trước nâng cấp vẫn giữ tên cột gốc trong dữ liệu. Cột `cc` hiện là trung bình TX tương thích với cấu trúc cũ; danh sách từng bài thật nằm trong `diem.tx` dạng JSON, được dùng để tính tổng kết. Các trường phần trăm cũ chỉ giữ để tương thích dữ liệu và không quyết định công thức.
