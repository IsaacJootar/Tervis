<script>
    new DataTable('#dataTable', {
        layout: {
            topStart: {
                buttons: [{
                    text: '<i class="bx bx-download me-1"></i>Export Data',
                    className: 'btn btn-primary',
                    extend: 'collection',
                    buttons: [
                        'copyHtml5',
                        'excelHtml5',
                        'csvHtml5',
                        'pdfHtml5'
                    ]
                }]
            },
            topEnd: {
                pageLength: true,
                search: true
            },
            bottomStart: {
                info: true
            },
            bottomEnd: {
                paging: true
            }
        },
        responsive: true,
        order: [
            [0, 'desc']
        ],
        pageLength: 20,
        lengthMenu: [
            [10, 20, 25, 50, 100, -1],
            [10, 20, 25, 50, 100, "All"]
        ]
    });
</script>
