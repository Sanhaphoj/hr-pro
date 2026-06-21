{{-- Recursive department node for the org chart. Expects: $dept (with manager, employees_count, subtree) --}}
<li>
    <div class="org-node">
        <div class="org-node__head">
            <span class="org-node__dept">{{ $dept->name }}</span>
            <span class="badge badge--gray">{{ $dept->code }}</span>
        </div>

        @if($dept->manager)
            <div class="org-node__person">
                <span class="avatar" style="background: {{ avatar_color($dept->manager->email) }}">{{ $dept->manager->initials }}</span>
                <div class="org-node__person-info">
                    <span class="org-node__person-name">{{ $dept->manager->full_name }}</span>
                    <span class="org-node__person-role">{{ $dept->manager->position?->title ?? 'หัวหน้าแผนก' }}</span>
                </div>
            </div>
        @else
            <div class="org-node__person org-node__person--empty">
                <span class="avatar avatar--empty">—</span>
                <div class="org-node__person-info">
                    <span class="org-node__person-role">ยังไม่กำหนดหัวหน้าแผนก</span>
                </div>
            </div>
        @endif

        <div class="org-node__foot">
            <x-icon name="users" width="14" height="14" /> {{ number_format($dept->employees_count) }} คน
            @can('employees.view')
                <a href="{{ route('employees.index', ['department_id' => $dept->id]) }}" class="org-node__link">ดูพนักงาน</a>
            @endcan
        </div>
    </div>

    @if($dept->subtree->isNotEmpty())
        <ul>
            @foreach($dept->subtree as $child)
                @include('org-chart._node', ['dept' => $child])
            @endforeach
        </ul>
    @endif
</li>
