/**
 * Datacenter Inspection System - Preventive Maintenance JavaScript Module
 * Handles View Switching, Interactive Calendar, and Drag-and-Drop Kanban Board.
 */

document.addEventListener('DOMContentLoaded', () => {
  initViewSwitcher();
  initRecurrenceSelector();
  initKanbanDragAndDrop();
  initCalendar();
  initEventModal();
  initEquipmentCategoryFilter();
  initAttachmentManager();
  initLiveSearch();
});

/* ============================================================
   1. VIEW SWITCHER (Table, Calendar, Kanban)
   ============================================================ */
function initViewSwitcher() {
  const pills = document.querySelectorAll('.view-pill-btn');
  const viewSections = {
    table: document.getElementById('maint-view-table'),
    calendar: document.getElementById('maint-view-calendar'),
    kanban: document.getElementById('maint-view-kanban')
  };

  pills.forEach(pill => {
    pill.addEventListener('click', (e) => {
      const targetView = pill.getAttribute('data-view');
      if (!targetView || !viewSections[targetView]) return;

      e.preventDefault();

      pills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');

      Object.keys(viewSections).forEach(v => {
        if (viewSections[v]) {
          viewSections[v].style.display = (v === targetView) ? 'block' : 'none';
        }
      });

      // Update URL without reload
      const url = new URL(window.location);
      url.searchParams.set('view', targetView);
      window.history.replaceState({}, '', url);

      // Trigger calendar render if calendar opened
      if (targetView === 'calendar' && window.renderMaintCalendar) {
        window.renderMaintCalendar();
      }
    });
  });
}

/* ============================================================
   2. DYNAMIC RECURRENCE FORM TOGGLES
   ============================================================ */
function initRecurrenceSelector() {
  const radios = document.querySelectorAll('input[name="recurrence_type"]');
  const customBox = document.getElementById('custom-recurrence-options');

  if (!radios.length || !customBox) return;

  function updateRecurrenceVisibility() {
    const selected = document.querySelector('input[name="recurrence_type"]:checked');
    if (selected && selected.value === 'custom') {
      customBox.classList.add('is-active');
      const input = customBox.querySelector('input');
      if (input) input.focus();
    } else {
      customBox.classList.remove('is-active');
    }
  }

  radios.forEach(r => r.addEventListener('change', updateRecurrenceVisibility));
  updateRecurrenceVisibility();
}

/* ============================================================
   3. KANBAN DRAG & DROP
   ============================================================ */
function initKanbanDragAndDrop() {
  const cards = document.querySelectorAll('.kanban-card');
  const wrappers = document.querySelectorAll('.kanban-cards-wrapper');

  let draggedCard = null;

  cards.forEach(card => {
    card.addEventListener('dragstart', (e) => {
      draggedCard = card;
      card.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', card.getAttribute('data-plan-id'));
    });

    card.addEventListener('dragend', () => {
      card.classList.remove('is-dragging');
      wrappers.forEach(w => w.classList.remove('drag-over'));
      draggedCard = null;
    });
  });

  wrappers.forEach(wrapper => {
    wrapper.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      wrapper.classList.add('drag-over');
    });

    wrapper.addEventListener('dragleave', (e) => {
      if (!wrapper.contains(e.relatedTarget)) {
        wrapper.classList.remove('drag-over');
      }
    });

    wrapper.addEventListener('drop', async (e) => {
      e.preventDefault();
      wrapper.classList.remove('drag-over');

      if (!draggedCard) return;

      const planId = draggedCard.getAttribute('data-plan-id');
      const targetColumn = wrapper.closest('.kanban-column');
      const newStatus = targetColumn ? targetColumn.getAttribute('data-status') : null;
      const oldWrapper = draggedCard.parentElement;

      if (!newStatus || oldWrapper === wrapper) return;

      // Optimistically move card in DOM
      wrapper.appendChild(draggedCard);
      updateKanbanColumnCounts();

      // Trigger status update via AJAX
      try {
        const response = await fetch('/maintenance/status', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            id: planId,
            status: newStatus
          })
        });

        const data = await response.json();
        if (data && data.success) {
          showMaintenanceToast('success', 'Status Updated', `Plan #${planId} is now ${newStatus.toUpperCase().replace('_', ' ')}`);
        } else {
          // Rollback on error
          oldWrapper.appendChild(draggedCard);
          updateKanbanColumnCounts();
          showMaintenanceToast('danger', 'Update Failed', data.message || 'Could not update plan status.');
        }
      } catch (err) {
        oldWrapper.appendChild(draggedCard);
        updateKanbanColumnCounts();
        showMaintenanceToast('danger', 'Network Error', 'Could not reach server.');
      }
    });
  });
}

function updateKanbanColumnCounts() {
  document.querySelectorAll('.kanban-column').forEach(col => {
    const countBadge = col.querySelector('.kanban-col-count');
    const cards = col.querySelectorAll('.kanban-card');
    if (countBadge) {
      countBadge.textContent = cards.length;
    }
  });
}

/* ============================================================
   4. INTERACTIVE CALENDAR ENGINE
   ============================================================ */
let calendarCurrentDate = new Date();

function initCalendar() {
  const calContainer = document.getElementById('calendar-grid-body');
  if (!calContainer) return;

  const prevBtn = document.getElementById('cal-prev-btn');
  const nextBtn = document.getElementById('cal-next-btn');
  const todayBtn = document.getElementById('cal-today-btn');

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      calendarCurrentDate.setMonth(calendarCurrentDate.getMonth() - 1);
      renderCalendar();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      calendarCurrentDate.setMonth(calendarCurrentDate.getMonth() + 1);
      renderCalendar();
    });
  }

  if (todayBtn) {
    todayBtn.addEventListener('click', () => {
      calendarCurrentDate = new Date();
      renderCalendar();
    });
  }

  window.renderMaintCalendar = renderCalendar;
  renderCalendar();
}

function renderCalendar() {
  const calGrid = document.getElementById('calendar-grid-body');
  const monthTitle = document.getElementById('cal-month-title');
  if (!calGrid) return;

  const year = calendarCurrentDate.getFullYear();
  const month = calendarCurrentDate.getMonth();

  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  if (monthTitle) {
    monthTitle.textContent = `${monthNames[month]} ${year}`;
  }

  // Get embedded events from window object
  const events = window.maintCalendarEvents || [];

  // Month calculation
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);

  // Monday-based index (0 = Monday, 6 = Sunday)
  let startDayIndex = firstDay.getDay() - 1;
  if (startDayIndex === -1) startDayIndex = 6;

  const totalDays = lastDay.getDate();
  const prevMonthLastDay = new Date(year, month, 0).getDate();

  const today = new Date();
  const isCurrentMonthYear = (today.getFullYear() === year && today.getMonth() === month);
  const todayDate = today.getDate();

  let html = '';

  // 1. Trailing days from previous month
  for (let i = startDayIndex - 1; i >= 0; i--) {
    const prevDayNum = prevMonthLastDay - i;
    html += `
      <div class="calendar-day-cell other-month">
        <div class="calendar-day-top">
          <span class="calendar-day-number">${prevDayNum}</span>
        </div>
      </div>`;
  }

  // 2. Days of the current month
  for (let d = 1; d <= totalDays; d++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    const isToday = (isCurrentMonthYear && d === todayDate);

    // Filter events for this date
    const dayEvents = events.filter(e => e.date === dateStr);

    html += `
      <div class="calendar-day-cell ${isToday ? 'is-today' : ''}" data-date="${dateStr}">
        <div class="calendar-day-top">
          <span class="calendar-day-number">${d}</span>
          <a href="/maintenance/create?date=${dateStr}" class="calendar-add-event-btn" title="Plan maintenance for this date">
            <i class="fa-solid fa-plus"></i>
          </a>
        </div>
        <div class="calendar-events-list">`;

    dayEvents.forEach(ev => {
      const bgRgba = hexToRgba(ev.color || '#3b82f6', 0.12);
      const borderRgba = hexToRgba(ev.color || '#3b82f6', 0.35);

      html += `
        <div class="calendar-event-chip" 
             style="background: ${bgRgba}; color: ${ev.color}; border: 1px solid ${borderRgba};"
             data-event-id="${ev.id}"
             title="${ev.code}: ${ev.title}">
          <span class="event-dot" style="background: ${ev.color};"></span>
          <span class="event-time">${ev.time}</span>
          <span class="event-title" style="overflow:hidden; text-overflow:ellipsis;">${ev.title}</span>
        </div>`;
    });

    html += `
        </div>
      </div>`;
  }

  // 3. Leading days of next month to complete the 7-column grid
  const remainingCells = 42 - (startDayIndex + totalDays);
  if (remainingCells < 7 && remainingCells > 0) {
    for (let nextDay = 1; nextDay <= remainingCells; nextDay++) {
      html += `
        <div class="calendar-day-cell other-month">
          <div class="calendar-day-top">
            <span class="calendar-day-number">${nextDay}</span>
          </div>
        </div>`;
    }
  }

  calGrid.innerHTML = html;

  // Attach event click modal listeners
  calGrid.querySelectorAll('.calendar-event-chip').forEach(chip => {
    chip.addEventListener('click', (e) => {
      e.stopPropagation();
      const evId = chip.getAttribute('data-event-id');
      const eventData = events.find(ev => String(ev.id) === String(evId));
      if (eventData) {
        openEventModal(eventData);
      }
    });
  });
}

/* ============================================================
   5. CALENDAR EVENT DETAIL MODAL
   ============================================================ */
function initEventModal() {
  const backdrop = document.getElementById('cal-event-modal');
  if (!backdrop) return;

  const closeBtns = backdrop.querySelectorAll('.cal-modal-close, [data-modal-dismiss]');
  closeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      backdrop.classList.remove('is-open');
    });
  });

  backdrop.addEventListener('click', (e) => {
    if (e.target === backdrop) {
      backdrop.classList.remove('is-open');
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && backdrop.classList.contains('is-open')) {
      backdrop.classList.remove('is-open');
    }
  });
}

function openEventModal(event) {
  const backdrop = document.getElementById('cal-event-modal');
  if (!backdrop) return;

  const modalCode = document.getElementById('modal-event-code');
  const modalPriority = document.getElementById('modal-event-priority');
  const modalTitle = document.getElementById('modal-event-title');
  const modalEquip = document.getElementById('modal-event-equipment');
  const modalDateTime = document.getElementById('modal-event-datetime');
  const modalRecurrence = document.getElementById('modal-event-recurrence');
  const modalAssigned = document.getElementById('modal-event-assigned');
  const modalStatus = document.getElementById('modal-event-status');
  const modalViewBtn = document.getElementById('modal-view-plan-btn');

  if (modalCode) modalCode.textContent = event.code;
  if (modalTitle) modalTitle.textContent = event.title;
  if (modalEquip) modalEquip.textContent = `${event.equipment} ${event.room ? '(' + event.room + ')' : ''}`;
  if (modalDateTime) modalDateTime.textContent = `${event.date} at ${event.time} (${event.duration})`;
  if (modalRecurrence) modalRecurrence.textContent = event.recurrence;
  if (modalAssigned) modalAssigned.textContent = event.assigned;
  if (modalStatus) modalStatus.innerHTML = `<span class="badge badge-${event.status}">${event.status.toUpperCase().replace('_', ' ')}</span>`;

  if (modalPriority) {
    modalPriority.textContent = event.priority.toUpperCase();
    modalPriority.className = `badge badge-priority-${event.priority}`;
  }

  if (modalViewBtn) {
    modalViewBtn.href = `/maintenance/show?id=${event.id}`;
  }

  backdrop.classList.add('is-open');
}

/* ============================================================
   HELPERS (Toast & Color Conversion)
   ============================================================ */
function hexToRgba(hex, alpha = 1.0) {
  let clean = hex.replace('#', '').trim();
  if (clean.length === 3) {
    clean = clean.split('').map(c => c + c).join('');
  }
  const r = parseInt(clean.substring(0, 2), 16) || 0;
  const g = parseInt(clean.substring(2, 4), 16) || 0;
  const b = parseInt(clean.substring(4, 6), 16) || 0;
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function showMaintenanceToast(type, title, message) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast-popup toast-${type}`;

  const iconMap = {
    success: 'fa-solid fa-circle-check',
    danger: 'fa-solid fa-circle-exclamation',
    warning: 'fa-solid fa-triangle-exclamation',
    info: 'fa-solid fa-circle-info'
  };

  toast.innerHTML = `
    <i class="${iconMap[type] || iconMap.info} toast-icon"></i>
    <div class="toast-body">
      <div class="toast-title">${title}</div>
      <div class="toast-msg">${message}</div>
    </div>
    <button class="toast-close">&times;</button>
    <div class="toast-progress">
      <div class="toast-progress-bar" style="animation-duration: 3500ms;"></div>
    </div>
  `;

  container.appendChild(toast);

  toast.querySelector('.toast-close').addEventListener('click', () => {
    toast.classList.add('toast-hiding');
    setTimeout(() => toast.remove(), 300);
  });

  setTimeout(() => {
    if (toast.parentElement) {
      toast.classList.add('toast-hiding');
      setTimeout(() => toast.remove(), 300);
    }
  }, 3500);
}

/* ============================================================
   7. LIVE CATEGORY-TO-EQUIPMENT FILTER & MULTI-SELECTOR
   ============================================================ */
function initEquipmentCategoryFilter() {
  const categorySelect = document.getElementById('maint-category-select');
  const searchInput = document.getElementById('equipment-search-input');
  const items = document.querySelectorAll('.equipment-check-item');
  const selectedCountBadge = document.getElementById('equipment-selected-count');
  const visibleCountBadge = document.getElementById('equipment-visible-count');
  const selectAllBtn = document.getElementById('btn-select-all-equipment');
  const deselectAllBtn = document.getElementById('btn-deselect-all-equipment');
  const emptyPlaceholder = document.getElementById('equipment-empty-filtered');

  if (!items.length) return;

  function filterEquipment() {
    const selectedCatId = categorySelect ? categorySelect.value.trim() : '';
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    let visibleCount = 0;

    items.forEach(item => {
      const itemCatId = (item.getAttribute('data-category-id') || '').trim();
      const searchText = (item.getAttribute('data-search-text') || item.textContent).toLowerCase();

      const matchesCat = !selectedCatId || itemCatId === selectedCatId;
      const matchesSearch = !query || searchText.includes(query);

      if (matchesCat && matchesSearch) {
        item.classList.remove('is-filtered-out');
        item.style.display = '';
        visibleCount++;
      } else {
        item.classList.add('is-filtered-out');
        item.style.display = 'none';
      }
    });

    if (visibleCountBadge) {
      visibleCountBadge.textContent = visibleCount;
    }

    if (emptyPlaceholder) {
      emptyPlaceholder.style.display = (visibleCount === 0) ? 'flex' : 'none';
    }

    updateSelectedCount();
  }

  function updateSelectedCount() {
    if (!selectedCountBadge) return;
    const checked = document.querySelectorAll('.equipment-check-item input[type="checkbox"]:checked');
    selectedCountBadge.textContent = checked.length;
  }

  if (categorySelect) {
    categorySelect.addEventListener('change', filterEquipment);
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterEquipment);
  }

  items.forEach(item => {
    const cb = item.querySelector('input[type="checkbox"]');
    if (cb) {
      cb.addEventListener('change', () => {
        if (cb.checked) {
          item.classList.add('is-checked');
        } else {
          item.classList.remove('is-checked');
        }
        updateSelectedCount();
      });
      if (cb.checked) item.classList.add('is-checked');
    }
  });

  if (selectAllBtn) {
    selectAllBtn.addEventListener('click', (e) => {
      e.preventDefault();
      items.forEach(item => {
        if (!item.classList.contains('is-filtered-out') && item.style.display !== 'none') {
          const cb = item.querySelector('input[type="checkbox"]');
          if (cb) {
            cb.checked = true;
            item.classList.add('is-checked');
          }
        }
      });
      updateSelectedCount();
    });
  }

  if (deselectAllBtn) {
    deselectAllBtn.addEventListener('click', (e) => {
      e.preventDefault();
      items.forEach(item => {
        const cb = item.querySelector('input[type="checkbox"]');
        if (cb) {
          cb.checked = false;
          item.classList.remove('is-checked');
        }
      });
      updateSelectedCount();
    });
  }

  // Initial filter run
  filterEquipment();
  updateSelectedCount();
}

/* ============================================================
   8. FILE ATTACHMENTS & UPLOAD / DELETE HANDLER
   ============================================================ */
function initAttachmentManager() {
  const dropBox = document.getElementById('attachment-drop-box');
  const fileInput = document.getElementById('maint-file-input');
  const stagedList = document.getElementById('staged-files-list');

  let stagedFiles = [];

  if (fileInput) {
    fileInput.addEventListener('change', () => {
      if (fileInput.files) {
        for (let i = 0; i < fileInput.files.length; i++) {
          stagedFiles.push(fileInput.files[i]);
        }
        syncFileInput();
        renderStagedFiles();
      }
    });
  }

  if (dropBox) {
    ['dragenter', 'dragover'].forEach(eventName => {
      dropBox.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropBox.classList.add('drag-over');
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropBox.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropBox.classList.remove('drag-over');
      }, false);
    });

    dropBox.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      if (dt && dt.files && dt.files.length) {
        for (let i = 0; i < dt.files.length; i++) {
          stagedFiles.push(dt.files[i]);
        }
        syncFileInput();
        renderStagedFiles();
      }
    });
  }

  function syncFileInput() {
    if (!fileInput) return;
    try {
      const dt = new DataTransfer();
      stagedFiles.forEach(f => dt.items.add(f));
      fileInput.files = dt.files;
    } catch (err) {
      console.warn('DataTransfer sync fallback', err);
    }
  }

  function renderStagedFiles() {
    if (!stagedList) return;
    stagedList.innerHTML = '';

    if (stagedFiles.length === 0) {
      stagedList.style.display = 'none';
      return;
    }

    stagedList.style.display = 'grid';

    stagedFiles.forEach((file, index) => {
      const card = document.createElement('div');
      card.className = 'staged-file-chip';
      const icon = getFileIcon(file.name);

      card.innerHTML = `
        <i class="${icon.class}" style="color: ${icon.color}; font-size: 1.1rem;"></i>
        <div class="staged-file-meta">
          <span class="staged-file-name" title="${file.name}">${file.name}</span>
          <span class="staged-file-size">${formatBytes(file.size)}</span>
        </div>
        <button type="button" class="btn-remove-staged" title="Remove file" data-index="${index}">&times;</button>
      `;

      card.querySelector('.btn-remove-staged').addEventListener('click', (e) => {
        e.stopPropagation();
        stagedFiles.splice(index, 1);
        syncFileInput();
        renderStagedFiles();
      });

      stagedList.appendChild(card);
    });
  }

  // Handle deletion of existing attachments via AJAX with fallback
  document.querySelectorAll('.btn-delete-attachment').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const form = btn.closest('form');
      const card = btn.closest('.attachment-file-card');
      const attachmentId = btn.getAttribute('data-id') || (form ? form.querySelector('input[name="id"]')?.value : null);
      const planId = btn.getAttribute('data-plan-id') || (form ? form.querySelector('input[name="plan_id"]')?.value : null);

      if (!confirm('Are you sure you want to permanently delete this attachment file?')) {
        return;
      }

      if (attachmentId && planId) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        try {
          const formData = new FormData();
          formData.append('id', attachmentId);
          formData.append('plan_id', planId);

          const res = await fetch('/maintenance/attachments/delete', {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
          });

          const data = await res.json();
          if (data && data.success) {
            showMaintenanceToast('success', 'File Deleted', data.message || 'File deleted successfully.');
            if (card) {
              card.style.transition = 'all 0.3s ease';
              card.style.opacity = '0';
              card.style.transform = 'scale(0.9)';
              setTimeout(() => {
                card.remove();
                const grid = document.querySelector('.attachment-files-grid');
                if (grid && !grid.children.length) {
                  grid.innerHTML = '<div style="color: #94a3b8; font-style: italic; grid-column: 1 / -1;">No attached files remaining.</div>';
                }
              }, 300);
            }
          } else {
            showMaintenanceToast('danger', 'Error', (data && data.message) ? data.message : 'Could not delete attachment.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-trash"></i>';
          }
        } catch (err) {
          console.error(err);
          if (form) form.submit();
        }
      } else if (form) {
        form.submit();
      }
    });
  });
}

function getFileIcon(filename) {
  const ext = (filename || '').split('.').pop().toLowerCase();
  switch (ext) {
    case 'pdf':
      return { class: 'fa-solid fa-file-pdf', color: '#ef4444' };
    case 'doc':
    case 'docx':
      return { class: 'fa-solid fa-file-word', color: '#2563eb' };
    case 'xls':
    case 'xlsx':
    case 'csv':
      return { class: 'fa-solid fa-file-excel', color: '#10b981' };
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'webp':
      return { class: 'fa-solid fa-file-image', color: '#8b5cf6' };
    case 'zip':
    case 'tar':
    case 'gz':
    case 'rar':
      return { class: 'fa-solid fa-file-zipper', color: '#f59e0b' };
    default:
      return { class: 'fa-solid fa-file-lines', color: '#64748b' };
  }
}

function formatBytes(bytes, decimals = 1) {
  if (!bytes || bytes === 0) return '0 Bytes';
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

/* ============================================================
   8. LIVE SEARCH (Table & Kanban)
   ============================================================ */
function initLiveSearch() {
  const searchInput = document.getElementById('maint-search-input');
  if (!searchInput) return;

  function filterContent(query) {
    const raw = (query || '').trim().toLowerCase();
    const words = raw ? raw.split(/\s+/).filter(Boolean) : [];

    // 1. Table rows filtering
    const tableRows = document.querySelectorAll('#maint-view-table tbody tr');
    let visibleRowCount = 0;
    let actualRows = 0;

    tableRows.forEach(row => {
      if (row.id === 'maint-table-empty-row') return;
      if (row.querySelector('td[colspan]')) {
        // Initial server-side empty placeholder
        return;
      }
      actualRows++;
      const text = row.textContent.toLowerCase();
      const match = words.length === 0 || words.every(w => text.includes(w));
      if (match) {
        row.style.display = '';
        visibleRowCount++;
      } else {
        row.style.display = 'none';
      }
    });

    // Dynamic empty state for table if filtered to zero
    let emptyRow = document.getElementById('maint-table-empty-row');
    const tbody = document.querySelector('#maint-view-table tbody');
    if (tbody && actualRows > 0) {
      if (visibleRowCount === 0 && words.length > 0) {
        if (!emptyRow) {
          emptyRow = document.createElement('tr');
          emptyRow.id = 'maint-table-empty-row';
          tbody.appendChild(emptyRow);
        }
        const safeQuery = raw.replace(/[<>&"']/g, c => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;' })[c]);
        emptyRow.innerHTML = `
          <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-secondary);">
            <i class="fa-solid fa-magnifying-glass" style="font-size: 2rem; opacity: 0.4; margin-bottom: 8px; display: block;"></i>
            No preventive maintenance plans matching "<strong>${safeQuery}</strong>".
          </td>`;
        emptyRow.style.display = '';
      } else if (emptyRow) {
        emptyRow.style.display = 'none';
      }
    }

    // 2. Kanban cards filtering
    const kanbanColumns = document.querySelectorAll('#maint-view-kanban .kanban-column');
    kanbanColumns.forEach(col => {
      const cards = col.querySelectorAll('.kanban-card');
      let visibleInCol = 0;
      cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        const match = words.length === 0 || words.every(w => text.includes(w));
        if (match) {
          card.style.display = '';
          visibleInCol++;
        } else {
          card.style.display = 'none';
        }
      });

      const countBadge = col.querySelector('.kanban-col-count');
      if (countBadge) {
        countBadge.textContent = visibleInCol;
      }
    });
  }

  // Real-time keystroke filtering
  searchInput.addEventListener('input', (e) => {
    filterContent(e.target.value);
  });

  // ESC to reset input
  searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      searchInput.value = '';
      filterContent('');
    }
  });

  // If page loaded with initial search query, trigger live filter
  if (searchInput.value.trim() !== '') {
    filterContent(searchInput.value);
  }
}

