$(document).ready(function() {
    let transactionsTable;
    let isMobile = window.innerWidth < 992; // Bootstrap lg breakpoint
    
    // Initialize DataTable when transactions tab is shown (desktop)
    $('button[data-bs-target="#transactions"]').on('shown.bs.tab', function (e) {
        if (!transactionsTable && !isMobile) {
            initializeTransactionsTable();
        }
    });
    
    // Initialize mobile transactions when mobile tab is shown
    $('button[data-bs-target="#mobile-transactions"]').on('shown.bs.tab', function (e) {
        if (isMobile) {
            loadMobileTransactions();
        }
    });
    
    // Handle window resize
    $(window).on('resize', function() {
        const wasMobile = isMobile;
        isMobile = window.innerWidth < 992;
        
        // If switching between mobile and desktop, reload content if tab is active
        if (wasMobile !== isMobile) {
            if ($('button[data-bs-target="#transactions"]').hasClass('active') || $('button[data-bs-target="#mobile-transactions"]').hasClass('active')) {
                if (isMobile) {
                    if (transactionsTable) {
                        transactionsTable.destroy();
                        transactionsTable = null;
                    }
                    loadMobileTransactions();
                } else {
                    // Clean up any mobile search elements when switching to desktop
                    $('.mobile-search-container').remove();
                    $('#transactionsMobile').empty();
                    if (!transactionsTable) {
                        initializeTransactionsTable();
                    }
                }
            }
        }
    });
    
    function loadMobileTransactions() {
        const container = $('#transactionsMobile');
        container.html('<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>');
        
        $.ajax({
            url: window.location.pathname + '/transactions',
            type: 'GET',
            data: {
                mobile: true,
                length: 50 // Load more for mobile initially
            },
            success: function(response) {
                let html = '';
                
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(transaction) {
                        html += `
                            <div class="card mb-3 transaction-card">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="transaction-type">
                                            ${transaction[0]}
                                        </div>
                                        <div class="transaction-amount text-end">
                                            ${transaction[1]}
                                        </div>
                                    </div>
                                    ${transaction[2] ? `<div class="transaction-player">${transaction[2]}</div>` : ''}
                                    <div class="row text-muted small">
                                        ${transaction[3] ? `<div class="col-6"><strong>De:</strong> ${transaction[3]}</div>` : ''}
                                        ${transaction[4] ? `<div class="col-6"><strong>Para:</strong> ${transaction[4]}</div>` : ''}
                                    </div>
                                    <div class="transaction-date text-muted small mt-2">
                                        <i class="fas fa-clock me-1"></i>${transaction[5]}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html = `
                        <div class="text-center py-5">
                            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay transacciones</h5>
                            <p class="text-muted">Las transacciones aparecerán aquí cuando se realicen movimientos</p>
                        </div>
                    `;
                }
                
                container.html(html);
            },
            error: function() {
                container.html(`
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5 class="text-muted">Error al cargar transacciones</h5>
                        <p class="text-muted">No se pudieron cargar las transacciones</p>
                    </div>
                `);
            }
        });
    }
    
    function initializeTransactionsTable() {
        transactionsTable = $('#transactionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: window.location.pathname + '/transactions',
                type: 'GET'
            },
            columns: [
                { 
                    data: 0, 
                    name: 'type_id',
                    orderable: true,
                    searchable: true, // Enable search for transaction type
                    className: 'align-middle',
                    width: '15%'
                },
                { 
                    data: 1, 
                    name: 'amount',
                    orderable: true,
                    searchable: true,
                    className: 'align-middle text-end',
                    width: '12%'
                },
                { 
                    data: 2, 
                    name: 'player_name',
                    orderable: true,
                    searchable: true,
                    className: 'align-middle',
                    width: '25%',
                    orderDataType: 'dom-data-player', // Custom ordering by player name
                    type: 'string'
                },
                { 
                    data: 3, 
                    name: 'from_user_id',
                    orderable: true,
                    searchable: true,
                    className: 'align-middle',
                    width: '16%'
                },
                { 
                    data: 4, 
                    name: 'to_user_id',
                    orderable: true,
                    searchable: true,
                    className: 'align-middle',
                    width: '16%'
                },
                { 
                    data: 5, 
                    name: 'date',
                    orderable: true,
                    searchable: true, // Enable search for dates
                    className: 'align-middle text-center',
                    width: '16%'
                }
            ],
            order: [[5, 'desc']], // Order by date descending by default
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                "decimal": "",
                "emptyTable": "No hay transacciones disponibles",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ transacciones",
                "infoEmpty": "Mostrando 0 a 0 de 0 transacciones",
                "infoFiltered": "(filtrado de _MAX_ transacciones totales)",
                "infoPostFix": "",
                "thousands": ".",
                "lengthMenu": "Mostrar _MENU_ registros por página",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar en transacciones:",
                "searchPlaceholder": "Buscar...",
                "zeroRecords": "No se encontraron transacciones que coincidan con la búsqueda",
                "paginate": {
                    "first": "Primera",
                    "last": "Última",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "aria": {
                    "sortAscending": ": activar para ordenar la columna de manera ascendente",
                    "sortDescending": ": activar para ordenar la columna de manera descendente"
                }
            },
            scrollX: false,
            responsive: false,
            autoWidth: false,
            dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"t>>' +
                 '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-end"p>>',
            drawCallback: function() {
                // Add custom styling after each draw
                $('.dataTables_wrapper .table').addClass('table-hover');
                
                // Remove default pagination wrapper classes and add custom styling
                $('.dataTables_paginate').removeClass('pagination').addClass('custom-pagination');
                $('.dataTables_paginate .paginate_button').removeClass('page-link btn-custom-pagination');
                
                // Remove any unwanted container elements
                $('.dataTables_paginate .paging_simple_numbers').unwrap();
            },
            initComplete: function() {
                // Add custom classes after initialization
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm').attr('placeholder', 'Buscar transacciones...');
                
                // Add DataTables plugin to support ordering by data-player-name attribute
                $.fn.dataTable.ext.order['dom-data-player'] = function(settings, col) {
                    return this.api().column(col, {order:'index'}).nodes().map(function(td, i) {
                        var playerName = $(td).find('[data-player-name]').attr('data-player-name');
                        return playerName ? playerName.toLowerCase() : '-';
                    });
                };
            }
        });
    }
    
    // Clean up DataTable when tab is hidden
    $('button[data-bs-target="#transactions"]').on('hidden.bs.tab', function (e) {
        // Optionally destroy the table to free memory when not visible
        // if (transactionsTable) {
        //     transactionsTable.destroy();
        //     transactionsTable = null;
        // }
    });
});
