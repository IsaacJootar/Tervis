@section('title', $heading)

<div>
    <div class="card portal-section-card">
        <div class="card-header border-0 pb-0">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="portal-section-icon">
                    <i class="bx {{ $tableIcon }}"></i>
                </span>
                <h6 class="portal-section-title mb-0">{{ $tableTitle }}</h6>
            </div>
            <small class="text-muted">{{ $tableDescription }}</small>
        </div>
        <div class="table-responsive pt-0">
            <table class="table align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        @foreach ($tableColumns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tableRows as $row)
                        <tr>
                            @foreach ($row['cells'] as $cell)
                                <td>{{ $cell ?: 'N/A' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($tableColumns) }}" class="text-center py-4">
                                <div class="portal-empty d-inline-block w-100">
                                    <i class="bx {{ $tableIcon }} bx-lg mb-2"></i>
                                    <p class="mb-0">{{ $emptyMessage }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tableRows->hasPages())
            <div class="card-body pt-3">
                {{ $tableRows->links() }}
            </div>
        @endif
    </div>
</div>
