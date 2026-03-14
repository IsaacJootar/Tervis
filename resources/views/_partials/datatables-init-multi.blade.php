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
            instances: {},
            timer: null,
            listenersBound: false,
            livewireHookBound: false,
        };

        const manager = window.__app1MultiDataTables;

        const nextRegistry = {};
        tableIds.forEach((tableId) => {
            nextRegistry[tableId] = {
                order: orders[tableId] || defaultOrder,
                nonOrderable: nonOrderable.includes(tableId),
            };
        });

        // Tear down stale instances from previous page/component includes.
        Object.keys(manager.instances).forEach((tableId) => {
            if (!nextRegistry[tableId] && manager.instances[tableId] &&
                typeof manager.instances[tableId].destroy === 'function') {
                try {
                    manager.instances[tableId].destroy();
                } catch (e) {
                    // Ignore teardown failures.
                }
                manager.instances[tableId] = null;
            }
        });

        manager.registry = nextRegistry;

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

                if (manager.instances[tableId] && typeof manager.instances[tableId].destroy === 'function') {
                    try {
                        manager.instances[tableId].destroy();
                    } catch (e) {
                        // Ignore and continue with fallback destroy.
                    }
                    manager.instances[tableId] = null;
                }

                if ($.fn.DataTable.isDataTable(table)) {
                    try {
                        $(table).DataTable().destroy();
                    } catch (e) {
                        // Ignore and continue.
                    }
                }

                const headerCount = table.querySelectorAll('thead th').length;
                const tbody = table.tBodies && table.tBodies[0] ? table.tBodies[0] : null;
                if (tbody && headerCount > 0) {
                    Array.from(tbody.rows).forEach((row) => {
                        const hasColspan = !!row.querySelector('td[colspan], th[colspan]');
                        if (hasColspan || row.cells.length !== headerCount) {
                            row.remove();
                        }
                    });
                }

                const dataTable = new DataTable(table, buildConfig(meta));
                manager.instances[tableId] = dataTable;

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
