{{-- ایمیل پشتیبان‌گیری خودکار — فاز ۱۴۵ — قالب مستقل RTL بدون هیچ منبع خارجی --}}
<div style="Margin:0;background:#f5f5f5!important;box-sizing:border-box;color:#0a0a0a;font-family:Vazirmatn,IRANSans,Tahoma,-apple-system,system-ui,'Segoe UI',Roboto,Arial,sans-serif;font-size:13px;font-weight:400;line-height:1.8;margin:0;min-width:100%;padding:24px 0;text-align:right;width:100%!important;direction:rtl">
    <table style="border-collapse:collapse;border-spacing:0;margin:0 auto;width:100%;max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;border-top:4px solid #16a34a">
        <tbody>
        <tr>
            <td style="padding:20px 24px 6px 24px">
                <p style="margin:0;font-size:15px;font-weight:700;color:#111827">پشتیبان‌گیری خودکار دیتابیس</p>
                <p style="margin:4px 0 0 0;font-size:12px;color:#6b7280">{{ $siteTitle }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding:8px 24px 0 24px">
                <p style="margin:0;font-size:13px;color:#374151">
                    نسخه‌ی پشتیبان خودکار از جداول و دیتای دیتابیس با موفقیت ساخته شد.
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 24px">
                <table style="border-collapse:collapse;width:100%;background:#f9fafb;border-radius:8px">
                    <tbody>
                    <tr>
                        <td style="padding:8px 14px;font-size:12px;color:#6b7280;width:38%">نام فایل</td>
                        <td style="padding:8px 14px;font-size:12px;font-weight:700;color:#111827;direction:ltr;text-align:left;font-family:monospace">{{ $filename }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 14px;font-size:12px;color:#6b7280;border-top:1px solid #e5e7eb">حجم</td>
                        <td style="padding:8px 14px;font-size:12px;color:#111827;border-top:1px solid #e5e7eb">
                            @if ($sizeBytes >= 1048576)
                                {{ number_format($sizeBytes / 1048576, 2) }} مگابایت
                            @else
                                {{ number_format($sizeBytes / 1024, 0) }} کیلوبایت
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 14px;font-size:12px;color:#6b7280;border-top:1px solid #e5e7eb">جداول</td>
                        <td style="padding:8px 14px;font-size:12px;color:#111827;border-top:1px solid #e5e7eb">{{ number_format($tablesCount) }} جدول</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 14px;font-size:12px;color:#6b7280;border-top:1px solid #e5e7eb">رکوردها</td>
                        <td style="padding:8px 14px;font-size:12px;color:#111827;border-top:1px solid #e5e7eb">{{ number_format($rowsCount) }} رکورد</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 14px;font-size:12px;color:#6b7280;border-top:1px solid #e5e7eb">تاریخ</td>
                        <td style="padding:8px 14px;font-size:12px;color:#111827;border-top:1px solid #e5e7eb">{{ $jalaliDate }}</td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding:0 24px 8px 24px">
                @if ($attachmentPath)
                    <p style="margin:0;font-size:12px;color:#16a34a;font-weight:700">فایل ZIP پشتیبان به این ایمیل پیوست شده است.</p>
                @else
                    <p style="margin:0;font-size:12px;color:#b45309">
                        حجم فایل بیشتر از حد مجاز پیوست بود؛ فایل روی سرور در مسیر
                        <span style="direction:ltr;font-family:monospace">storage/app/backups</span>
                        نگه‌داری می‌شود — از صفحه‌ی «پشتیبان‌گیری و بازیابی» پنل قابل دانلود است.
                    </p>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:6px 24px 20px 24px;border-top:1px solid #f3f4f6">
                <p style="margin:8px 0 0 0;font-size:11px;color:#9ca3af">
                    این ایمیل به‌صورت خودکار توسط سامانه‌ی پشتیبان‌گیری ارسال شده است.
                </p>
            </td>
        </tr>
        </tbody>
    </table>
</div>
