'use strict';
// UI only: keep PHP endpoints, form names, CSRF and submitter values intact.
window.eduToast = (message, error = false) => {
  const region = document.getElementById('toastRegion');
  if (!region) return;
  const item = document.createElement('div');
  item.className = 'ui-toast' + (error ? ' error' : '');
  item.setAttribute('role', error ? 'alert' : 'status');
  item.textContent = message;
  region.append(item);
  setTimeout(() => item.remove(), 6500);
};

document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('sidebarToggle');
  const setSidebar = collapsed => {
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    if (toggle) {
      toggle.setAttribute('aria-expanded', String(!collapsed));
      toggle.setAttribute('aria-label', collapsed ? 'Mở rộng menu' : 'Thu gọn menu');
    }
  };
  setSidebar(document.documentElement.dataset.sidebar === 'collapsed');
  toggle?.addEventListener('click', () => {
    const collapsed = !document.body.classList.contains('sidebar-collapsed');
    setSidebar(collapsed);
    try { localStorage.setItem('eduscore-sidebar', collapsed ? 'collapsed' : 'expanded'); } catch (_) {}
  });
  // Keep native links working; close the mobile overlay for in-page sections.
  const sidebar = document.getElementById('sidebar');
  sidebar?.querySelectorAll('a.nav-item-link').forEach(link => {
    link.addEventListener('click', () => {
      const target = new URL(link.href, location.href);
      if (target.pathname === location.pathname && target.hash && window.bootstrap?.Offcanvas) {
        bootstrap.Offcanvas.getInstance(sidebar)?.hide();
      }
    });
  });
  const highlightSection = () => {
    if (!location.hash || !sidebar) return;
    const links = Array.from(sidebar.querySelectorAll('.sidebar-nav a.nav-item-link'));
    const selected = links.find(link => { const url = new URL(link.href); return url.pathname === location.pathname && url.hash === location.hash; });
    if (!selected) return;
    links.forEach(link => { link.classList.toggle('active', link === selected); link.removeAttribute('aria-current'); });
    selected.setAttribute('aria-current', 'location');
  };
  highlightSection();
  window.addEventListener('hashchange', highlightSection);

  // Bind visible labels without changing POST field names.
  document.querySelectorAll('label:not([for])').forEach((label, i) => {
    const input = label.parentElement.querySelector('input:not([type=hidden]),select,textarea');
    if (input) { if (!input.id) input.id = 'edu-field-' + i; label.htmlFor = input.id; }
  });
  document.querySelectorAll('input:not([type=hidden]),select,textarea').forEach((input, i) => {
    if (!input.getAttribute('aria-label') && !input.labels?.length && input.type !== 'submit') {
      input.setAttribute('aria-label', input.dataset.label || input.placeholder || input.name || 'Trường thông tin ' + (i + 1));
    }
  });

  document.querySelectorAll('.content > form:not([method]), .content > form[method=get]').forEach(form => form.classList.add('filter-bar'));
  const courseFilters = document.getElementById('courseFilters');
  courseFilters?.addEventListener('change', () => {
    const year = document.getElementById('courseYear').value, term = document.getElementById('courseTerm').value, state = document.getElementById('courseState').value;
    let count = 0;
    document.querySelectorAll('tr[data-course-year]').forEach(row => {
      const show = (!year || row.dataset.courseYear === year) && (!term || row.dataset.courseTerm === term) && (!state || row.dataset.courseState === state);
      row.hidden = !show; if (show) count++;
    });
    document.getElementById('courseFilterEmpty').hidden = count > 0;
  });
  document.getElementById('studentYearFilter')?.addEventListener('change', e => {
    document.querySelectorAll('[data-school-year]').forEach(card => card.hidden = Boolean(e.target.value && card.dataset.schoolYear !== e.target.value));
  });
  document.querySelectorAll('.content table').forEach(table => {
    if (!table.closest('.table-responsive,.table-wrap,.score-grid-wrap')) {
      const wrap = document.createElement('div'); wrap.className = 'table-wrap';
      table.before(wrap); wrap.append(table);
    }
    if (table.classList.contains('table-scores') || table.closest('.print-sheet')) return;
    const body = table.tBodies[0];
    if (!body) return;
    const collator = new Intl.Collator('vi', { numeric: true, sensitivity: 'base' });
    table.querySelectorAll('thead th').forEach((th, column) => {
      if (!th.textContent.trim() || /thao tác|lý do/i.test(th.textContent) || th.colSpan > 1) return;
      th.dataset.sortable = 'true'; th.setAttribute('aria-sort', 'none');
      const sortButton = document.createElement('button'); sortButton.type = 'button'; sortButton.className = 'table-sort';
      sortButton.setAttribute('aria-label', th.textContent.trim() + ' — sắp xếp các dòng trong trang này');
      while (th.firstChild) sortButton.append(th.firstChild);
      const indicator = document.createElement('span'); indicator.className = 'ms-1 opacity-50'; indicator.textContent = '↕'; indicator.setAttribute('aria-hidden', 'true'); sortButton.append(indicator); th.append(sortButton);
      const sort = () => {
        const ascending = th.getAttribute('aria-sort') !== 'ascending';
        table.querySelectorAll('th[aria-sort]').forEach(h => h.setAttribute('aria-sort', 'none'));
        th.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');
        const rows = Array.from(body.rows).filter(row => row.cells.length > column && row.cells[0].colSpan === 1);
        rows.sort((a, b) => collator.compare(a.cells[column].textContent.trim(), b.cells[column].textContent.trim()) * (ascending ? 1 : -1));
        const fragment = document.createDocumentFragment(); rows.forEach(r => fragment.append(r)); body.append(fragment);
      };
      sortButton.addEventListener('click', sort);
    });
  });
  document.querySelectorAll('.content td').forEach(td => {
    if (td.closest('.table-scores') || td.childElementCount) return;
    const text = td.textContent.trim();
    if (td.colSpan > 1 && /chưa|không có|không tìm/i.test(text)) {
      td.classList.add('empty-state'); const icon = document.createElement('i'); icon.className = 'bi bi-inbox'; icon.setAttribute('aria-hidden', 'true'); td.prepend(icon); return;
    }
    if (['Đạt', 'Không đạt', 'Chưa công bố', 'Chính thức', 'Bản nháp', 'Đang học'].includes(text)) {
      const badge = document.createElement('span'); badge.className = 'result-badge' + (text === 'Đạt' ? ' pass' : text === 'Không đạt' ? ' fail' : ''); badge.textContent = text; td.replaceChildren(badge);
    }
  });

  document.querySelectorAll('[data-password-toggle]').forEach(button => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.passwordToggle); if (!input) return;
      const visible = input.type === 'password'; input.type = visible ? 'text' : 'password';
      button.setAttribute('aria-label', visible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'); button.setAttribute('aria-pressed', String(visible));
      button.querySelector('i').className = 'bi ' + (visible ? 'bi-eye-slash' : 'bi-eye');
    });
  });

  // Click the original control after confirmation so name=action survives.
  let pendingControl = null;
  let approvedControl = null;
  const modalElement = document.getElementById('confirmModal');
  document.querySelectorAll('button[name=action][value=toggle]').forEach(button => { button.dataset.confirm ||= 'Xác nhận thay đổi trạng thái khóa của tài khoản này?'; });
  document.addEventListener('click', e => {
    const control = e.target.closest('[data-confirm]'); if (!control) return;
    if (control === approvedControl) { approvedControl = null; return; }
    e.preventDefault(); e.stopImmediatePropagation();
    if (!window.bootstrap?.Modal || !modalElement) {
      if (window.confirm(control.dataset.confirm)) { approvedControl = control; control.click(); }
      return;
    }
    pendingControl = control;
    document.getElementById('confirmMessage').textContent = control.dataset.confirm;
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
  }, true);
  document.getElementById('confirmProceed')?.addEventListener('click', () => {
    if (!pendingControl) return;
    const control = pendingControl; pendingControl = null;
    bootstrap.Modal.getInstance(modalElement)?.hide(); approvedControl = control; control.click();
  });
  modalElement?.addEventListener('hidden.bs.modal', () => { pendingControl = null; });

  document.querySelectorAll('form[method=post],form[method=POST]').forEach(form => {
    form.addEventListener('submit', e => {
      if (e.defaultPrevented) return;
      if (form.dataset.submitting === 'true') { e.preventDefault(); return; }
      // Downloads don't navigate: do not leave their buttons permanently locked.
      if (e.submitter?.value === 'export') return;
      form.dataset.submitting = 'true'; form.setAttribute('aria-busy', 'true');
      form.dispatchEvent(new CustomEvent('eduscore:submitting'));
      if (e.submitter) {
        e.submitter.dataset.previousHtml = e.submitter.innerHTML;
        e.submitter.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Đang xử lý…';
      }
      // Disabled buttons are omitted from POST; wait for native serialization.
      setTimeout(() => form.querySelectorAll('button[type=submit],button:not([type]),input[type=submit]').forEach(b => b.disabled = true), 0);
    });
  });
  window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting]').forEach(form => {
      delete form.dataset.submitting; form.removeAttribute('aria-busy');
      form.querySelectorAll('[data-previous-html]').forEach(b => { b.innerHTML = b.dataset.previousHtml; delete b.dataset.previousHtml; b.disabled = false; });
      form.querySelectorAll('button:disabled').forEach(b => { if (!b.dataset.permanentlyDisabled) b.disabled = false; });
    });
  });
});
