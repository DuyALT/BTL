# Cài đặt và sử dụng

## Cài mới trên XAMPP

1. Chép mã nguồn vào `C:\xampp\htdocs\BTL-GitHub`.
2. Bật Apache và MySQL trong XAMPP Control Panel.
3. Trong phpMyAdmin, import `database/schema.sql`, sau đó `database/sample_data.sql`. SQL cài mới **xóa các bảng cũ cùng tên**.
4. Sao chép `config/db.example.php` thành `config/db.php` (file cấu hình riêng được Git bỏ qua).
5. Điều chỉnh thông tin MySQL và `BASE_URL` trong `config/db.php` nếu dùng thư mục hoặc tài khoản MySQL khác.
6. Mở `http://localhost/BTL-GitHub/`.

Tài khoản mẫu: `admin`, `gv001` đến `gv004`, `2251000001` đến `2251000040`. Mật khẩu mẫu `123456`. Sinh viên cuối cùng bị khóa để kiểm thử. Đổi mật khẩu khi dùng thực tế.

## Nâng cấp website đang có dữ liệu

Không import lại tệp SQL cài mới. Chạy trong thư mục dự án:

```powershell
C:\xampp\php\php.exe database/upgrade_features.php
```

Script lưu bản sao các bảng cũ trong tệp PHP có chặn truy cập rồi thêm cấu trúc còn thiếu. Điểm cũ được giữ; chuyên cần cũ trở thành bài thường xuyên đầu tiên, kiểm tra cũ thành giữa HP, thi cũ thành cuối HP. Thực hành cũ vẫn được lưu nhưng không tính vào công thức. Lớp cũ mặc định là bản nháp; cần kiểm tra rồi chốt để sinh viên xem được. Chạy lại nâng cấp không mở khóa lớp đã chốt.

## Admin

- Tạo năm học, học kỳ và trạng thái Mở/Đóng; thêm lớp hành chính trước khi tạo sinh viên.
- Thêm sinh viên, email, khóa học và trạng thái học tập. Tài khoản được tạo theo mã SV.
- Thêm giảng viên, môn học, mở lớp học phần, phân công giảng viên và thêm sinh viên vào lớp.
- Dùng Quản lý người dùng để cập nhật thông tin, khóa/mở khóa, phân quyền hoặc đặt lại mật khẩu. Tài khoản tạo riêng cần hồ sơ giảng viên/sinh viên tương ứng để có dữ liệu học tập.
- Mở Nhập điểm, chọn lớp. Có thể sửa điểm chính thức, bắt buộc nhập lý do; điểm vẫn khóa với giảng viên.
- Lịch sử sửa điểm hiển thị tài khoản, ngày giờ, sinh viên, học phần, điểm trước/sau và lý do. Điểm thường xuyên lưu cả danh sách, kể cả sửa hai bài có cùng trung bình.
- Dùng Tra cứu để lọc, sắp xếp, in và xuất CSV. Thống kê chỉ tính điểm đã chốt.

## Giảng viên

1. Mở Nhập điểm và chọn lớp được phân công.
2. Nhập từng bài thường xuyên trong ô TX1, TX2… (mỗi bài hệ số 1); bấm **Thêm bài TX** khi cần. Nhập một điểm GK (giữa HP, hệ số 2), một điểm CK (cuối HP, hệ số 3). Mỗi điểm từ 0 đến 10, lưu hai chữ số thập phân. Không để trống giữa các bài TX đã nhập; cột TX cuối còn trống là cột dự phòng, không tính thành một bài. Khi JavaScript tắt, ô nhập gốc dùng dạng `7; 8,5; 9`.
3. Nhấn **Lưu bản nháp** để lưu và sửa tiếp. Tổng kết hiện ngay để xem trước; sinh viên chưa thấy bản nháp.
4. Khi mọi sinh viên đã đủ ba phần, nhấn **Lưu điểm chính thức** và xác nhận. Cả lớp bị khóa; giảng viên chỉ xem được.
5. Nếu cần sửa điểm chính thức, liên hệ Admin. Giảng viên cũng không được sửa bản nháp khi học kỳ đã đóng.

Công thức: `(tổng điểm TX + 2 × giữa HP + 3 × cuối HP) / (số bài TX + 5)`, làm tròn hai chữ số. Đạt khi tổng kết và cuối HP cùng từ 5 trở lên. Thiếu điểm bắt buộc thì chưa có kết quả và không thể chốt. Hệ số cố định, không cấu hình theo phần trăm.

Khi phiên khác thay đổi điểm hoặc danh sách lớp, website yêu cầu tải lại thay vì ghi đè dữ liệu mới.

## Sinh viên

- Trang Thông tin cá nhân hiển thị hồ sơ, số học phần đạt/không đạt và danh sách học phần đăng ký.
- Bảng điểm cá nhân chỉ hiển thị lớp đã lưu chính thức, phân nhóm theo học kỳ/năm học.
- Tra cứu cho phép lọc môn, học phần, học kỳ, năm học và kết quả; xuất CSV hoặc in. Chỉ dữ liệu của tài khoản đang đăng nhập được trả về.
- Không có chức năng nhập hoặc sửa điểm. Cột điểm chữ đã bỏ theo yêu cầu; thang 4 hiện có vẫn giữ ở bảng điểm cá nhân.

## In và xuất

- Bảng điểm cá nhân, bản in học phần và bảng tổng hợp lớp có nút In; có thể chọn Save as PDF trong trình duyệt.
- CSV UTF-8 mở được bằng Excel; nếu Excel hiển thị sai cột, dùng Data → From Text/CSV, chọn UTF-8 và dấu phẩy.
- Các danh sách có phân trang 20 hàng/trang. Bản xuất CSV chứa toàn bộ kết quả của bộ lọc, không chỉ trang đang xem.

## Sao lưu và khôi phục

Admin vào Sao lưu & khôi phục, tải tệp JSON. Tệp chứa dữ liệu tài khoản và điểm nên cần giữ riêng tư. Không hỗ trợ tải SQL tùy ý lên website để thực thi.

Khôi phục cần tệp do website xuất, mật khẩu Admin hiện tại, nhập `KHOI PHUC` và xác nhận. Website tạo bản trước khôi phục trong `database/`, kiểm tra cấu trúc, khóa ngoại và tính lại kết quả trong transaction. Lỗi thì hoàn tác; thành công thì đăng xuất. Dùng tài khoản nằm trong bản sao lưu để đăng nhập lại. Giới hạn tệp 20 MB và giới hạn upload PHP (giá trị nhỏ hơn được áp dụng).

Thư mục `database/` bị Apache chặn bằng `.htaccess`; khi chuyển máy chủ cần giữ cấu hình này. Các script nâng cấp, tạo SQL và kiểm thử chỉ chạy qua CLI.

## Kiểm thử

```powershell
C:\xampp\php\php.exe database/test_grading.php
C:\xampp\php\php.exe database/test_features.php
```

Kiểm thử tích hợp tạo cơ sở dữ liệu tên ngẫu nhiên `quanlydiem_test_*`, chạy SQL và thử nghiệp vụ rồi xóa chính cơ sở dữ liệu thử đó. Cần tài khoản MySQL có quyền tạo/xóa database. Không thay đổi cơ sở dữ liệu đang dùng.

Biểu đồ, Bootstrap và phông chữ tải từ CDN. Khi máy không có Internet, nghiệp vụ PHP/MySQL vẫn chạy nhưng biểu đồ và giao diện phụ thuộc CDN có thể không tải đầy đủ.
