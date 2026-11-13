# DANH SÁCH CHỨC NĂNG WEBSITE QUẢN LÝ ĐIỂM HỌC TẬP SINH VIÊN

## I. QUẢN LÝ TÀI KHOẢN VÀ PHÂN QUYỀN

### 1. Đăng nhập, đăng xuất
- Đăng nhập bằng tên tài khoản và mật khẩu.
- Mật khẩu được mã hóa an toàn trong cơ sở dữ liệu.
- Kiểm tra thông tin đăng nhập.
- Thông báo khi nhập sai tài khoản hoặc mật khẩu.
- Kiểm tra trạng thái tài khoản đang hoạt động hay bị khóa.
- Duy trì phiên đăng nhập.
- Đăng xuất và hủy phiên đăng nhập.
- Ngăn người chưa đăng nhập truy cập các trang yêu cầu xác thực.

### 2. Phân quyền người dùng
Hệ thống có 3 vai trò:

**Quản trị viên (Admin):**
- Quản lý toàn bộ hệ thống.
- Quản lý tài khoản giảng viên và sinh viên.
- Quản lý danh sách sinh viên, lớp học, môn học, học phần.
- Phân công giảng viên giảng dạy.
- Xem toàn bộ điểm của sinh viên.
- Chỉnh sửa điểm đã được giảng viên lưu chính thức.
- Xem lịch sử chỉnh sửa điểm.
- Xem thống kê và xuất báo cáo.

**Giảng viên:**
- Xem danh sách lớp và học phần được phân công.
- Xem danh sách sinh viên trong lớp học phần.
- Nhập điểm cho sinh viên.
- Chỉnh sửa điểm khi chưa lưu chính thức.
- Xác nhận và lưu điểm chính thức.
- Xem điểm đã lưu nhưng không được tự ý sửa.
- Xem thống kê kết quả học tập của lớp được phân công.

**Sinh viên:**
- Đăng nhập vào tài khoản cá nhân.
- Xem thông tin cá nhân.
- Xem danh sách học phần.
- Xem điểm thành phần và điểm tổng kết.
- Xem kết quả đạt hoặc không đạt.
- Xem bảng điểm theo học kỳ và năm học.
- Tìm kiếm và lọc kết quả học tập.
- Không được chỉnh sửa điểm.

### 3. Quản lý tài khoản
- Thêm tài khoản mới.
- Xem danh sách tài khoản.
- Cập nhật thông tin tài khoản.
- Khóa hoặc mở khóa tài khoản.
- Xóa hoặc vô hiệu hóa tài khoản theo quyền hạn.
- Phân quyền tài khoản.
- Đổi mật khẩu.
- Tìm kiếm tài khoản theo tên, mã sinh viên hoặc vai trò.

## II. QUẢN LÝ SINH VIÊN VÀ LỚP HỌC

### 4. Quản lý thông tin sinh viên
- Thêm sinh viên.
- Xem danh sách sinh viên.
- Sửa thông tin sinh viên.
- Xóa sinh viên theo quyền hạn.
- Tìm kiếm sinh viên theo mã sinh viên hoặc họ tên.
- Lọc sinh viên theo lớp, khóa học.
- Xem thông tin chi tiết và kết quả học tập của từng sinh viên.

Thông tin sinh viên bao gồm:
- Mã sinh viên.
- Họ và tên.
- Ngày sinh.
- Giới tính.
- Lớp hành chính.
- Khóa học.
- Email.
- Trạng thái học tập.

### 5. Quản lý lớp hành chính
- Thêm lớp.
- Sửa thông tin lớp.
- Xóa lớp khi đủ điều kiện.
- Xem danh sách lớp.
- Xem danh sách sinh viên thuộc từng lớp.
- Tìm kiếm lớp theo mã lớp hoặc tên lớp.
- Thống kê số lượng sinh viên trong lớp.

### 6. Quản lý giảng viên
- Thêm giảng viên.
- Cập nhật thông tin giảng viên.
- Xem danh sách giảng viên.
- Tìm kiếm giảng viên.
- Phân công giảng viên phụ trách học phần.
- Xem danh sách học phần giảng viên đang giảng dạy.

## III. QUẢN LÝ MÔN HỌC, HỌC PHẦN VÀ NĂM HỌC

### 7. Quản lý danh mục môn học
- Thêm môn học.
- Sửa thông tin môn học.
- Xóa môn học khi đủ điều kiện.
- Xem danh sách môn học.
- Tìm kiếm môn học.
- Quản lý mã môn học.
- Quản lý tên môn học.
- Quản lý số tín chỉ.

### 8. Quản lý học phần
- Tạo học phần từ danh mục môn học.
- Gán học phần vào học kỳ và năm học.
- Phân công giảng viên phụ trách.
- Quản lý danh sách sinh viên của học phần.
- Xem thông tin học phần.
- Cập nhật thông tin học phần.
- Tìm kiếm học phần theo tên hoặc mã.

### 9. Quản lý học kỳ và năm học
- Thêm năm học.
- Thêm học kỳ.
- Cập nhật thông tin học kỳ.
- Quản lý trạng thái học kỳ.
- Xem danh sách học phần theo học kỳ.
- Lọc kết quả học tập theo năm học và học kỳ.

### 10. Quản lý danh sách sinh viên theo học phần
- Thêm sinh viên vào học phần.
- Xem danh sách sinh viên của học phần.
- Xóa sinh viên khỏi học phần khi chưa phát sinh dữ liệu điểm chính thức.
- Kiểm tra tránh đăng ký trùng học phần.
- Tìm kiếm sinh viên trong học phần.

## IV. QUẢN LÝ ĐIỂM HỌC TẬP

### 11. Nhập điểm thành phần
Mỗi học phần có đúng 3 nhóm điểm:

**Điểm thường xuyên:**
- Có thể có một hoặc nhiều bài kiểm tra thường xuyên.
- Giảng viên nhập điểm từng bài kiểm tra.
- Mỗi bài kiểm tra thường xuyên có hệ số 1.

**Điểm giữa học phần:**
- Chỉ có duy nhất 1 bài kiểm tra.
- Hệ số 2.

**Điểm cuối học phần:**
- Chỉ có duy nhất 1 bài kiểm tra.
- Hệ số 3.

### 12. Kiểm tra tính hợp lệ của điểm
- Điểm phải nằm trong khoảng từ 0 đến 10.
- Cho phép nhập điểm thập phân.
- Không cho phép nhập chữ hoặc ký tự không hợp lệ.
- Không cho phép nhập điểm âm.
- Không cho phép nhập điểm lớn hơn 10.
- Hiển thị thông báo khi dữ liệu không hợp lệ.
- Kiểm tra dữ liệu trước khi lưu vào cơ sở dữ liệu.
- Không tự động coi điểm chưa nhập là 0.

### 13. Tự động tính điểm tổng kết học phần
Công thức:

Điểm tổng kết = (Tổng điểm thường xuyên + Điểm giữa học phần × 2 + Điểm cuối học phần × 3) / (Số bài kiểm tra thường xuyên + 5)

Ví dụ có 2 bài kiểm tra thường xuyên:

Điểm tổng kết = (TX1 + TX2 + GK × 2 + CK × 3) / 7

Yêu cầu:
- Tự động tính điểm tổng kết.
- Làm tròn đến 2 chữ số thập phân.
- Tính lại khi điểm thành phần thay đổi trước lúc lưu chính thức.
- Hiển thị điểm tổng kết cho giảng viên và sinh viên theo quyền truy cập.
- Chỉ tính điểm chính thức khi đã có đủ điểm thành phần bắt buộc.

### 14. Tự động xác định kết quả đạt hoặc không đạt
Sinh viên được xác định **KHÔNG ĐẠT** nếu thuộc một trong hai trường hợp:

- Điểm tổng kết học phần dưới 5.
- Điểm cuối học phần dưới 5.

Sinh viên chỉ được xác định **ĐẠT** khi đồng thời:
- Điểm tổng kết học phần từ 5 trở lên.
- Điểm cuối học phần từ 5 trở lên.

Hệ thống tự động hiển thị trạng thái ĐẠT hoặc KHÔNG ĐẠT.

### 15. Lưu và khóa điểm chính thức
- Giảng viên được nhập và chỉnh sửa điểm ở trạng thái bản nháp.
- Giảng viên có thể xem trước điểm tổng kết.
- Khi giảng viên nhấn "Lưu điểm chính thức", hệ thống yêu cầu xác nhận.
- Sau khi lưu chính thức, điểm tự động bị khóa.
- Giảng viên không thể chỉnh sửa hoặc xóa điểm đã khóa.
- Chỉ Admin có quyền chỉnh sửa điểm chính thức.
- Hệ thống kiểm tra quyền ở phía máy chủ, không chỉ khóa nút trên giao diện.
- Không cho phép lưu chính thức khi dữ liệu điểm chưa hợp lệ.

### 16. Chỉnh sửa điểm bởi Admin
- Admin có quyền mở chức năng chỉnh sửa điểm đã khóa.
- Admin phải nhập lý do chỉnh sửa.
- Hệ thống lưu điểm cũ và điểm mới.
- Lưu người thực hiện chỉnh sửa.
- Lưu ngày giờ chỉnh sửa.
- Tự động tính lại điểm tổng kết và kết quả đạt/không đạt.
- Giảng viên chỉ được xem điểm sau chỉnh sửa.

### 17. Lịch sử chỉnh sửa điểm
- Lưu lại toàn bộ lần chỉnh sửa điểm chính thức.
- Hiển thị mã sinh viên.
- Hiển thị học phần.
- Hiển thị thành phần điểm được sửa.
- Hiển thị điểm cũ và điểm mới.
- Hiển thị thời gian chỉnh sửa.
- Hiển thị tài khoản thực hiện.
- Hiển thị lý do chỉnh sửa.
- Cho phép Admin tra cứu lịch sử.

## V. TRA CỨU VÀ XEM BẢNG ĐIỂM

### 18. Tra cứu điểm sinh viên
- Tìm kiếm theo mã sinh viên.
- Tìm kiếm theo họ tên.
- Lọc theo lớp.
- Lọc theo môn học hoặc học phần.
- Lọc theo học kỳ.
- Lọc theo năm học.
- Lọc theo kết quả đạt hoặc không đạt.
- Hiển thị bảng điểm chi tiết.

### 19. Bảng điểm cá nhân
Sinh viên xem được:
- Mã học phần.
- Tên học phần.
- Số tín chỉ.
- Điểm thường xuyên.
- Điểm giữa học phần.
- Điểm cuối học phần.
- Điểm tổng kết.
- Kết quả đạt hoặc không đạt.
- Học kỳ và năm học.

Sinh viên chỉ được xem bảng điểm của chính mình.

### 20. Bảng điểm theo lớp và học phần
- Giảng viên xem bảng điểm học phần được phân công.
- Admin xem bảng điểm của tất cả các lớp và học phần.
- Hiển thị danh sách sinh viên.
- Hiển thị các điểm thành phần.
- Hiển thị điểm tổng kết.
- Hiển thị kết quả đạt/không đạt.
- Sắp xếp theo mã sinh viên, họ tên hoặc điểm tổng kết.

### 21. In và xuất bảng điểm
- In bảng điểm cá nhân.
- In bảng điểm theo lớp.
- In bảng điểm theo học phần.
- Xuất bảng điểm Excel hoặc CSV.
- Tạo bản in có tiêu đề, thông tin lớp, học phần và bảng điểm rõ ràng.
- Chỉ xuất dữ liệu thuộc phạm vi quyền truy cập.

## VI. THỐNG KÊ VÀ BÁO CÁO

### 22. Thống kê kết quả học tập
- Tổng số sinh viên.
- Tổng số học phần.
- Tổng số sinh viên đạt.
- Tổng số sinh viên không đạt.
- Tỷ lệ đạt.
- Tỷ lệ không đạt.
- Điểm trung bình của học phần.
- Điểm cao nhất.
- Điểm thấp nhất.
- Thống kê kết quả theo lớp, học phần, học kỳ và năm học.

### 23. Biểu đồ thống kê
- Biểu đồ cột thể hiện số lượng đạt và không đạt.
- Biểu đồ tròn thể hiện tỷ lệ đạt và không đạt.
- Biểu đồ phân bố điểm tổng kết.
- Biểu đồ so sánh kết quả giữa các học phần.
- Biểu đồ thống kê theo lớp.
- Dữ liệu biểu đồ được lấy từ cơ sở dữ liệu MySQL.

### 24. Báo cáo kết quả
- Báo cáo kết quả học tập theo lớp.
- Báo cáo kết quả theo học phần.
- Báo cáo kết quả theo học kỳ.
- Báo cáo tỷ lệ đạt/không đạt.
- Xuất hoặc in báo cáo.

## VII. GIAO DIỆN VÀ TRẢI NGHIỆM NGƯỜI DÙNG

### 25. Trang tổng quan (Dashboard)

**Admin Dashboard:**
- Tổng số sinh viên.
- Tổng số giảng viên.
- Tổng số lớp.
- Tổng số học phần.
- Thống kê tỷ lệ đạt/không đạt.
- Biểu đồ kết quả học tập.
- Các chức năng quản trị nhanh.

**Giảng viên Dashboard:**
- Danh sách học phần được phân công.
- Số lượng sinh viên phụ trách.
- Các học phần chưa hoàn tất nhập điểm.
- Các học phần đã lưu điểm chính thức.
- Thống kê kết quả học phần.

**Sinh viên Dashboard:**
- Thông tin cá nhân.
- Danh sách học phần đang học.
- Kết quả học tập.
- Số học phần đạt.
- Số học phần không đạt.
- Truy cập nhanh bảng điểm.

### 26. Giao diện website
- Giao diện tiếng Việt.
- Thiết kế rõ ràng, dễ sử dụng.
- Thanh điều hướng chức năng.
- Bảng dữ liệu có phân trang.
- Có tìm kiếm và bộ lọc.
- Thông báo khi thao tác thành công hoặc thất bại.
- Hộp thoại xác nhận trước các thao tác quan trọng.
- Giao diện tương thích với máy tính và điện thoại.

## VIII. BẢO MẬT VÀ QUẢN LÝ DỮ LIỆU

### 27. Bảo mật hệ thống
- Mã hóa mật khẩu bằng password_hash() của PHP.
- Xác thực mật khẩu bằng password_verify().
- Sử dụng PHP Session để quản lý đăng nhập.
- Kiểm tra quyền truy cập trên từng chức năng.
- Sử dụng PDO Prepared Statements để chống SQL Injection.
- Chống XSS khi hiển thị dữ liệu.
- Có cơ chế chống CSRF đối với thao tác thay đổi dữ liệu.
- Ngăn sinh viên truy cập điểm của người khác.
- Ngăn giảng viên chỉnh sửa điểm đã khóa.
- Ngăn truy cập trực tiếp vào trang quản trị khi không đủ quyền.

### 28. Quản lý cơ sở dữ liệu
- Sử dụng MySQL trên XAMPP.
- Thiết kế các bảng có khóa chính.
- Thiết lập khóa ngoại giữa các bảng.
- Đảm bảo dữ liệu liên kết chính xác.
- Tránh dữ liệu trùng lặp.
- Sử dụng ràng buộc dữ liệu.
- Thực hiện đầy đủ thêm, xem, sửa, xóa.
- Sử dụng transaction khi lưu điểm chính thức và chỉnh sửa điểm.
- Có dữ liệu mẫu để kiểm thử.

### 29. Sao lưu và khôi phục dữ liệu
- Cho phép Admin xuất bản sao lưu cơ sở dữ liệu.
- Có khả năng khôi phục dữ liệu từ bản sao lưu hợp lệ.
- Hạn chế quyền truy cập vào các tệp sao lưu.
- Tránh mất dữ liệu khi xảy ra lỗi.

## IX. CÔNG NGHỆ SỬ DỤNG

- Frontend: HTML5, CSS3, JavaScript.
- Backend: PHP.
- Database: MySQL.
- Môi trường phát triển: XAMPP.
- Công cụ lập trình: Visual Studio Code.
- Có thể sử dụng Bootstrap để thiết kế giao diện.
- Có thể sử dụng Chart.js để xây dựng biểu đồ thống kê.

## X. CÁC TÀI LIỆU VÀ SẢN PHẨM CẦN BÀN GIAO

1. Toàn bộ mã nguồn website.
2. File SQL tạo cơ sở dữ liệu.
3. File SQL chứa dữ liệu mẫu.
4. Tài liệu phân tích yêu cầu hệ thống.
5. Tài liệu mô tả các chức năng.
6. Sơ đồ thực thể liên kết ERD.
7. Sơ đồ Use Case hoặc sơ đồ chức năng.
8. Hướng dẫn cài đặt và cấu hình XAMPP.
9. Hướng dẫn sử dụng theo từng vai trò.
10. Báo cáo kiểm thử các chức năng.
11. Bảng phân chia nhiệm vụ cho từng thành viên nhóm.
