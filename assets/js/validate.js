// ===== KIỂM TRA DỮ LIỆU PHÍA TRÌNH DUYỆT (JavaScript) =====
// PHP vẫn kiểm tra lại toàn bộ ở phía server, JS chỉ giúp báo lỗi sớm.

function hopLeDiem(v) {
    v = String(v).trim().replace(',', '.');
    if (v === '') return true;                       // được để trống (chưa nhập)
    if (!/^\d+(\.\d+)?$/.test(v)) return false;
    const n = parseFloat(v);
    return n >= 0 && n <= 10;
}

function baoLoi(input, msg) {
    input.classList.add('is-invalid');
    input.title = msg;
}

function xoaLoi(input) {
    input.classList.remove('is-invalid');
    input.title = '';
}

document.addEventListener('DOMContentLoaded', () => {
    // 1) Ô điểm: phải trong khoảng 0–10
    document.querySelectorAll('input.score').forEach(inp => {
        inp.addEventListener('input', () => {
            hopLeDiem(inp.value) ? xoaLoi(inp) : baoLoi(inp, 'Điểm phải là số từ 0 đến 10');
        });
    });

    // 2) Các form có class js-validate: kiểm tra trước khi gửi
    document.querySelectorAll('form.js-validate').forEach(form => {
        form.addEventListener('submit', e => {
            const loi = [];

            form.querySelectorAll('[required]').forEach(el => {
                if (!el.disabled && el.value.trim() === '') {
                    baoLoi(el, 'Không được để trống');
                    loi.push((el.dataset.label || el.name) + ' không được để trống');
                } else xoaLoi(el);
            });

            form.querySelectorAll('input.score').forEach(el => {
                if (!el.disabled && !hopLeDiem(el.value)) {
                    baoLoi(el, 'Điểm phải từ 0 đến 10');
                    loi.push('Có ô điểm không hợp lệ (phải từ 0 đến 10)');
                }
            });

            // Trọng số môn học phải có tổng = 100
            const ts = form.querySelectorAll('input.weight');
            if (ts.length) {
                let tong = 0;
                ts.forEach(el => tong += parseInt(el.value || 0, 10));
                if (tong !== 100) loi.push('Tổng trọng số phải bằng 100% (hiện tại ' + tong + '%)');
            }

            const pw = form.querySelector('[name=mat_khau_moi]');
            const pw2 = form.querySelector('[name=nhap_lai]');
            if (pw && pw.value && pw.value.length < 6) loi.push('Mật khẩu tối thiểu 6 ký tự');
            if (pw && pw2 && pw.value !== pw2.value) loi.push('Mật khẩu nhập lại không khớp');

            form.querySelectorAll('input[type=email]').forEach(el => {
                if (el.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value)) loi.push('Email không đúng định dạng');
            });

            if (loi.length) {
                e.preventDefault();
                alert([...new Set(loi)].join('\n'));
            }
        });
    });

    // 3) Nút xóa: hỏi xác nhận
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });

    // 4) Hiển thị tổng trọng số khi sửa môn học
    const tsInputs = document.querySelectorAll('input.weight');
    const tsOut = document.getElementById('tongTrongSo');
    if (tsInputs.length && tsOut) {
        const cap = () => {
            let t = 0;
            tsInputs.forEach(el => t += parseInt(el.value || 0, 10));
            tsOut.textContent = t + '%';
            tsOut.className = t === 100 ? 'text-success fw-bold' : 'text-danger fw-bold';
        };
        tsInputs.forEach(el => el.addEventListener('input', cap));
        cap();
    }
});
