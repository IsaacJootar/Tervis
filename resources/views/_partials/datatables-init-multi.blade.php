@php
    $tableIds = $tableIds ?? ['dataTable'];
    $orders = $orders ?? [];
    $nonOrderable = $nonOrderable ?? [];
    $defaultOrder = $defaultOrder ?? [0, 'desc'];
@endphp

<script>
    (function() {
        const tableIds = @json(array_values($tableIds));
        const orders = @json($orders);
        const nonOrderable = @json(array_values($nonOrderable));
        const defaultOrder = @json($defaultOrder);

        window.__app1MultiDataTables = window.__app1MultiDataTables || {
            registry: {},
            timer: null,
            listenersBound: false,
            livewireHookBound: false,
        };

        const manager = window.__app1MultiDataTables;

        tableIds.forEach((tableId) => {
            manager.registry[tableId] = {
                order: orders[tableId] || defaultOrder,
                nonOrderable: nonOrderable.includes(tableId),
            };
        });

        function buildConfig(meta) {
            const config = {
                layout: {
                    topStart: {
                        buttons: [{
                            text: '<i class="bx bx-download me-1"></i>Export Data',
                            className: 'btn btn-primary',
                            extend: 'collection',
                            buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
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
                order: meta.order,
                pageLength: 20,
                lengthMenu: [
                    [10, 20, 25, 50, 100, -1],
                    [10, 20, 25, 50, 100, 'All']
                ],
                autoWidth: false,
                destroy: true,
            };

            if (meta.nonOrderable) {
                config.ordering = false;
                delete config.order;
            }

            return config;
        }

        function initDataTables() {
            if (typeof DataTable === 'undefined' || !window.jQuery || !$.fn || !$.fn.DataTable) {
                return;
            }

            Object.entries(manager.registry).forEach(([tableId, meta]) => {
                const table = document.getElementById(tableId);
                if (!table) {
                    return;
                }

                if ($.fn.DataTable.isDataTable(table)) {
                    $(table).DataTable().destroy();
                }

                const dataTable = new DataTable(table, buildConfig(meta));

                if (dataTable?.columns && typeof dataTable.columns.adjust === 'function') {
                    dataTable.columns.adjust();
                }
            });
        }

        function scheduleInit(delay = 80) {
            clearTimeout(manager.timer);
            manager.timer = setTimeout(initDataTables, delay);
        }

        function bindLivewireHook() {
            if (manager.livewireHookBound) {
                return;
            }

            if (window.Livewire && typeof Livewire.hook === 'function') {
                Livewire.hook('message.processed', function() {
                    scheduleInit(80);
                });
                manager.livewireHookBound = true;
            }
        }

        if (!manager.listenersBound) {
            document.addEventListener('livewire:initialized', function() {
                bindLivewireHook();
                scheduleInit(80);
            });

            document.addEventListener('livewire:navigated', function() {
                scheduleInit(80);
            });

            document.addEventListener('DOMContentLoaded', function() {
                scheduleInit(60);
            });

            window.addEventListener('load', function() {
                scheduleInit(80);
            });

            manager.listenersBound = true;
        }

        bindLivewireHook();
        scheduleInit(50);
    })();
</script>
