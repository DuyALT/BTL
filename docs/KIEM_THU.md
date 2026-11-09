# Báo cáo kiểm thử

Ngày kiểm tra: 08/11/2026. Môi trường: PHP/MySQL/Apache của XAMPP, website `http://localhost/BTL/`.

## Kết quả tự động

| Bộ kiểm tra | Kết quả | Nội dung |
|---|---|---|
| PHP lint | Đạt | Toàn bộ tệp PHP có cú pháp hợp lệ |
| `database/test_grading.php` | Đạt | 6 ca công thức, 7 ca kết quả và kiểm tra dữ liệu đầu vào |
| `database/test_features.php` | Đạt, 19 kiểm tra | SQL cài mới, dữ liệu mẫu, lưu nháp/chốt, quyền, lịch sử, phiên bản, khôi phục |
| `database/test_http.ps1` | Đạt, 34 kiểm tra | Trang của ba vai trò, CSV, từ chối truy cập và CSRF |

Kiểm thử tích hợp dùng database thử ngẫu nhiên và tự xóa sau khi hoàn tất. Website hiện tại không bị sửa điểm bởi các kiểm thử HTTP.

## Các ca quan trọng đã chạy

| Trường hợp | Kết quả mong đợi và đã xác nhận |
|---|---|
| TX 7, 8, 9; giữa 8; cuối 9 | Tổng kết 8.38 |
| Một bài TX 10; giữa 0; cuối 0 | Tổng kết 1.67 |
| Tất cả điểm bằng 0 | Tổng kết 0.00, không coi là thiếu điểm |
| Thiếu TX/giữa/cuối | Tổng kết chưa có, không chốt được |
| Tổng kết 4.9; cuối 8 | Không đạt |
| Tổng kết 7; cuối 4.9 | Không đạt |
| Tổng kết 5; cuối 5 | Đạt |
| Điểm chữ, âm, >10, nhiều giá trị trong giữa/cuối | Từ chối, không ghi dữ liệu |
| Giảng viên sửa lớp khác / sinh viên gửi yêu cầu sửa | Từ chối phía server |
| Học kỳ đóng | Giảng viên không lưu/chốt được |
| Lưu form phiên bản cũ | Từ chối ghi đè |
| Chốt cả lớp đủ điểm | Khóa điểm và lưu người/thời điểm chốt |
| Giảng viên sửa sau chốt | Từ chối dù gửi trực tiếp yêu cầu |
| Admin sửa chính thức thiếu lý do | Từ chối |
| Admin sửa chính thức có lý do | Tính lại kết quả, ghi nhật ký |
| Đổi TX từ 7;8;9 thành 6;9;9 | Nhật ký giữ cả danh sách cũ/mới dù trung bình bằng nhau |
| Sao lưu → khôi phục | Dữ liệu các bảng giữ nguyên |
| Khôi phục có khóa ngoại sai | Rollback toàn bộ, dữ liệu cũ giữ nguyên |
| Tệp sao lưu thiếu bảng / TX ngoài thang điểm | Từ chối |
| Sinh viên truy cập trang Admin/GV | HTTP 403 |
| Giảng viên truy cập trang Admin/lớp ngoài phân công | HTTP 403 |
| Truy cập SQL qua HTTP | HTTP 403 |
| Gửi POST CSRF sai | HTTP 400 |

## Kiểm tra bổ sung sau thiết kế lại giao diện

Giao diện EduScore đã qua 69 kiểm tra trình duyệt ở 375/768/1024/1440px, đã xem ảnh desktop/mobile, kiểm tra Tab/Enter, cột/header cố định, menu mobile, modal, validation và quyền xem dữ liệu. Không có runtime exception JavaScript trong các kiểm tra. Bộ 34 kiểm tra HTTP và kiểm tra công thức tiếp tục đạt; dữ liệu MySQL trước/sau được so sánh SHA-256 và không thay đổi. Chi tiết tệp và phạm vi kiểm thử tại `GIAO_DIEN.md`.

Không chạy lại `test_features.php` trong đợt giao diện vì bộ kiểm tra đó có lệnh DROP database thử, trái yêu cầu an toàn của đợt này. Kết quả 19 kiểm tra tích hợp ở trên thuộc đợt bổ sung nghiệp vụ trước đó; mã nghiệp vụ giữ nguyên trong đợt giao diện.

## Phạm vi chưa kiểm tra thủ công

- Đã kiểm tra ảnh chụp các kích thước yêu cầu bằng trình duyệt ẩn; chưa kiểm tra trên thiết bị điện thoại vật lý.
- Chưa in trên máy in vật lý hoặc thử mở CSV trong ứng dụng Excel; đã kiểm tra endpoint trả dữ liệu.
- Chưa kiểm thử tải lớn, nhiều người dùng đồng thời bằng công cụ tải; đã kiểm tra từ chối phiên bản form cũ.
- Chưa thực hiện khôi phục đè cơ sở dữ liệu đang sử dụng từ giao diện; nghiệp vụ khôi phục và rollback đã được chạy trên database thử riêng.

Sau khi nhóm nhập thông tin thật, nên kiểm tra thêm trên điện thoại, in/PDF, Excel và dữ liệu thực tế trước khi bàn giao.
