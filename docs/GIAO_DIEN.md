# Giao diện EduScore

Thiết kế lấy cảm hứng từ bố cục dashboard Sneat, tự viết bằng Bootstrap 5, CSS và JavaScript; không sử dụng mã nguồn hay tài sản thương mại của Sneat. Logo Học viện An ninh nhân dân hiện có được giữ cùng tên EduScore.

## Thành phần đã triển khai

- Bộ màu navy `#142D52`, navy đậm `#0B1F3A`, tương tác `#2563EB`, nền `#F5F7FB`, surface trắng. Giữ font Be Vietnam Pro và fallback hệ thống.
- Sidebar desktop 250px, thu gọn 78px, lưu lựa chọn trong localStorage; menu trượt trên tablet/mobile. Menu vẫn được tạo theo quyền PHP hiện có.
- Header, breadcrumb, tiêu đề và mô tả trang, menu tài khoản, hồ sơ dạng modal và liên kết đổi mật khẩu.
- Card/KPI, bảng, bộ lọc, biểu mẫu, phân trang, badge kết quả, thông báo, modal xác nhận và trạng thái trống dùng chung.
- Login có logo trên cả desktop/mobile, hiện/ẩn mật khẩu, autocomplete và trạng thái đang xử lý.
- Nền login dùng ảnh học viện do người dùng cung cấp (`assets/images/login-campus.jpg`), được thiết kế riêng trong `assets/css/login-campus.css`. Liên kết Quên mật khẩu mở hướng dẫn liên hệ Admin để xác minh/cấp lại mật khẩu theo chức năng hiện có; chưa có luồng gửi email hoặc OTP tự động.
- Bảng nhập điểm TX1…TXn, GK, CK, TK; cố định header/cột nhận diện; Tab/Enter/↑/↓; ô đang sửa, cảnh báo chưa lưu, validation và trạng thái lưu.
- Dashboard dùng dữ liệu có thật, bộ lọc học phần ngay trên trang; dashboard sinh viên có hồ sơ và biểu đồ số học phần đạt/không đạt theo học kỳ.
- Bảng điểm sinh viên có bộ lọc năm học trên dữ liệu đang xem. Tra cứu hiện có vẫn giữ bộ lọc năm/học kỳ phía máy chủ, in và CSV.
- Trang lỗi 403/404/500 có giao diện chung; lỗi không mong đợi ghi vào log máy chủ, không trả chi tiết PHP/SQL cho người dùng.
- Liên kết bỏ qua menu, nhãn input, focus bàn phím, nút sắp xếp có nhãn và aria-sort, hỗ trợ reduced motion và CSS in.

## Các tệp chính

| Nhóm | Tệp |
|---|---|
| Design system | `assets/css/eduscore.css` (nạp sau CSS cũ để giữ tương thích) |
| Tương tác chung | `assets/js/app.js`, `assets/js/validate.js` |
| Layout / modal | `includes/header.php`, `includes/footer.php`, `includes/ui-modals.php` |
| Bảng điểm Excel | `giangvien/nhap_diem.php`, `assets/js/score-grid.js` |
| Dashboard và bảng điểm | `index.php`, `sinhvien/tong_quan.php`, `sinhvien/bang_diem.php`, `assets/js/student-dashboard.js` |
| Login / danh sách SV | `login.php`, `admin/sinhvien.php` |
| Biểu đồ / trạng thái trống | `assets/js/thongke.js` |
| Trang lỗi | `includes/error-view.php`, `includes/error-handler.php`, phần hiển thị 403 trong `includes/auth.php`, `errors/404.php`, `errors/500.php`, `.htaccess` |

## Cách nhập điểm mới

Mỗi bài TX là một ô. Bấm **Thêm bài TX** để thêm cột hiển thị. Số cột ban đầu lấy từ số bài có trong dữ liệu của lớp; backend hiện không có cấu hình số bài cố định riêng cho học phần.

Ô TX cuối để trống chỉ là cột dự phòng, không được tính thành một bài. Không để trống giữa những bài đã nhập. JavaScript ghép từng bài lại thành trường `diem[id][cc]` ngăn cách bởi dấu `;`, đúng giao thức POST cũ. GK, CK, CSRF, phiên bản và `action` vẫn giữ tên cũ. Khi JavaScript không chạy, trường nhập TX gốc vẫn hiển thị để nhập chuỗi phân cách `;`.

Tab sang ô kế tiếp; Enter hoặc ↑/↓ sang cùng cột của sinh viên liền kề. Ô vừa sửa có nền vàng nhạt. Trạng thái "Có thay đổi chưa lưu" và cảnh báo rời trang giúp tránh mất nội dung chưa gửi. Các điểm xem trước vẫn được tính lại và xác thực ở máy chủ khi lưu.

Modal xác nhận kích hoạt lại nút gốc để giữ chính xác `action=finalize` hoặc thao tác CRUD tương ứng. Các nút chỉ bị vô hiệu hóa sau khi trình duyệt thu thập trường POST, tránh làm mất giá trị của nút được bấm.

## Phạm vi giữ nguyên

Không sửa `config/db.php`, `includes/functions.php`, `includes/grade-service.php`, `includes/backup.php` hoặc bộ SQL. Không chạy migration, import SQL, DROP hay TRUNCATE. Công thức, làm tròn, điều kiện đạt, transaction, phân quyền và lịch sử điểm giữ nguyên. Kiểm tra SHA-256 các hàng dữ liệu trước/sau kiểm thử xác nhận cơ sở dữ liệu hiện tại không thay đổi.

Các bảng dùng sắp xếp phía trình duyệt trên **trang đang xem**; sắp xếp toàn bộ kết quả vẫn dùng bộ chọn có sẵn trên Tra cứu. Phân trang PHP đang có được giữ nguyên. Chưa chuyển sang phân trang SQL hay API/AJAX mới vì nằm ngoài phạm vi giao diện.

Đồ thị sinh viên hiển thị số học phần đạt/không đạt từ dữ liệu dashboard hiện có; không bổ sung truy vấn điểm trung bình học kỳ hay dùng số liệu giả.

## Kiểm tra giao diện

- 48 kiểm tra trình duyệt ban đầu: login, sidebar, trang quản lý/tra cứu/báo cáo, bảng điểm ở 375/768/1024/1440px, POST TX, trạng thái chưa lưu, validation, modal xác nhận, quyền sinh viên và lỗi 403/404.
- 21 kiểm tra tương tác bổ sung: Tab/Enter, sticky header/cột, CSS in, mở/đóng menu mobile, hồ sơ, validation form sinh viên, sắp xếp bảng, bộ lọc, ghi nhớ sidebar, giao diện nhập điểm mobile, quyền dữ liệu cá nhân, lỗi 500 và đăng xuất.
- Không có runtime exception JavaScript trong các kiểm tra; đã xem ảnh chụp desktop/mobile.
- 34 kiểm tra HTTP hiện có và kiểm tra công thức vẫn đạt. Kiểm tra PHP không có lỗi cú pháp.
- Các bài thử trên giao diện không gửi yêu cầu lưu điểm. Hộp xác nhận được kiểm tra bằng cách chặn submit ngay trong trình duyệt, giữ nguyên dữ liệu thật.

Chưa thực hiện CRUD ghi/xóa, chốt điểm hoặc khôi phục trên dữ liệu thật; chưa thử máy in vật lý và ứng dụng Excel. Nghiệp vụ này giữ nguyên mã nguồn hiện có; bộ test tích hợp cũ có DROP cơ sở dữ liệu thử nên không chạy trong đợt thay giao diện theo yêu cầu an toàn mới.

Ảnh kiểm tra và script CDP cục bộ nằm tại `.tools/` (không đưa vào Git và đã chặn truy cập HTTP). Giao diện dùng CDN Bootstrap/Chart.js và font như trước; cần kết nối Internet để tải đầy đủ tài nguyên đó.

## Hoàn tác và vận hành

Mốc trước thay giao diện: commit `67fe43f`. Git portable nằm tại `.tools/git/cmd/git.exe`. Dự án chỉ tạo kho Git cục bộ, không đẩy lên dịch vụ bên ngoài.

Sau khi cập nhật, dùng Ctrl+F5. Nếu đổi đường dẫn thư mục khỏi `/BTL/`, cần điều chỉnh ErrorDocument trong `.htaccess` tương ứng với `BASE_URL`. Thư mục `.git` và `.tools` bị chặn truy cập web; thư mục `database` giữ cấu hình chặn sẵn có.
