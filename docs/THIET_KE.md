# Phân tích và thiết kế

## Phạm vi và quy tắc

Ba tác nhân: Admin quản lý hệ thống, giảng viên phụ trách học phần và sinh viên xem điểm cá nhân. Hệ thống chạy PHP/PDO/MySQL trên XAMPP, HTML/CSS/JS với Bootstrap và Chart.js. Mô tả chức năng chi tiết ở `CHUC_NANG.md`; yêu cầu gốc ở `YEU_CAU.md`.

Lớp học phần đi qua hai trạng thái: **nháp → chính thức**. Chỉ chốt khi toàn bộ sinh viên đủ điểm. Chốt là khóa quyền sửa của giảng viên, không có chức năng giảng viên tự mở khóa. Admin sửa chính thức phải có lý do và lưu lịch sử. Sinh viên và báo cáo chỉ lấy điểm chính thức. Học kỳ đóng ngăn giảng viên sửa/chốt bản nháp.

Khi lưu, hệ thống khóa hàng lớp bằng `SELECT ... FOR UPDATE`, kiểm tra quyền và phiên bản form, kiểm tra dữ liệu toàn lớp, lưu điểm và nhật ký trong cùng transaction. Bản form cũ sau một lần lưu hoặc thay đổi danh sách sẽ bị từ chối để tránh ghi đè.

## ERD

```mermaid
erDiagram
    users ||--o| sinh_vien : "tài khoản"
    users ||--o| giang_vien : "tài khoản"
    users ||--o{ lich_su_diem : "thực hiện"
    lop_hanh_chinh ||--o{ sinh_vien : "thuộc lớp"
    nam_hoc ||--o{ hoc_ky : "gồm"
    hoc_ky ||--o{ lop_hoc_phan : "mở trong"
    mon_hoc ||--o{ lop_hoc_phan : "môn"
    giang_vien ||--o{ lop_hoc_phan : "phụ trách"
    sinh_vien ||--o{ dang_ky : "học"
    lop_hoc_phan ||--o{ dang_ky : "danh sách"
    dang_ky ||--|| diem : "điểm"
    diem ||--o{ lich_su_diem : "lịch sử"
    lop_hanh_chinh {
        varchar ma_lop PK
        varchar ten_lop
        varchar khoa_hoc
    }
    lop_hoc_phan {
        int id PK
        int mon_hoc_id FK
        int hoc_ky_id FK
        int giang_vien_id FK
        boolean diem_chinh_thuc
        datetime chot_luc
        int chot_boi
        int phien_ban
    }
    diem {
        int id PK
        int dang_ky_id FK
        text tx "JSON danh sách các bài HS1"
        decimal kt "Giữa HP HS2"
        decimal thi "Cuối HP HS3"
        decimal tong_ket "2 chữ số"
        enum ket_qua
    }
    lich_su_diem {
        int id PK
        int diem_id FK
        int nguoi_sua FK
        varchar cot
        text gia_tri_cu_text
        text gia_tri_moi_text
        varchar ly_do
        datetime thoi_gian
    }
```

Ràng buộc đăng ký duy nhất `(lhp_id,sinh_vien_id)`, một hàng điểm cho một đăng ký. Liên kết lớp hành chính dùng mã lớp, cập nhật mã tự lan truyền qua khóa ngoại. Các cột điểm và tổng kết dùng `DECIMAL(4,2)`. Cột thực hành và trọng số cũ được giữ để bảo toàn dữ liệu lịch sử nhưng không dùng trong nghiệp vụ mới.

## Sơ đồ chức năng theo tác nhân

```mermaid
flowchart LR
    A[Admin] --> Q[Quản lý tài khoản và danh mục]
    A --> P[Phân công / danh sách học phần]
    A --> S[Sửa điểm chính thức có lý do]
    A --> L[Xem lịch sử chỉnh sửa]
    A --> B[Sao lưu / khôi phục]
    G[Giảng viên] --> N[Nhập và sửa bản nháp]
    G --> C[Xác nhận chốt điểm]
    C --> K[Khóa điểm với giảng viên]
    S --> L
    G --> T[Tra cứu / in / CSV theo quyền]
    A --> T
    V[Sinh viên] --> H[Thông tin cá nhân]
    V --> D[Xem điểm chính thức của mình]
    D --> T
    A --> R[Biểu đồ / báo cáo chính thức]
    G --> R
```

## Luồng khôi phục

Xác thực Admin → kiểm tra CSRF/mật khẩu/câu xác nhận → kiểm tra JSON và tên/cột bảng → lưu bản trước khôi phục → transaction xóa theo thứ tự con-trước, thêm cha-trước → kiểm tra khóa ngoại, tính lại điểm và kiểm tra điểm chính thức đầy đủ → commit → hủy phiên. Mọi lỗi trước commit dẫn đến rollback. Website không thực thi mã SQL từ tệp upload.
