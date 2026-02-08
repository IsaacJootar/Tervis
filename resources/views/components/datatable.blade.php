{{-- resources/views/components/datatable.blade.php --}}
@props([
    'id' => 'datatable-' . uniqid(),
    'title' => 'DataTable',
    'showExport' => true,
    'showAddButton' => false,
    'addButtonText' => 'Add New',
    'addButtonClick' => '',
])

<!-- DataTable with Buttons -->
<div class="card">
    <div class="card-datatable table-responsive pt-0">
        <table id="{{ $id }}" class="datatables-basic table">
            {{ $slot }}
        </table>
    </div>
</div>

@pushOnce('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dt_basic_table = document.querySelector('#{{ $id }}');

            if (dt_basic_table) {
                let tableTitle = document.createElement('h5');
                tableTitle.classList.add('card-title', 'mb-0', 'text-md-start', 'text-center', 'pb-md-0', 'pb-6');
                tableTitle.innerHTML = '{{ $title }}';

                // Auto-detect columns
                const headerCells = dt_basic_table.querySelectorAll('thead tr:first-child th');
                const columns = Array.from(headerCells).map(() => ({
                    data: null
                }));

                const dt_basic = new DataTable(dt_basic_table, {
                    columns: columns,
                    columnDefs: [{
                        targets: -1,
                        orderable: false,
                        searchable: false
                    }],
                    order: [
                        [0, 'desc']
                    ],
                    layout: {
                        top2Start: {
                            rowClass: 'row card-header flex-column flex-md-row border-bottom mx-0 px-3',
                            features: [tableTitle]
                        },
                        top2End: {
                            features: [{
                                buttons: [
                                    @if ($showExport)
                                        {
                                            extend: 'collection',
                                            className: 'btn btn-label-primary dropdown-toggle me-4',
                                            text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ti tabler-upload icon-xs me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span></span>',
                                            buttons: [{
                                                    extend: 'print',
                                                    text: '<span class="d-flex align-items-center"><i class="icon-base ti tabler-printer me-1"></i>Print</span>',
                                                    className: 'dropdown-item',
                                                    exportOptions: {
                                                        columns: ':not(:last-child)',
                                                        format: {
                                                            body: function(inner, coldex,
                                                                rowdex) {
                                                                if (inner.length <= 0)
                                                                    return inner;
                                                                if (inner.indexOf('<') >
                                                                    -1) {
                                                                    const parser =
                                                                        new DOMParser();
                                                                    const doc = parser
                                                                        .parseFromString(
                                                                            inner,
                                                                            'text/html'
                                                                            );
                                                                    return doc.body
                                                                        .textContent ||
                                                                        doc.body
                                                                        .innerText;
                                                                }
                                                                return inner;
                                                            }
                                                        }
                                                    }
                                                },
                                                {
                                                    extend: 'csv',
                                                    text: '<span class="d-flex align-items-center"><i class="icon-base ti tabler-file-text me-1"></i>CSV</span>',
                                                    className: 'dropdown-item',
                                                    exportOptions: {
                                                        columns: ':not(:last-child)',
                                                        format: {
                                                            body: function(inner, coldex,
                                                                rowdex) {
                                                                if (inner.length <= 0)
                                                                    return inner;
                                                                const parser =
                                                                    new DOMParser();
                                                                const doc = parser
                                                                    .parseFromString(
                                                                        inner,
                                                                        'text/html');
                                                                return doc.body
                                                                    .textContent || doc
                                                                    .body.innerText ||
                                                                    inner;
                                                            }
                                                        }
                                                    }
                                                },
                                                {
                                                    extend: 'excel',
                                                    text: '<span class="d-flex align-items-center"><i class="icon-base ti tabler-file-spreadsheet me-1"></i>Excel</span>',
                                                    className: 'dropdown-item',
                                                    exportOptions: {
                                                        columns: ':not(:last-child)',
                                                        format: {
                                                            body: function(inner, coldex,
                                                                rowdex) {
                                                                if (inner.length <= 0)
                                                                    return inner;
                                                                const parser =
                                                                    new DOMParser();
                                                                const doc = parser
                                                                    .parseFromString(
                                                                        inner,
                                                                        'text/html');
                                                                return doc.body
                                                                    .textContent || doc
                                                                    .body.innerText ||
                                                                    inner;
                                                            }
                                                        }
                                                    }
                                                },
                                                {
                                                    extend: 'pdf',
                                                    text: '<span class="d-flex align-items-center"><i class="icon-base ti tabler-file-description me-1"></i>PDF</span>',
                                                    className: 'dropdown-item',
                                                    exportOptions: {
                                                        columns: ':not(:last-child)',
                                                        format: {
                                                            body: function(inner, coldex,
                                                                rowdex) {
                                                                if (inner.length <= 0)
                                                                    return inner;
                                                                const parser =
                                                                    new DOMParser();
                                                                const doc = parser
                                                                    .parseFromString(
                                                                        inner,
                                                                        'text/html');
                                                                return doc.body
                                                                    .textContent || doc
                                                                    .body.innerText ||
                                                                    inner;
                                                            }
                                                        }
                                                    }
                                                },
                                                {
                                                    extend: 'copy',
                                                    text: '<i class="icon-base ti tabler-copy me-1"></i>Copy',
                                                    className: 'dropdown-item',
                                                    exportOptions: {
                                                        columns: ':not(:last-child)',
                                                        format: {
                                                            body: function(inner, coldex,
                                                                rowdex) {
                                                                if (inner.length <= 0)
                                                                    return inner;
                                                                const parser =
                                                                    new DOMParser();
                                                                const doc = parser
                                                                    .parseFromString(
                                                                        inner,
                                                                        'text/html');
                                                                return doc.body
                                                                    .textContent || doc
                                                                    .body.innerText ||
                                                                    inner;
                                                            }
                                                        }
                                                    }
                                                }
                                            ]
                                        },
                                    @endif
                                    @if ($showAddButton)
                                        {
                                            text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ti tabler-plus icon-sm"></i> <span class="d-none d-sm-inline-block">{{ $addButtonText }}</span></span>',
                                            className: 'btn btn-primary',
                                            action: function(e, dt, node, config) {
                                                @if ($addButtonClick)
                                                    eval(`{{ $addButtonClick }}`);
                                                @endif
                                            }
                                        }
                                    @endif
                                ]
                            }]
                        },
                        topStart: {
                            rowClass: 'row mx-0 px-3 my-0 justify-content-between border-bottom',
                            features: [{
                                pageLength: {
                                    menu: [10, 25, 50, 100],
                                    text: 'Show_MENU_entries'
                                }
                            }]
                        },
                        topEnd: {
                            search: {
                                placeholder: 'Search...'
                            }
                        },
                        bottomStart: {
                            rowClass: 'row mx-3 justify-content-between',
                            features: ['info']
                        },
                        bottomEnd: 'paging'
                    },
                    language: {
                        paginate: {
                            next: '<i class="icon-base ti tabler-chevron-right scaleX-n1-rtl icon-18px"></i>',
                            previous: '<i class="icon-base ti tabler-chevron-left scaleX-n1-rtl icon-18px"></i>',
                            first: '<i class="icon-base ti tabler-chevrons-left scaleX-n1-rtl icon-18px"></i>',
                            last: '<i class="icon-base ti tabler-chevrons-right scaleX-n1-rtl icon-18px"></i>'
                        }
                    },
                    responsive: true,
                    pageLength: 10
                });

                // Clean up styling
                setTimeout(() => {
                    const elementsToModify = [{
                            selector: '.dt-buttons .btn',
                            classToRemove: 'btn-secondary'
                        },
                        {
                            selector: '.dt-search .form-control',
                            classToRemove: 'form-control-sm',
                            classToAdd: 'ms-4'
                        },
                        {
                            selector: '.dt-length .form-select',
                            classToRemove: 'form-select-sm'
                        },
                        {
                            selector: '.dt-layout-end',
                            classToAdd: 'mt-0'
                        },
                        {
                            selector: '.dt-layout-end .dt-search',
                            classToAdd: 'mt-0 mt-md-6 mb-6'
                        },
                        {
                            selector: '.dt-layout-start',
                            classToAdd: 'mt-0'
                        },
                        {
                            selector: '.dt-layout-end .dt-buttons',
                            classToAdd: 'mb-0'
                        },
                        {
                            selector: '.dt-layout-full',
                            classToRemove: 'col-md col-12',
                            classToAdd: 'table-responsive'
                        }
                    ];

                    elementsToModify.forEach(({
                        selector,
                        classToRemove,
                        classToAdd
                    }) => {
                        document.querySelectorAll(selector).forEach(element => {
                            if (classToRemove) {
                                classToRemove.split(' ').forEach(className => element
                                    .classList.remove(className));
                            }
                            if (classToAdd) {
                                classToAdd.split(' ').forEach(className => element.classList
                                    .add(className));
                            }
                        });
                    });
                }, 100);
            }
        });
    </script>
@endPushOnce
