$(document).ready(function() {
    let transactionsTable;
    
    // Initialize DataTable when transactions tab is shown
    $('button[data-bs-target="#transactions"]').on('shown.bs.tab', function (e) {
        if (!transactionsTable) {
            initializeTransactionsTable();
        }
    });
    
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
                    searchable: false,
                    className: 'align-middle',
                    width: '10%'
                },
                { 
                    data: 1, 
                    name: 'description',
                    orderable: true,
                    searchable: true,
                    className: 'align-middle',
                    width: '25%'
                },
                { 
                    data: 2, 
                    name: 'amount',
                    orderable: true,
                    searchable: true,
                    className: 'align-middle text-end',
                    width: '12%'
                },
                { 
                    data: 3, 
                    name: 'player_name',
                    orderable: true,
                    searchable: true,
                    className: 'align-middle',
                    width: '18%'
                },
                { 
                    data: 4, 
                    name: 'from_user_id',
                    orderable: true,
                    searchable: true,
                    className: 'align-middle',
                    width: '12%'
                },
                { 
                    data: 5, 
                    name: 'to_user_id',
                    orderable: true,
                    searchable: true,
                    className: 'align-middle',
                    width: '12%'
                },
                { 
                    data: 6, 
                    name: 'date',
                    orderable: true,
                    searchable: false,
                    className: 'align-middle text-center',
                    width: '11%'
                }
            ],
            order: [[6, 'desc']], // Order by date descending by default
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
