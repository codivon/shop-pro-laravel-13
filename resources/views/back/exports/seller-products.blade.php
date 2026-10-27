<table>
    <thead>
        <tr>
            <th>ردیف</th>
            <th>شناسه</th>
            <th>عنوان</th>
            <th>دسته‌بندی</th>
            <th>قیمت (تومان)</th>
            <th>موجودی کل</th>
            <th>تعداد تنوع</th>
            <th>وضعیت انتشار</th>
            <th>تاریخ ثبت</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($products as $product)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $product->product_id }}</td>
                <td>{{ $product->title }}</td>
                <td>{{ $product->category?->title }}</td>
                <td>{{ $product->prices->isNotEmpty() ? number_format((int) $product->prices->min(fn ($p) => (int) $p->salePrice())) : '-' }}</td>
                <td>{{ number_format((int) ($product->total_stock ?? 0)) }}</td>
                <td>{{ $product->prices->count() }}</td>
                <td>{{ $product->published ? 'منتشرشده' : 'پیش‌نویس' }}</td>
                <td>{{ $product->created_at ? jdate($product->created_at)->format('Y/m/d') : '' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9">محصولی برای خروجی وجود ندارد.</td>
            </tr>
        @endforelse
    </tbody>
</table>
