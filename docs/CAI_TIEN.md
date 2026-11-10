# Ghi chú cải tiến giao diện EduScore

Tài liệu này ghi lại các thay đổi giao diện đã thực hiện, lý do và cách kiểm tra lại.
Xem thêm [GIAO_DIEN.md](GIAO_DIEN.md) cho mô tả hệ thống thiết kế tổng thể.

## 1. Ngôn ngữ và phông chữ

| Hạng mục | Trước | Sau |
|---|---|---|
| Nội dung giao diện | Tiếng Anh lẫn tiếng Việt | Tiếng Việt toàn bộ: nhãn, thông báo, trạng thái, lỗi |
| Phông tiêu đề / số liệu | Font hệ thống | **Lexend** qua token `--font-display` |
| Phông nội dung / bảng | Font hệ thống | **Be Vietnam Pro** (`--bs-body-font-family`) |

Cả hai phông đều có bộ dấu tiếng Việt đầy đủ. Phông được nạp một lần trong `includes/header.php`
qua Google Fonts, khai báo token trong `assets/css/eduscore.css`.

## 2. Trang đăng nhập (`login.php`, `assets/css/login-campus.css`)

- Nền ảnh học viện `assets/images/login-campus.jpg` phủ lớp gradient xanh navy để chữ trắng đủ tương phản.
- Thẻ đăng nhập dạng kính mờ, bo góc 22px, đổ bóng mềm; cột trái giữ khối giới thiệu ba điểm nhấn.
- Tên đơn vị **Học viện An ninh nhân dân** in đậm ngay dưới logo (lớp `.login-institute`), cả bản desktop và bản thu gọn.
- Có sẵn: hiện/ẩn mật khẩu, ghi nhớ tên đăng nhập khi nhập sai, bấm tài khoản mẫu để điền nhanh,
  hộp thoại **Quên mật khẩu** hướng dẫn liên hệ quản trị viên, cảnh báo khóa tài khoản và hết phiên.
- Chỉ thiết kế cho khổ desktop (≥992px); dưới ngưỡng này cột giới thiệu ẩn, form thu về một cột.

## 3. In đậm tên riêng trong bảng

Hai lớp dùng chung trong `assets/css/eduscore.css`:

```css
.cell-person, .cell-entity { font-weight:600; color:var(--ink); }
.cell-person { white-space:nowrap; }   /* họ tên luôn nằm trên một dòng */
```

- `.cell-person`: họ tên người — sinh viên, giảng viên, người sửa điểm, chủ tài khoản.
- `.cell-entity`: tên môn học, lớp hành chính, học kỳ, năm học, tên lớp học phần.

Đã áp dụng ở 17 tệp: `index.php`, `tra_cuu.php`, `thongke.php`, toàn bộ `admin/*.php`,
`giangvien/*.php`, `sinhvien/*.php`.

## 4. Bảng không cuộn ngang trên desktop

Bổ sung hai lớp tiện ích:

```css
.table.table-fit td, .table.table-fit th { padding-left:8px; padding-right:8px; }
.table.table-dense { font-size:.74rem; }
.table.table-dense td, .table.table-dense th { padding-top:8px; padding-bottom:8px; }
```

- **Tổng quan**: bảng tiến độ nhập điểm bỏ cột *Sĩ số* (thông tin đã nằm trong thanh `20/20`),
  còn 7 cột; bỏ `nowrap` ở cột Học kỳ để chữ tự xuống dòng. Kết quả đo tại 1440px: tràn ngang 181px → 0px,
  chiều cao hàng 75px → 56px.
- **Thống kê**: hai bảng được bọc trong `.table-responsive` và dùng `.table-fit .table-dense` thay cho
  `table-nowrap`. Tràn ngang 181px và 9px → 0px.
- **Danh sách sinh viên**: dùng `.table-fit` cho bảng 11 cột.

Khi thêm cột mới, kiểm tra lại bằng DevTools: `bo.scrollWidth - bo.clientWidth` phải bằng 0.

## 5. Hạ tầng CSS

- Tăng phiên bản tệp CSS để trình duyệt nạp bản mới: `eduscore.css?v=7`, `login-campus.css?v=3`.
- Mọi màu, bo góc, đổ bóng dùng biến trong `:root` của `assets/css/eduscore.css`, không viết giá trị rời trong HTML.

## 6. Lưu ý khi làm việc tiếp

- Bộ đệm của editor đôi khi ghi đè tệp trên đĩa bằng nội dung cũ, làm mất thay đổi chưa commit.
  Sau mỗi cụm chỉnh sửa nên commit ngay; nếu thấy nội dung biến mất, đối chiếu `git diff` và chép lại.
- Kiểm tra nhanh sau khi sửa: `php -l <tệp>` cho cú pháp, mở trang ở khổ 1440px cho bố cục.
