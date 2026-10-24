<?php

namespace App\Livewire\Admin\Tickets\Concerns;

use App\Models\TicketMessage;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * ذخیره‌ی فایل‌های پیوست تیکت — پورت منطق tmp→tickets کنترلر قدیمی.
 *
 * قدیمی: آپلود آژاکسی Dropzone به /uploads/tmp/{jdate} (storeFile با uploadOptimizedImage)
 * و در store/update جابه‌جایی به /uploads/tickets/{jdate}/ — که هرگز اجرا نمی‌شد چون
 * نام برگشتی (بدون مسیر) با Storage::exists چک می‌شد و همیشه false بود.
 * در پنل جدید آپلود مستقیم با WithFileUploads انجام می‌شود؛ برای تصاویر همان
 * uploadOptimizedImage (وب‌پی بهینه) و برای زیپ ذخیره‌ی خام استفاده می‌شود.
 */
trait InteractsWithTicketFiles
{
    /**
     * قوانین اعتبارسنجی فایل‌ها — پورت validate کنترلر قدیمی (file|max:204800|mimes).
     */
    protected function ticketFileRules(): array
    {
        return [
            'files'   => 'array',
            'files.*' => 'file|max:204800|mimes:png,jpeg,jpg,zip',
        ];
    }

    protected function ticketFileMessages(): array
    {
        return [
            'files.array'   => 'فایل‌های پیوست نامعتبر هستند.',
            'files.*.file'  => 'فایل انتخاب‌شده معتبر نیست.',
            'files.*.max'   => 'حجم هر فایل نباید بیشتر از ۲۰۰ مگابایت باشد.',
            'files.*.mimes' => 'فقط فایل‌های png ،jpeg ،jpg و zip مجاز هستند.',
        ];
    }

    protected function ticketFileAttributes(): array
    {
        return [
            'files'   => 'فایل‌های پیوست',
            'files.*' => 'فایل پیوست',
        ];
    }

    /**
     * ذخیره‌ی نهایی فایل‌ها در /uploads/tickets/{jdate Y-m-d}/ + رکورد File (morphMany).
     */
    protected function persistTicketFiles(TicketMessage $message): void
    {
        if (! $this->files) {
            return;
        }

        $date = jdate()->format('Y-m-d');
        $folder = 'uploads/tickets/' . $date;

        foreach ($this->files as $file) {
            /** @var TemporaryUploadedFile $file */
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());

            if (in_array($ext, ['png', 'jpeg', 'jpg'])) {
                // پورت uploadOptimizedImage قدیمی — خروجی وب‌پی بهینه؛ تابع مسیر نسبی کامل را برمی‌گرداند
                $relative = uploadOptimizedImage($file, 'tickets', null, ['path' => $folder . '/']);

                $dbPath = '/' . ltrim((string) $relative, '/');
            } else {
                $name = uniqid() . '_' . time() . '.' . $ext;
                $file->storeAs($folder, $name, 'public');

                $dbPath = '/' . $folder . '/' . $name;
            }

            $message->files()->create([
                'file' => $dbPath,
            ]);
        }
    }

    /**
     * پاک‌سازی فایل‌های یک تیکت از دیسک — پورت destroy قدیمی.
     */
    protected function deleteTicketFiles($ticket): void
    {
        foreach ($ticket->messages as $message) {
            foreach ($message->files as $file) {
                Storage::disk('public')->delete($file->file);
                $file->delete();
            }
        }
    }
}
