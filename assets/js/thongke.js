// ===== VẼ BIỂU ĐỒ THỐNG KÊ (Chart.js) =====
// Dữ liệu lấy từ biến THONG_KE do thongke.php in ra.

document.addEventListener('DOMContentLoaded', () => {
    if (typeof THONG_KE === 'undefined' || typeof Chart === 'undefined') return;

    // Font và màu chữ chung cho biểu đồ
    Chart.defaults.font.family = '"Be Vietnam Pro", system-ui, sans-serif';
    Chart.defaults.color = '#64748b';

    // 1. Điểm trung bình theo lớp học phần (cột)
    new Chart(document.getElementById('cTB'), {
        type: 'bar',
        data: {
            labels: THONG_KE.lop,
            datasets: [{ label: 'Điểm TB', data: THONG_KE.diemTB, backgroundColor: '#3b82f6', borderRadius: 6 }]
        },
        options: { scales: { y: { min: 0, max: 10 }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
    });

    // 2. Tỷ lệ đạt / không đạt (vành khuyên)
    new Chart(document.getElementById('cKQ'), {
        type: 'doughnut',
        data: {
            labels: ['Đạt', 'Không đạt'],
            datasets: [{ data: [THONG_KE.dat, THONG_KE.truot], backgroundColor: ['#22c55e', '#ef4444'], borderWidth: 0 }]
        },
        options: { cutout: '68%', plugins: { legend: { position: 'bottom' } } }
    });

    // 3. Phân bố điểm chữ (cột)
    new Chart(document.getElementById('cPB'), {
        type: 'bar',
        data: {
            labels: THONG_KE.chu,
            datasets: [{ label: 'Số lượt', data: THONG_KE.soLuot, backgroundColor: '#818cf8', borderRadius: 6 }]
        },
        options: { plugins: { legend: { display: false } } }
    });
});
