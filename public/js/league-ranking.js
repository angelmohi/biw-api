document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('rankingTable');
    
    if (!table) {
        return;
    }
    
    const headers = table.querySelectorAll('th.sortable');
    let currentSort = { column: null, direction: null };

    headers.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-column');
            let direction = 'asc';

            if (currentSort.column === column && currentSort.direction === 'asc') {
                direction = 'desc';
            }

            headers.forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });

            this.classList.add(direction === 'asc' ? 'sorted-asc' : 'sorted-desc');

            currentSort = { column, direction };

            sortTable(column, direction);
        });
    });

    function sortTable(column, direction) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            let aVal, bVal;

            if (column === 'name') {
                aVal = a.getAttribute('data-name').toLowerCase();
                bVal = b.getAttribute('data-name').toLowerCase();
                return direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            } else {
                aVal = parseFloat(a.getAttribute('data-' + column.replace('_', '-'))) || 0;
                bVal = parseFloat(b.getAttribute('data-' + column.replace('_', '-'))) || 0;
                return direction === 'asc' ? aVal - bVal : bVal - aVal;
            }
        });

        rows.forEach(row => tbody.appendChild(row));
    }
});

$(document).ready(function() {
    console.log('Update league script loaded');
    
    $('#update-league-btn').closest('form').on('submit', function(e) {
        console.log('Form submit detected');
        
        const updateBtn = $('#update-league-btn');
        const btnText = updateBtn.find('.btn-text');
        const btnLoading = updateBtn.find('.btn-loading');
        
        console.log('Elements found:', {
            updateBtn: updateBtn.length,
            btnText: btnText.length,
            btnLoading: btnLoading.length
        });
        
        updateBtn.prop('disabled', true);
        btnText.addClass('d-none');
        btnLoading.removeClass('d-none');
        
        console.log('Update button spinner activated');
    });
    
    $('#update-league-btn').on('click', function(e) {
        console.log('Update button click detected');
        
        const updateBtn = $(this);
        const btnText = updateBtn.find('.btn-text');
        const btnLoading = updateBtn.find('.btn-loading');
        
        setTimeout(function() {
            updateBtn.prop('disabled', true);
            btnText.addClass('d-none');
            btnLoading.removeClass('d-none');
        }, 10);
    });
});
