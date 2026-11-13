# Phân tích luồng lưu điểm

Ngày kiểm tra: 2026-11-10. Chỉ đọc mã nguồn; không chạy PHP hoặc truy vấn MySQL.

## Thiết lập
- uv 0.13.7, Graphify 0.9.105 đã có sẵn trên PATH.
- Đã chạy graphify install --project --platform codex thành công.
- Đã chạy graphify update . và graphify export html.
- Bản đồ hiện có: 121 nodes, 113 edges, 45 communities; lần cập nhật không phát hiện thay đổi topology.
- Báo cáo hiện có ghi chi phí 0 input / 0 output token; cập nhật AST không dùng LLM.

## Luồng đã đối chiếu
1. giangvien/nhap_diem.php: require_login('admin','giangvien'); co_quyen_lhp kiểm tra phạm vi lớp. Form POST gửi diem[id][cc|kt|thi], ly_do, phien_ban và action=draft hoặc finalize về chính trang này.
2. Nhánh POST kiểm tra CSRF rồi gọi includes/grade-service.php::luu_diem().
3. luu_diem mở transaction, SELECT lớp FOR UPDATE, kiểm tra người dạy/vai trò, trạng thái điểm chính thức và học kỳ; kiểm tra phien_ban; khóa các hàng điểm của lớp.
4. Kiểm tra điểm 0–10 và dữ liệu thuộc lớp; tính điểm thường xuyên, tổng kết và kết quả. Chuẩn bị UPDATE diem và INSERT lich_su_diem bằng PDO.
5. Mỗi điểm thay đổi được ghi lịch sử và UPDATE diem. Khi finalize, UPDATE lop_hoc_phan SET diem_chinh_thuc=1, chot_luc=NOW(), chot_boi=...; tăng phien_ban rồi commit. Lỗi sẽ rollback.

## Quyền sửa sau lưu
- includes/auth.php::co_quyen_lhp chỉ kiểm tra phạm vi lớp; không phải nơi khóa sau khi chốt.
- Kiểm tra quyết định ở includes/grade-service.php::luu_diem: nếu không phải admin, từ chối khi diem_chinh_thuc hoặc học kỳ Đóng. Lưu nháp vẫn sửa được nếu học kỳ Mở.
- Giao diện giangvien/nhap_diem.php vô hiệu hóa ô nhập và ẩn nút lưu cho giảng viên khi đã chốt/đóng học kỳ; server vẫn kiểm tra lại nên POST trực tiếp không bỏ qua được khóa.
- Admin vào menu Điểm → Nhập điểm, cũng dùng giangvien/nhap_diem.php?lhp=<id>, chọn bất kỳ lớp và bấm Lưu chỉnh sửa của Admin. Không có trang sửa điểm admin riêng trong luồng này. Sửa điểm chính thức có thay đổi phải nhập lý do; vẫn kiểm tra phiên bản và tính hợp lệ.

## Giới hạn bản đồ
- graphify path nhap_diem.php luu_diem() không tìm được đường, dù lời gọi trực tiếp tồn tại trong PHP. Luồng trên được xác nhận bằng mã nguồn, không giả lập cạnh trong bản đồ.
- tree_sitter_sql DLL bị Windows Application Control chặn; hai tệp SQL không đóng góp vào graph. Các câu UPDATE/INSERT nêu trên được đọc từ PHP. Chưa xác minh schema/trigger bằng MySQL và không thay đổi dữ liệu.
- Không cần khởi động lại VS Code hoặc phiên chat để hoàn tất tác vụ hiện tại: skill đã có trong phiên.
- graphify-out/ nằm trong working tree và hiện chưa được Git theo dõi. Không sửa .gitignore, không commit/push.
