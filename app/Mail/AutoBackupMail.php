<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * ایمیل پشتیبان‌گیری خودکار — فاز ۱۴۵
 *
 * اگر حجم فایل ≤ حد پیوست (AutoBackupRunner::EMAIL_ATTACH_MAX_MB) فایل ZIP
 * ضمیمه می‌شود؛ در غیر این صورت فایل روی سرور می‌ماند و فقط مشخصاتش ارسال می‌شود.
 * (attachmentPath = null یعنی «بدون پیوست»)
 */
class AutoBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $siteTitle,
        public string $filename,
        public int $sizeBytes,
        public int $tablesCount,
        public int $rowsCount,
        public string $jalaliDate,
        public ?string $attachmentPath = null,
    ) {}

    public function build(): self
    {
        $mail = $this->subject('پشتیبان خودکار دیتابیس — '.$this->siteTitle)
            ->view('mail.auto-backup');

        if ($this->attachmentPath && is_file($this->attachmentPath)) {
            $mail->attach($this->attachmentPath, [
                'as' => $this->filename,
                'mime' => 'application/zip',
            ]);
        }

        return $mail;
    }
}
