// Add this to your main layout or a separate JS file
function initializeDataTable(selector = '.datatables-basic', options = {}) {
  const defaultOptions = {
    dom: '<"card-header border-bottom p-1"<"head-label"><"dt-action-buttons text-end"B>><"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    buttons: [
      {
        extend: 'collection',
        className: 'btn btn-label-primary dropdown-toggle me-2',
        text: '<i class="ti ti-file-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
        buttons: [
          {
            extend: 'print',
            text: '<i class="ti ti-printer me-1"></i>Print',
            className: 'dropdown-item',
            exportOptions: { columns: ':not(:last-child)' }
          },
          {
            extend: 'csv',
            text: '<i class="ti ti-file-text me-1"></i>CSV',
            className: 'dropdown-item',
            exportOptions: { columns: ':not(:last-child)' }
          },
          {
            extend: 'excel',
            text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel',
            className: 'dropdown-item',
            exportOptions: { columns: ':not(:last-child)' }
          },
          {
            extend: 'pdf',
            text: '<i class="ti ti-file-description me-1"></i>PDF',
            className: 'dropdown-item',
            exportOptions: { columns: ':not(:last-child)' }
          },
          {
            extend: 'copy',
            text: '<i class="ti ti-copy me-1"></i>Copy',
            className: 'dropdown-item',
            exportOptions: { columns: ':not(:last-child)' }
          }
        ]
      }
    ],
    responsive: true,
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50, 100],
    language: {
      search: '',
      searchPlaceholder: 'Search records...'
    },
    columnDefs: [
      {
        targets: -1,
        orderable: false,
        searchable: false
      }
    ],
    order: [[0, 'desc']]
  };

  const finalOptions = { ...defaultOptions, ...options };

  $(selector).DataTable(finalOptions);
}

// Auto-initialize on page load
$(document).ready(function () {
  if ($('.datatables-basic').length) {
    initializeDataTable();
  }
});
