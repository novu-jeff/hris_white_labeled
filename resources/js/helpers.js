/**
 * Shared helpers used by app.js and livewire.js
 */

export function resetErrorsAndFields() {
    if (typeof $ !== 'undefined') {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
    }
}

export function removeRowDT(id) {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        const table = $('.dataTable').DataTable();
        if (table && table.row) {
            const row = table.row('#' + id);
            if (row) row.remove().draw();
        }
    }
}

export function reloadDT() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('.dataTable').each(function () {
            const dt = $(this).DataTable();
            if (dt && dt.ajax) dt.ajax.reload();
            else if (dt) dt.draw();
        });
    }
}

export function hideModal() {
    if (typeof $ !== 'undefined' && $.fn.modal) {
        $('.modal').modal('hide');
    }
}

export function ckeditor() {
    if (typeof ClassicEditor !== 'undefined' && document.querySelector('#ckeditor')) {
        ClassicEditor.create(document.querySelector('#ckeditor')).catch(() => {});
    }
}

export function reinitializeDataTable() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('.dataTable').each(function () {
            if ($.fn.DataTable.isDataTable(this)) {
                $(this).DataTable().destroy();
            }
            $(this).DataTable();
        });
    }
}

export function copy_link() {
    if (navigator.clipboard && window.location) {
        navigator.clipboard.writeText(window.location.href);
    }
}

export function formatTime(minutes) {
    if (minutes == null) return '—';
    const h = Math.floor(minutes / 60);
    const m = Math.round(minutes % 60);
    return (h ? h + 'h ' : '') + (m ? m + 'm' : '');
}

export function convertToHoursAndMinutes(value) {
    if (value == null) return { hours: 0, minutes: 0 };
    const n = parseFloat(value);
    const hours = Math.floor(n);
    const minutes = Math.round((n - hours) * 60);
    return { hours, minutes };
}

export function getGPSCoordinates() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation not supported'));
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
            reject,
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    });
}

export function setupMap(containerId, options = {}) {
    if (typeof $ !== 'undefined' && $(containerId).length) {
        $(containerId).attr('data-map-setup', '1');
    }
}
