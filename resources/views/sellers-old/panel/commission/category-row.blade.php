<tr class="category-row" data-id="{{ $category['id'] }}" style="cursor: default;">
    <td>{{ $index }}</td>
    <td class="tree-level-{{ $level }}">
        @for($i = 0; $i < $level; $i++)
            &nbsp;&nbsp;&nbsp;&nbsp;
            @if($i == $level - 1)
                <i class="fas fa-angle-left" style="color: #999;"></i>
            @endif
        @endfor

        @if($level > 0)
            <i class="fas fa-folder-open" style="color: #ffc107; margin-left: 8px;"></i>
        @else
            <i class="fas fa-database" style="color: #17a2b8; margin-left: 8px;"></i>
        @endif

        {{ $category['name'] }}

        @if($category['commission_type'] == 'inherited')
            <small class="text-muted inherit-icon" data-toggle="tooltip" title="از دسته والد به ارث رسیده">
                <i class="fas fa-arrow-up"></i>
            </small>
        @endif
    </td>
    <td>
        @if($category['commission'] > 0)
            <span class="badge commission-badge" style="background-color: #28a745; color: white;">
                {{ $category['commission'] }}%
            </span>
        @elseif($category['commission'] == 0 && $category['commission_type'] == 'explicit_zero')
            <span class="badge commission-badge" style="background-color: #dc3545; color: white;">
                0% (بدون کمیسیون)
            </span>
        @elseif($category['commission'] == 0 && $category['commission_type'] == 'default_zero')
            <span class="badge commission-badge" style="background-color: #6c757d; color: white;">
                0% (تعریف نشده)
            </span>
        @else
            <span class="badge commission-badge" style="background-color: #ffc107; color: #333;">
                {{ $category['commission'] }}%
            </span>
        @endif
    </td>
    <td>
        @switch($category['commission_type'])
            @case('explicit')
                <span class="text-success">
                    <i class="fas fa-check-circle"></i> کمیسیون مستقیم
                </span>
                @break
            @case('explicit_zero')
                <span class="text-danger">
                    <i class="fas fa-ban"></i> صفر عمدی
                </span>
                @break
            @case('inherited')
                <span class="text-warning">
                    <i class="fas fa-share-alt"></i> ارث بری شده
                </span>
                @break
            @default
                <span class="text-secondary">
                    <i class="fas fa-question-circle"></i> پیش‌فرض صفر
                </span>
        @endswitch
    </td>
    <td>
        @if($category['commission'] > 0)
            <span class="badge badge-success">فعال</span>
        @elseif($category['commission'] == 0 && $category['commission_type'] == 'explicit_zero')
            <span class="badge badge-danger">معاف از کمیسیون</span>
        @else
            <span class="badge badge-secondary">بدون کمیسیون</span>
        @endif
    </td>
</tr>

@foreach($category['children'] as $childIndex => $child)
    @include('sellers-old.panel.commission.category-row', [
        'category' => $child,
        'level' => $level + 1,
        'index' => $index . '.' . ($childIndex + 1)
    ])
@endforeach
