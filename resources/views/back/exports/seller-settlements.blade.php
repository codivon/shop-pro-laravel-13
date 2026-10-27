<table>
    <thead>
        <tr>
            <th>ردیف</th>
            <th>سفارش</th>
            <th>تاریخ</th>
            <th>مبلغ سفارش (تومان)</th>
            <th>درصد کمیسیون</th>
            <th>کمیسیون سایت (تومان)</th>
            <th>سهم واریزشده (تومان)</th>
            <th>وضعیت</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($deposits as $deposit)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>#{{ $deposit->order_id }}</td>
                <td>{{ $deposit->created_at ? jdate($deposit->created_at)->format('Y/m/d') : '' }}</td>
                <td>{{ number_format((int) ($deposit->order->price ?? 0)) }}</td>
                <td>{{ is_null($deposit->percent) ? '-' : ((int) $deposit->percent.'٪') }}</td>
                <td>{{ number_format((int) $deposit->amount) }}</td>
                <td>{{ number_format((int) ($netByOrder[$deposit->order_id] ?? 0)) }}</td>
                <td>{{ $deposit->status === 'success' ? 'تسویه‌شده' : 'ناموفق' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">ردیفی برای صورت‌حساب وجود ندارد.</td>
            </tr>
        @endforelse
    </tbody>
</table>
