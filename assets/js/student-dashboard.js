document.addEventListener('DOMContentLoaded', () => {
  const dataElement = document.getElementById('studentChartData');
  if (!dataElement) return;
  const data = JSON.parse(dataElement.textContent);
  const labels = Object.keys(data);
  if (!labels.length || typeof Chart === 'undefined') {
    document.getElementById('studentResultsChart').hidden = true;
    const empty = document.getElementById('studentChartEmpty'); empty.hidden = false;
    if (labels.length) empty.textContent = 'Không tải được biểu đồ. Bạn vẫn có thể xem kết quả trong bảng học phần.';
    return;
  }
  new Chart(document.getElementById('studentResultsChart'), { type: 'bar', data: { labels, datasets: [
    { label: 'Đạt', data: labels.map(k => data[k].dat), backgroundColor: '#16a34a', borderRadius: 5 },
    { label: 'Không đạt', data: labels.map(k => data[k].truot), backgroundColor: '#dc2626', borderRadius: 5 }
  ] }, options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { position: 'bottom' } } } });
});
