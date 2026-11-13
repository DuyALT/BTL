'use strict';
(() => {
  const form = document.getElementById('formDiem');
  if (!form) return;
  const table = form.querySelector('.table-scores');
  const formula = document.querySelector('.score-formula');
  if (formula && window.matchMedia('(max-width: 767px)').matches) formula.open = false;
  const rows = Array.from(table.querySelectorAll('tbody tr[data-search]'));
  const sources = rows.map(row => row.querySelector('.tx-scores'));
  if (!sources.length) { document.getElementById('addTxColumn')?.setAttribute('disabled', 'disabled'); return; }
  const header = table.tHead.rows[0];
  const sourceHeader = header.cells[4];
  const originalHeaders = Array.from(header.cells);
  const status = document.getElementById('scoreStatus');
  const initial = new Map(Array.from(form.querySelectorAll('input[name]')).map(i => [i, i.value]));
  const recoveredInput = Boolean(document.querySelector('.content .alert-danger'));
  let dirty = recoveredInput;
  let columns = 0;
  const isLocked = sources.every(input => input.disabled);
  const parts = input => input.value.trim() === '' ? [] : input.value.split(';').map(v => v.trim());
  const requiredColumns = Math.max(1, ...sources.map(i => parts(i).length));

  originalHeaders.slice(0, 3).forEach((th, i) => th.classList.add(['sticky-num', 'sticky-code', 'sticky-name'][i]));
  rows.forEach(row => Array.from(row.cells).slice(0, 3).forEach((td, i) => td.classList.add(['sticky-num', 'sticky-code', 'sticky-name'][i])));
  originalHeaders[5].innerHTML = 'GK';
  originalHeaders[5].title = 'Giữa học phần';
  originalHeaders[6].innerHTML = 'CK';
  originalHeaders[6].title = 'Cuối học phần';
  originalHeaders[7].textContent = 'TK'; originalHeaders[7].title = 'Tổng kết học phần';
  sourceHeader.hidden = true;
  sources.forEach(source => { source.closest('td').hidden = true; });

  const number = value => Number(value.trim().replace(',', '.'));
  const round = value => Math.round((value + Number.EPSILON) * 100) / 100;
  const valid = value => /^\d+(\.\d+)?$/.test(value.trim().replace(',', '.')) && number(value) >= 0 && number(value) <= 10;
  const setStatus = (text, state = '') => {
    if (!status) return;
    status.className = 'score-status ' + state;
    status.replaceChildren();
    const icon = document.createElement('i'); icon.className = 'bi ' + (state === 'dirty' ? 'bi-pencil-square' : state === 'error' ? 'bi-exclamation-circle' : 'bi-check2-circle');
    const message = document.createElement('span'); message.textContent = text;
    status.append(icon, message);
  };
  const rowValues = row => {
    const values = Array.from(row.querySelectorAll('.tx-cell')).map(input => input.value.trim());
    // Extra display columns are optional. Interior missing values remain missing.
    while (values.length && values[values.length - 1] === '') values.pop();
    return values;
  };
  const syncRow = row => {
    const source = row.querySelector('.tx-scores');
    const values = rowValues(row);
    source.value = values.join('; ');
    return values;
  };
  const badge = (cell, text, passed = null) => {
    const span = document.createElement('span'); span.className = 'result-badge' + (passed === null ? '' : passed ? ' pass' : ' fail'); span.textContent = text;
    cell.className = 'kq'; cell.replaceChildren(span);
  };
  const calculate = row => {
    const tx = syncRow(row);
    const gk = row.querySelector('[data-kind=kt]'); const ck = row.querySelector('[data-kind=thi]');
    let invalid = false;
    row.querySelectorAll('input.score:not(.tx-scores)').forEach(input => {
      const bad = input.value.trim() !== '' && !valid(input.value);
      input.classList.toggle('is-invalid', bad); input.setAttribute('aria-invalid', String(bad));
      if (bad) { input.title = 'Điểm phải là một số từ 0 đến 10'; invalid = true; }
      else input.removeAttribute('title');
    });
    const total = row.querySelector('.tong'); const result = row.querySelector('.kq');
    if (invalid || !tx.length || tx.some(v => !v || !valid(v)) || !gk.value.trim() || !ck.value.trim()) {
      total.textContent = '—'; badge(result, invalid ? 'Điểm không hợp lệ' : 'Chưa đủ điểm'); return;
    }
    const t = round((tx.reduce((sum, v) => sum + round(number(v)), 0) + 2 * round(number(gk.value)) + 3 * round(number(ck.value))) / (tx.length + 5));
    total.textContent = t.toFixed(2);
    const passed = t >= 5 && round(number(ck.value)) >= 5;
    badge(result, passed ? 'Đạt' : 'Không đạt', passed);
  };
  const updateDirty = () => {
    dirty = recoveredInput || Array.from(initial).some(([input, value]) => input.value !== value);
    setStatus(dirty ? 'Có thay đổi chưa lưu' : isLocked ? 'Điểm chỉ xem — đã khóa' : 'Dữ liệu đã tải', dirty ? 'dirty' : '');
  };
  const bind = input => {
    const start = input.value;
    input.addEventListener('input', () => {
      input.classList.toggle('cell-changed', input.value !== start);
      calculate(input.closest('tr')); updateDirty();
    });
    input.addEventListener('keydown', e => {
      if (!['Enter', 'ArrowDown', 'ArrowUp'].includes(e.key)) return;
      e.preventDefault(); const row = input.closest('tr'); const index = input.closest('td').cellIndex;
      let next = e.key === 'ArrowUp' ? row.previousElementSibling : row.nextElementSibling;
      while (next && next.style.display === 'none') next = e.key === 'ArrowUp' ? next.previousElementSibling : next.nextElementSibling;
      const target = next?.cells[index]?.querySelector('input:not(:disabled)'); target?.focus(); target?.select();
    });
  };
  const addColumn = initialValues => {
    columns++;
    const th = document.createElement('th'); th.className = 'text-center'; th.dataset.txColumn = String(columns);
    th.innerHTML = 'TX' + columns;
    header.insertBefore(th, sourceHeader);
    rows.forEach((row, i) => {
      const source = sources[i]; const td = document.createElement('td'); td.className = 'text-center';
      const input = document.createElement('input'); input.type = 'text'; input.inputMode = 'decimal'; input.autocomplete = 'off';
      input.className = 'form-control form-control-sm score tx-cell'; input.value = initialValues?.[i]?.[columns - 1] || ''; input.disabled = source.disabled;
      input.setAttribute('aria-label', 'Thường xuyên bài ' + columns + ' của ' + row.cells[1].textContent.trim());
      td.append(input); source.closest('td').before(td); bind(input);
    });
  };
  const savedParts = sources.map(parts);
  for (let i = 0; i < requiredColumns; i++) addColumn(savedParts);
  rows.forEach(row => {
    row.querySelectorAll('input.score[data-kind]').forEach(bind);
    const cell = row.querySelector('.kq'); const text = cell.textContent.trim();
    badge(cell, text || 'Chưa đủ điểm', text === 'Đạt' ? true : text === 'Không đạt' ? false : null);
  });
  const addButton = document.getElementById('addTxColumn');
  if (addButton) {
    addButton.disabled = isLocked;
    addButton.addEventListener('click', () => {
      addColumn();
      const first = rows.find(r => r.style.display !== 'none');
      first?.querySelector('td:nth-child(' + (4 + columns) + ') input')?.focus();
      window.eduToast?.('Đã thêm cột TX' + columns + '.');
    });
  }
  document.getElementById('locSV')?.addEventListener('input', e => {
    const key = e.target.value.toLocaleLowerCase('vi').trim(); let visible = 0;
    rows.forEach(row => { const show = row.dataset.search.includes(key); row.style.display = show ? '' : 'none'; if (show) visible++; });
    document.getElementById('scoreRowCount').textContent = visible + ' / ' + rows.length + ' sinh viên';
  });
  // This capture handler runs before generic form validation/loading handlers.
  form.addEventListener('submit', e => {
    let firstInvalid = null;
    rows.forEach(row => {
      const values = syncRow(row);
      row.querySelectorAll('.tx-cell').forEach((input, i) => {
        const bad = i < values.length && (!input.value.trim() || !valid(input.value));
        input.classList.toggle('is-invalid', bad);
        if (bad) firstInvalid ||= input;
      });
    });
    if (firstInvalid) {
      e.preventDefault(); e.stopImmediatePropagation();
      setStatus('Chưa lưu — kiểm tra lại điểm thường xuyên', 'error');
      window.eduToast?.('Điểm thường xuyên phải từ 0 đến 10. Không để trống giữa các bài đã nhập.', true);
      firstInvalid.focus();
    }
  }, true);
  form.addEventListener('eduscore:submitting', () => { dirty = false; setStatus('Đang lưu điểm…'); });
  window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  setStatus(isLocked ? 'Điểm chỉ xem — đã khóa' : dirty ? 'Chưa lưu — xem thông báo lỗi' : 'Dữ liệu đã tải', dirty ? 'error' : '');
})();
