/**
 * PSU Research Project Management System
 * Main JavaScript — app.js
 * Bootstrap 5.3 + DataTables + Chart.js + SweetAlert2
 */

'use strict';

// ====================================================================
// CSRF TOKEN
// Read from <meta name="csrf-token" content="...">
// ====================================================================
const PSU = window.PSU || {};

PSU.csrfToken = (function () {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
})();

// Inject CSRF token into all AJAX requests globally
(function () {
    const origOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (...args) {
        origOpen.apply(this, args);
        this.setRequestHeader('X-CSRF-Token', PSU.csrfToken);
        this.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    };
})();

// Also add to fetch() calls via a wrapper (use PSU.fetch for internal calls)
PSU.fetch = function (url, options = {}) {
    const defaults = {
        headers: {
            'X-CSRF-Token': PSU.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        },
    };
    options.headers = Object.assign({}, defaults.headers, options.headers || {});
    return fetch(url, options);
};

// ====================================================================
// DATATABLES THAI LANGUAGE PACK (inline — no CDN)
// ====================================================================
window.DataTablesThaiLang = {
    sEmptyTable:     "ไม่มีข้อมูลในตาราง",
    sInfo:           "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
    sInfoEmpty:      "แสดง 0 ถึง 0 จาก 0 รายการ",
    sInfoFiltered:   "(กรองจากทั้งหมด _MAX_ รายการ)",
    sInfoThousands:  ",",
    sLengthMenu:     "แสดง _MENU_ รายการต่อหน้า",
    sLoadingRecords: "กำลังโหลด...",
    sProcessing:     "<i class='bi bi-hourglass-split'></i> กำลังประมวลผล...",
    sSearch:         "ค้นหา:",
    sSearchPlaceholder: "พิมพ์เพื่อค้นหา...",
    sZeroRecords:    "ไม่พบข้อมูลที่ตรงกัน",
    oPaginate: {
        sFirst:    '<i class="bi bi-chevron-double-left"></i>',
        sPrevious: '<i class="bi bi-chevron-left"></i>',
        sNext:     '<i class="bi bi-chevron-right"></i>',
        sLast:     '<i class="bi bi-chevron-double-right"></i>',
    },
    oAria: {
        sSortAscending:  ": เรียงลำดับจากน้อยไปมาก",
        sSortDescending: ": เรียงลำดับจากมากไปน้อย",
    },
    select: {
        rows: { _: "เลือก %d แถว", 0: "", 1: "เลือก 1 แถว" },
    },
    buttons: {
        copy:       "คัดลอก",
        copyTitle:  "คัดลอกไปยังคลิปบอร์ด",
        copySuccess: { _: "คัดลอกแล้ว %d แถว", 1: "คัดลอกแล้ว 1 แถว" },
        csv:        "CSV",
        excel:      "Excel",
        pdf:        "PDF",
        print:      "พิมพ์",
        colvis:     "คอลัมน์",
    },
};

// ====================================================================
// DOCUMENT READY
// ====================================================================
document.addEventListener('DOMContentLoaded', function () {

    // ------------------------------------------------------------------
    // 1. SIDEBAR TOGGLE
    // ------------------------------------------------------------------
    initSidebar();

    // ------------------------------------------------------------------
    // 2. FLASH MESSAGE AUTO-DISMISS
    // ------------------------------------------------------------------
    initFlashMessages();

    // ------------------------------------------------------------------
    // 3. BOOTSTRAP TOOLTIPS & POPOVERS
    // ------------------------------------------------------------------
    initTooltipsAndPopovers();

    // ------------------------------------------------------------------
    // 4. FORM CHANGE WARNING
    // ------------------------------------------------------------------
    initFormChangeWarning();

    // ------------------------------------------------------------------
    // 5. FILE UPLOAD PREVIEW
    // ------------------------------------------------------------------
    initFileUpload();

    // ------------------------------------------------------------------
    // 6. CO-INVESTIGATOR DYNAMIC ROWS
    // ------------------------------------------------------------------
    initCoInvestigators();

    // ------------------------------------------------------------------
    // 7. FIELD OF STUDY → FACULTY AUTO-FILL
    // ------------------------------------------------------------------
    initFieldFacultyAutofill();

    // ------------------------------------------------------------------
    // 8. PROGRESS SLIDER DISPLAY
    // ------------------------------------------------------------------
    initProgressSliders();

    // ------------------------------------------------------------------
    // 9. BUDGET FORMATTER
    // ------------------------------------------------------------------
    initBudgetFormatters();

    // ------------------------------------------------------------------
    // 10. CHART.JS DEFAULT THEME
    // ------------------------------------------------------------------
    initChartDefaults();

    // ------------------------------------------------------------------
    // 11. NOTIFICATION BELL POLL
    // ------------------------------------------------------------------
    initNotificationBell();

    // ------------------------------------------------------------------
    // 12. DATATABLES (default init for any table with class .dt-table)
    // ------------------------------------------------------------------
    initDataTables();

});

// ====================================================================
// SIDEBAR TOGGLE (hamburger) with localStorage persistence
// ====================================================================
function initSidebar() {
    const SIDEBAR_KEY = 'psu_sidebar_collapsed';
    const body        = document.body;
    const toggleBtn   = document.getElementById('btnSidebarToggle');
    const sidebar     = document.getElementById('sidebar');

    if (!sidebar) return;

    // Restore state
    if (localStorage.getItem(SIDEBAR_KEY) === '1') {
        body.classList.add('sidebar-collapsed');
    }

    // Desktop toggle
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const isCollapsed = body.classList.toggle('sidebar-collapsed');
            localStorage.setItem(SIDEBAR_KEY, isCollapsed ? '1' : '0');
        });
    }

    // Mobile overlay
    let overlay = document.getElementById('sidebarOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sidebarOverlay';
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    // Mobile toggle (same button at small screens)
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (window.innerWidth < 992) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            }
        });
    }

    overlay.addEventListener('click', function () {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
    });

    // Submenu toggles
    document.querySelectorAll('.sidebar-submenu-toggle').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const submenu  = document.getElementById(targetId);
            if (!submenu) return;

            const isOpen = submenu.classList.contains('open');
            // Close all
            document.querySelectorAll('.sidebar-submenu.open').forEach(function (sm) {
                sm.classList.remove('open');
                const parentLink = sm.previousElementSibling;
                if (parentLink) parentLink.setAttribute('aria-expanded', 'false');
            });
            // Open clicked if it was closed
            if (!isOpen) {
                submenu.classList.add('open');
                this.setAttribute('aria-expanded', 'true');
            } else {
                this.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // Auto-open submenu for active links
    document.querySelectorAll('.sidebar-submenu .sidebar-link.active').forEach(function (activeLink) {
        const submenu = activeLink.closest('.sidebar-submenu');
        if (submenu) {
            submenu.classList.add('open');
            const parentLink = submenu.previousElementSibling;
            if (parentLink) parentLink.setAttribute('aria-expanded', 'true');
        }
    });
}

// ====================================================================
// FLASH MESSAGE AUTO-DISMISS (4 seconds with fade)
// ====================================================================
function initFlashMessages() {
    document.querySelectorAll('.flash-alert').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity    = '0';
            alert.style.transform  = 'translateY(-10px)';
            setTimeout(function () { alert.remove(); }, 500);
        }, 4000);
    });
}

// ====================================================================
// BOOTSTRAP TOOLTIPS & POPOVERS
// ====================================================================
function initTooltipsAndPopovers() {
    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
    // Popovers
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el);
    });
}

// ====================================================================
// FORM CHANGE WARNING
// Warn user before navigating away from unsaved forms
// ====================================================================
function initFormChangeWarning() {
    const forms = document.querySelectorAll('form[data-warn-unsaved]');
    forms.forEach(function (form) {
        let formChanged = false;

        form.addEventListener('change', function () { formChanged = true; });
        form.addEventListener('input',  function () { formChanged = true; });
        form.addEventListener('submit', function () { formChanged = false; }); // Clear on submit

        window.addEventListener('beforeunload', function (e) {
            if (formChanged) {
                const msg = 'คุณมีข้อมูลที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?';
                e.preventDefault();
                e.returnValue = msg;
                return msg;
            }
        });
    });
}

// ====================================================================
// FILE UPLOAD PREVIEW (PDF only, max 10MB)
// ====================================================================
function initFileUpload() {
    document.querySelectorAll('.file-dropzone input[type="file"]').forEach(function (input) {
        const dropzone   = input.closest('.file-dropzone');
        const previewDiv = document.getElementById(input.dataset.preview);
        const MAX_SIZE   = 10 * 1024 * 1024; // 10MB

        function handleFiles(files) {
            if (!files || files.length === 0) return;
            const file = files[0];

            if (file.type !== 'application/pdf') {
                showAlert('กรุณาอัปโหลดไฟล์ PDF เท่านั้น', 'danger');
                input.value = '';
                return;
            }
            if (file.size > MAX_SIZE) {
                showAlert(`ขนาดไฟล์ต้องไม่เกิน 10 MB (ไฟล์ที่เลือก: ${(file.size / 1024 / 1024).toFixed(2)} MB)`, 'danger');
                input.value = '';
                return;
            }

            if (previewDiv) {
                previewDiv.innerHTML = `
                    <div class="file-preview-item mt-2">
                        <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                        <span class="text-truncate">${escHtml(file.name)}</span>
                        <span class="text-muted">(${(file.size / 1024).toFixed(1)} KB)</span>
                        <button type="button" class="btn-remove-file" title="ลบ">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>`;
                previewDiv.querySelector('.btn-remove-file').addEventListener('click', function () {
                    input.value = '';
                    previewDiv.innerHTML = '';
                    dropzone.classList.remove('has-file');
                });
            }
            dropzone.classList.add('has-file');
        }

        input.addEventListener('change', function () { handleFiles(this.files); });

        // Drag & drop
        ['dragenter', 'dragover'].forEach(function (evt) {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                dropzone.classList.remove('dragover');
                if (evt === 'drop') handleFiles(e.dataTransfer.files);
            });
        });
    });
}

// ====================================================================
// CO-INVESTIGATOR DYNAMIC ROWS
// ====================================================================
function initCoInvestigators() {
    const container = document.getElementById('coinvestigatorsContainer');
    const btnAdd    = document.getElementById('btnAddCoinvestigator');
    if (!container || !btnAdd) return;

    let rowIndex = container.querySelectorAll('.coinvestigator-row').length;

    btnAdd.addEventListener('click', function () {
        rowIndex++;
        const row = document.createElement('div');
        row.className = 'coinvestigator-row';
        row.dataset.index = rowIndex;
        row.innerHTML = `
            <button type="button" class="btn-remove-coinvestigator" title="ลบ">
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label form-label-sm">ชื่อ-นามสกุล</label>
                    <input type="text" class="form-control form-control-sm"
                        name="coinvestigators[${rowIndex}][name]"
                        placeholder="ชื่อ นักวิจัยร่วม">
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">หน่วยงาน / คณะ</label>
                    <input type="text" class="form-control form-control-sm"
                        name="coinvestigators[${rowIndex}][department]"
                        placeholder="คณะ / หน่วยงาน">
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">ร้อยละการมีส่วนร่วม</label>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control"
                            name="coinvestigators[${rowIndex}][percentage]"
                            min="1" max="100" placeholder="0">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>`;
        container.appendChild(row);
        row.querySelector('.btn-remove-coinvestigator').addEventListener('click', function () {
            row.remove();
        });
        row.querySelector('input[type="text"]').focus();
    });

    // Handle existing remove buttons
    container.querySelectorAll('.btn-remove-coinvestigator').forEach(function (btn) {
        btn.addEventListener('click', function () {
            this.closest('.coinvestigator-row').remove();
        });
    });
}

// ====================================================================
// FIELD OF STUDY → FACULTY AUTO-FILL
// ====================================================================
function initFieldFacultyAutofill() {
    const fieldSelect   = document.getElementById('fieldOfStudySelect');
    const facultyInput  = document.getElementById('facultyAutoFill');
    if (!fieldSelect || !facultyInput) return;

    // Build map from data attributes on <option>s
    fieldSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const faculty  = selected ? (selected.dataset.faculty || '') : '';
        facultyInput.value = faculty;
    });
}

// ====================================================================
// PROGRESS SLIDER — show current value
// ====================================================================
function initProgressSliders() {
    document.querySelectorAll('input[type="range"].progress-slider').forEach(function (slider) {
        const displayId = slider.dataset.display;
        const display   = displayId ? document.getElementById(displayId) : null;

        function update() {
            const val = slider.value;
            if (display) display.textContent = val + '%';
            // Update background gradient
            const pct = ((val - slider.min) / (slider.max - slider.min)) * 100;
            slider.style.background = `linear-gradient(to right, #0066CC ${pct}%, #e2e8f0 ${pct}%)`;
        }

        slider.addEventListener('input', update);
        update(); // Init on load
    });
}

// ====================================================================
// BUDGET FORMATTER (comma-separated Thai baht display)
// ====================================================================
function initBudgetFormatters() {
    // Auto-format budget inputs on blur
    document.querySelectorAll('input.budget-input').forEach(function (input) {
        input.addEventListener('blur', function () {
            const raw = parseFloat(this.value.replace(/,/g, '')) || 0;
            this.value = formatBaht(raw);
        });
        input.addEventListener('focus', function () {
            this.value = this.value.replace(/,/g, '');
        });
    });
}

/**
 * Format number as Thai baht with commas
 * @param {number} amount
 * @returns {string}
 */
PSU.formatBaht = function (amount) {
    return formatBaht(amount);
};
function formatBaht(amount) {
    return new Intl.NumberFormat('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

// ====================================================================
// CHART.JS DEFAULT THEME (PSU Blue Palette)
// ====================================================================
function initChartDefaults() {
    if (typeof Chart === 'undefined') return;

    const PSU_COLORS = [
        '#003B6D', '#0066CC', '#0099FF', '#60b3ff',
        '#002244', '#0052a3', '#3388d8', '#99ccff',
        '#16a34a', '#d97706', '#dc2626', '#7c3aed',
    ];

    Chart.defaults.font.family = "'Sarabun', 'Noto Sans Thai', Arial, sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = '#64748b';
    Chart.defaults.responsive  = true;
    Chart.defaults.maintainAspectRatio = false;

    // Default plugin settings
    Chart.defaults.plugins.legend.position  = 'bottom';
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.font = { size: 11 };
    Chart.defaults.plugins.tooltip.backgroundColor = '#1e293b';
    Chart.defaults.plugins.tooltip.titleFont        = { size: 12, weight: 'bold' };
    Chart.defaults.plugins.tooltip.bodyFont         = { size: 11 };
    Chart.defaults.plugins.tooltip.padding          = 10;
    Chart.defaults.plugins.tooltip.cornerRadius     = 6;

    // Store palette for manual use
    PSU.chartColors = PSU_COLORS;
}

// ====================================================================
// NOTIFICATION BELL — Poll every 5 minutes
// ====================================================================
function initNotificationBell() {
    const badge = document.getElementById('notificationBadge');
    if (!badge) return;

    function pollUnreadCount() {
        fetch('/research/notifications/unread-count', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': PSU.csrfToken,
            },
        })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (data) {
            const count = parseInt(data.count) || 0;
            if (count === 0) {
                badge.style.display = 'none';
                badge.textContent   = '0';
            } else {
                badge.style.display = '';
                badge.textContent   = count > 99 ? '99+' : count;
            }
        })
        .catch(function () {
            // Silently fail — don't spam console
        });
    }

    pollUnreadCount();
    setInterval(pollUnreadCount, 5 * 60 * 1000); // Every 5 minutes
}

// ====================================================================
// DATATABLES — default initialization for .dt-table elements
// ====================================================================
function initDataTables() {
    if (typeof $.fn.DataTable === 'undefined') return;

    document.querySelectorAll('table.dt-table:not(.dataTable)').forEach(function (table) {
        const opts = {
            language:    window.DataTablesThaiLang,
            pageLength:  parseInt(table.dataset.pageLength) || 25,
            responsive:  true,
            order:       JSON.parse(table.dataset.order || '[[0,"asc"]]'),
        };
        $(table).DataTable(opts);
    });
}

// ====================================================================
// SWEETALERT2 DELETE CONFIRMATION HELPER
// @param {HTMLFormElement|string} formOrId - form element or form ID
// @param {string} [message] - custom confirmation message
// ====================================================================
PSU.confirmDelete = function (formOrId, message) {
    const form = typeof formOrId === 'string'
        ? document.getElementById(formOrId)
        : formOrId;

    if (!form) {
        console.error('confirmDelete: form not found', formOrId);
        return false;
    }

    const defaultMsg = form.dataset.confirmMessage || 'ต้องการลบรายการนี้ใช่หรือไม่?<br><small class="text-muted">การดำเนินการนี้ไม่สามารถย้อนกลับได้</small>';
    const msg = message || defaultMsg;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title:              'ยืนยันการลบ',
            html:               msg,
            icon:               'warning',
            showCancelButton:   true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor:  '#6b7280',
            confirmButtonText:  '<i class="bi bi-trash me-1"></i> ลบ',
            cancelButtonText:   'ยกเลิก',
            reverseButtons:     true,
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    } else {
        if (confirm(form.dataset.confirmMessage || 'ต้องการลบรายการนี้ใช่หรือไม่?')) {
            form.submit();
        }
    }
    return false; // Prevent default form submission
};

// Attach to all forms with data-confirm-delete
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-confirm-delete]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const formId = this.dataset.confirmDelete;
            PSU.confirmDelete(formId);
        });
    });
    document.querySelectorAll('form.form-confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            PSU.confirmDelete(form);
        });
    });
});

// ====================================================================
// STATUS CHANGE MODAL HELPER
// ====================================================================
PSU.openStatusModal = function (proposalId, currentStatus, proposalTitle) {
    const modal     = document.getElementById('statusChangeModal');
    const idInput   = modal ? modal.querySelector('#statusModalProposalId')   : null;
    const titleEl   = modal ? modal.querySelector('#statusModalProposalTitle') : null;
    const selectEl  = modal ? modal.querySelector('#statusModalNewStatus')     : null;

    if (!modal) return;
    if (idInput)  idInput.value    = proposalId;
    if (titleEl)  titleEl.textContent = proposalTitle;
    if (selectEl) selectEl.value   = currentStatus;

    bootstrap.Modal.getOrCreateInstance(modal).show();
};

// ====================================================================
// LOADING OVERLAY HELPERS
// ====================================================================
PSU.showLoading = function () {
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(overlay);
    }
    requestAnimationFrame(function () { overlay.classList.add('active'); });
};
PSU.hideLoading = function () {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.remove('active');
};

// Show loading on form submit
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-loading]').forEach(function (form) {
        form.addEventListener('submit', function () {
            PSU.showLoading();
        });
    });
});

// ====================================================================
// EXPORT HELPERS (trigger download links)
// ====================================================================
PSU.exportExcel = function (url) {
    const a = document.createElement('a');
    a.href  = url;
    a.download = '';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
};
PSU.exportPdf = function (url) {
    window.open(url, '_blank');
};

// ====================================================================
// ALERT HELPER (Bootstrap toast/alert)
// ====================================================================
function showAlert(message, type = 'danger') {
    const container = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0 show`;
    toast.role = 'alert';
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${escHtml(message)}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}
function createToastContainer() {
    const div = document.createElement('div');
    div.id = 'toastContainer';
    div.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    div.style.zIndex = '9999';
    document.body.appendChild(div);
    return div;
}
PSU.showAlert = showAlert;

// ====================================================================
// ESCAPE HTML HELPER
// ====================================================================
function escHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}
PSU.escHtml = escHtml;

// ====================================================================
// EXPOSE PSU NAMESPACE
// ====================================================================
window.PSU = PSU;
