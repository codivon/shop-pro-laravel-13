{{-- ردیف درختی کمیسیون — سانیار فاز I (بازگشتی) | قرارداد: $category, $level, $index --}}
<tr class="sfc-category-row" data-name="{{ mb_strtolower($category['name']) }}">
    <td class="font-13 text-muted">{{ $index }}</td>
    <td class="text-start sfc-level-{{ min($level, 4) }}">
        @for($i = 0; $i < $level; $i++)
            <i class="bi bi-arrow-return-left text-muted font-11 ms-1"></i>
        @endfor
        @if($level > 0)
            <i class="bi bi-folder2-open text-warning ms-1"></i>
        @else
            <i class="bi bi-grid main-color-one-color ms-1"></i>
        @endif
        <span class="font-13 {{ $level == 0 ? 'fw-bold' : '' }}">{{ $category['name'] }}</span>
        @if($category['commission_type'] == 'inherited')
            <span class="badge rounded-pill warning-label font-11 ms-1" title="از دستهٔ والد به ارث رسیده"><i class="bi bi-arrow-up"></i> ارثی</span>
        @endif
    </td>
    <td>
        @if($category['commission'] > 0)
            <span class="badge rounded-pill success-label font-13">{{ $category['commission'] }}%</span>
        @elseif($category['commission'] == 0 && $category['commission_type'] == 'explicit_zero')
            <span class="badge rounded-pill danger-label font-13">۰٪ (بدون کمیسیون)</span>
        @elseif($category['commission'] == 0 && $category['commission_type'] == 'default_zero')
            <span class="badge rounded-pill secondary-label font-13">۰٪ (تعریف‌نشده)</span>
        @else
            <span class="badge rounded-pill warning-label font-13">{{ $category['commission'] }}%</span>
        @endif
    </td>
    <td class="font-13">
        @switch($category['commission_type'])
            @case('explicit')
                <span class="text-success"><i class="bi bi-check-circle ms-1"></i>کمیسیون مستقیم</span>
                @break
            @case('explicit_zero')
                <span class="text-danger"><i class="bi bi-slash-circle ms-1"></i>صفر عمدی</span>
                @break
            @case('inherited')
                <span class="text-warning"><i class="bi bi-share ms-1"></i>ارث‌بری‌شده</span>
                @break
            @default
                <span class="text-secondary"><i class="bi bi-question-circle ms-1"></i>پیش‌فرض صفر</span>
        @endswitch
    </td>
    <td>
        @if($category['commission'] > 0)
            <span class="badge rounded-pill success-label">فعال</span>
        @elseif($category['commission'] == 0 && $category['commission_type'] == 'explicit_zero')
            <span class="badge rounded-pill danger-label">معاف از کمیسیون</span>
        @else
            <span class="badge rounded-pill secondary-label">بدون کمیسیون</span>
        @endif
    </td>
</tr>

@foreach($category['children'] as $childIndex => $child)
    @include('front::sellers.panel.commission.category-row', [
        'category' => $child,
        'level' => $level + 1,
        'index' => $index . '.' . ($childIndex + 1),
    ])
@endforeach
