<?php

namespace App\Livewire\Admin\Messages;

use App\Models\Message;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * پورت show قدیمی: جزئیات پیام + لیست گیرندگان (message_user با صفحه‌بندی ۵۰).
 * کنترلر قدیمی گیت نداشت — پرمیشن منوی پنل (settings.sms) اعمال می‌شود.
 */
#[Title('مشاهده پیام')]
class MessageShow extends Component
{
    use WithPagination;

    public Message $message;

    public function mount(Message $message): void
    {
        auth('adminPanel')->user()?->can('settings.sms') ?: abort(403);

        $this->message = $message;
    }

    #[Computed]
    public function messageItems()
    {
        // پورت دقیق: $message->items()->paginate(50)
        return $this->message->items()
            ->with(['user:id,first_name,last_name,username'])
            ->paginate(50);
    }

    #[Computed]
    public function totalRecipients(): int
    {
        return $this->message->items()->count();
    }

    #[Computed]
    public function channelCount(): int
    {
        // پورت محاسبه‌ی ویوی قدیمی
        return collect([$this->message->email, $this->message->sms, $this->message->notification])->filter()->count();
    }

    /**
     * پترن پیامک: قدیمی $message->pattern_code (ستون وجود ندارد!) را چک می‌کرد و همیشه
     * به option('user_message_pattern_code') می‌افتاد — در پنل جدید مقدار ذخیره‌شده‌ی
     * خودِ پیام (sms_patternCode) اولویت دارد و گیرش option فعلی است.
     */
    public function patternCode(): string
    {
        return (string) ($this->message->sms_patternCode ?: option('user_message_pattern_code'));
    }

    /**
     * متغیرهای پترن ذخیره‌شده (پورت msgVars ویوی قدیمی).
     */
    public function smsVariables(): array
    {
        $variables = json_decode((string) $this->message->sms_variables, true);

        return is_array($variables) && count($variables) > 0 ? $variables : [];
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.messages.message-show')
            ->layout('admin-panel::layout');
    }
}
