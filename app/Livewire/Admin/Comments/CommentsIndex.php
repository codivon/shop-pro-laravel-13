<?php

namespace App\Livewire\Admin\Comments;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * مدیریت دیدگاه‌ها (پرسش و پاسخ‌ها) — پورت ۱:۱ Back\CommentController به Livewire
 *
 * این کامپوننت با $type دو صفحه می‌سازد:
 *   type=product → دیدگاه‌های محصولات (gate: products.comments)
 *   type=post    → دیدگاه‌های نوشته‌ها  (gate: posts.comments)
 *
 * ⚠️ انحراف مستندشده از قدیمی: کنترلر قدیمی در update/destroy/reply/updateReply/…
 * از منطق «||» استفاده کرده بود (اجرا فقط با یکی از دو پرمیشن!) — در پنل جدید منطق
 * صحیح پیاده شده: هر اکشن فقط با پرمیشن متناظر با type همان صفحه gate می‌شود.
 */
#[Title('مدیریت دیدگاه‌ها')]
class CommentsIndex extends Component
{
    use WithPagination;

    public const TYPE_PRODUCT = 'product';

    public const TYPE_POST = 'post';

    /** نقشه برچسب وضعیت‌ها — پورت getStatusText قدیمی */
    public const STATUS_TEXTS = [
        'pending'     => 'در انتظار',
        'accepted'    => 'تایید شده',
        'rejected'    => 'رد شده',
        'unconfirmed' => 'تایید نشده',
        'approved'    => 'تایید شده',
    ];

    public string $type = self::TYPE_PRODUCT;

    public int $perPage = 15;

    /* ---------------- فیلترها (State در URL) — پورت Comment::scopeFilter ---------------- */

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'ordering', except: 'latest')]
    public string $ordering = 'latest';

    /* ---------------- مودال جزئیات + درخت پاسخ‌ها (پورت show قدیمی) ---------------- */

    public bool $showDetail = false;

    public ?int $viewingComment = null;

    /* ---------------- ویرایش دیدگاه (پورت update قدیمی) ---------------- */

    public ?int $editingComment = null;

    public string $editBody = '';

    public string $editStatus = 'pending';

    /* -------- پاسخ جدید (پورت reply + بخش replay در update — با یک متد پورت شد) -------- */

    public string $replyBody = '';

    /* ---------------- ویرایش پاسخ (پورت updateReply قدیمی) ---------------- */

    public ?int $editingReply = null;

    public string $replyEditBody = '';

    /* ---------------- حذف دیدگاه/پاسخ (پورت destroy/destroyReply قدیمی) ---------------- */

    public ?int $confirmingDelete = null;

    public ?int $confirmingReplyDelete = null;

    public function mount(string $type = self::TYPE_PRODUCT): void
    {
        $this->type = in_array($type, [self::TYPE_PRODUCT, self::TYPE_POST], true) ? $type : self::TYPE_PRODUCT;

        $this->authorizeType();
    }

    /* ---------------- گیت پویا (منطق صحیح — انحراف مستند از «||» قدیمی) ---------------- */

    protected function permission(): string
    {
        return $this->type === self::TYPE_POST ? 'posts.comments' : 'products.comments';
    }

    protected function authorizeType(): void
    {
        auth('adminPanel')->user()?->can($this->permission()) ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['status', 'ordering', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function applyFilters(): void
    {
        $this->status = '';
        $this->ordering = 'latest';
        $this->resetPage();
    }

    /* ---------------- لیست (پورت productComments/postComments قدیمی) ---------------- */

    protected function commentableClass(): string
    {
        return $this->type === self::TYPE_POST ? Post::class : Product::class;
    }

    /** پورت دقیق Comment::scopeFilter (request → پراپرتی‌های #[Url]) */
    protected function baseQuery()
    {
        return Comment::query()
            ->where('commentable_type', $this->commentableClass())
            ->whereNull('comment_id')
            // — پورت scopeFilter —
            ->when($this->status !== '' && $this->status !== 'all', function ($query) {
                if ($this->status === 'answer_accepted') {
                    $query->where('status', 'accepted')->where('comment_id', '!=', null);
                } elseif ($this->status === 'noanswer') {
                    $query->where('status', $this->status)->where('comment_id', null);
                } else {
                    $query->where('status', $this->status);
                }
            })
            ->when($this->status === 'answer_accepted', fn ($query) => $query->where('comment_id', '!=', null))
            // پورت switch(ordering) قدیمی
            ->when($this->ordering === 'oldest', fn ($query) => $query->oldest(), fn ($query) => $query->latest())
            // ادامه پورت productComments/postComments
            ->withCount('replies')
            ->with(['user', 'admin', 'seller', 'commentable']);
    }

    #[Computed]
    public function comments()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    #[Computed(persist: true, seconds: 60)]
    public function pendingCount(): int
    {
        return Comment::query()
            ->where('commentable_type', $this->commentableClass())
            ->where('comment_id', null)
            ->where('status', 'pending')
            ->count();
    }

    /* ---------------- مودال جزئیات (پورت show قدیمی) ---------------- */

    public function openDetail(int $id): void
    {
        $this->authorizeType();

        $comment = Comment::find($id);

        if (! $comment || $comment->commentable_type !== $this->commentableClass()) {
            $this->dispatch('toast', message: 'دیدگاه یافت نشد.', type: 'error');

            return;
        }

        $this->editingComment = null;
        $this->editBody = '';
        $this->editStatus = 'pending';
        $this->replyBody = '';
        $this->editingReply = null;
        $this->replyEditBody = '';
        $this->viewingComment = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->viewingComment = null;
        $this->editingComment = null;
        $this->editBody = '';
        $this->editStatus = 'pending';
        $this->replyBody = '';
        $this->editingReply = null;
        $this->replyEditBody = '';
    }

    #[Computed]
    public function detailComment(): ?Comment
    {
        if (! $this->viewingComment) {
            return null;
        }

        return Comment::query()
            ->with([
                'user',
                'admin',
                'seller',
                'commentable',
                // درخت پاسخ‌ها — پورت $comment->replies قدیمی (نویسنده هر پاسخ هم لازم است)
                'replies' => fn ($query) => $query->with(['user', 'admin', 'seller'])->orderBy('created_at', 'asc'),
            ])
            ->find($this->viewingComment);
    }

    /* --------------- ویرایش دیدگاه (پورت update قدیمی + activity log سه‌شاخه‌ای) --------------- */

    public function openEdit(int $id): void
    {
        $this->authorizeType();

        $comment = Comment::find($id);

        if (! $comment || $comment->commentable_type !== $this->commentableClass()) {
            return;
        }

        $this->editingComment = $comment->id;
        $this->editBody = (string) $comment->body;
        $this->editStatus = (string) $comment->status;
    }

    public function update(): void
    {
        $this->authorizeType();

        if (! $comment = $this->findComment()) {
            $this->dispatch('toast', message: 'دیدگاه یافت نشد.', type: 'error');

            return;
        }

        $this->validate([
            'editBody'   => 'required|string',
            'editStatus' => 'required|string',
        ], [], [
            'editBody'   => 'متن دیدگاه',
            'editStatus' => 'وضعیت',
        ]);

        // پورت دقیق update قدیمی — لاگ سه‌شاخه‌ای (فقط‌متن / فقط‌وضعیت / هردو)
        $oldStatus = $comment->status;
        $oldBody = $comment->body;
        $commentText = mb_substr($comment->body ?? '', 0, 50);
        $commentType = $this->commentTypeLabel();
        $commentableTitle = $comment->commentable->title ?? $comment->commentable->name ?? 'نامشخص';

        $comment->update([
            'body'   => $this->editBody,
            'status' => $this->editStatus,
        ]);

        $newCommentText = mb_substr($comment->body ?? '', 0, 50);

        $description = '';

        if ($oldBody != $this->editBody && $oldStatus != $this->editStatus) {
            // هم متن هم وضعیت تغییر کرده
            $oldStatusText = $this->statusText($oldStatus);
            $newStatusText = $this->statusText($this->editStatus);
            $description = "نظر «{$commentText}» را در {$commentType} «{$commentableTitle}» ویرایش کرد و وضعیت را از {$oldStatusText} به {$newStatusText} تغییر داد";
        } elseif ($oldBody != $this->editBody) {
            // فقط متن تغییر کرده - نوع اول
            $description = "نظر «{$commentText}» در {$commentType} «{$commentableTitle}» ویرایش کرد";
        } elseif ($oldStatus != $this->editStatus) {
            // فقط وضعیت تغییر کرده - نوع دوم
            $oldStatusText = $this->statusText($oldStatus);
            $newStatusText = $this->statusText($this->editStatus);
            $description = "وضعیت نظر «{$commentText}» را در {$commentType} «{$commentableTitle}» از {$oldStatusText} به {$newStatusText} تغییر داد";
        }

        if ($description) {
            activity()
                ->performedOn($comment)
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action'     => 'update_comment',
                    'old_body'   => $oldBody,
                    'new_body'   => $this->editBody,
                    'old_status' => $oldStatus,
                    'new_status' => $this->editStatus,
                ])
                ->log($description);
        }

        $this->editingComment = null;
        $this->editBody = '';
        $this->editStatus = 'pending';

        $this->dispatch('toast', message: 'دیدگاه با موفقیت به‌روزرسانی شد.', type: 'success');
    }

    /* ---------------- پاسخ‌دادن (پورت reply + بخش replay در update) ---------------- */

    /**
     * هر دو مسیر قدیمیِ ایجاد پاسخ (reply() و بخش replay در update()) با یک متد پورت شد:
     * اعتبارسنجی سخت‌گیرانه reply() (min:3|max:1000) + ایجاد Comment فرزند با
     * admin_id و status='accepted' و comment_id + لاگ reply_to_comment با ساختار کامل
     * متد reply (شامل adminName و parent_comment).
     */
    public function addReply(): void
    {
        $this->authorizeType();

        if (! $comment = $this->findComment()) {
            $this->dispatch('toast', message: 'دیدگاه یافت نشد.', type: 'error');

            return;
        }

        $this->validate([
            'replyBody' => 'required|string|min:3|max:1000',
        ], [], [
            'replyBody' => 'متن پاسخ',
        ]);

        $commentText = mb_substr($comment->body ?? '', 0, 50);
        $replyText = mb_substr($this->replyBody, 0, 50);
        $commentType = $this->commentTypeLabel();
        $commentableTitle = $comment->commentable->title ?? $comment->commentable->name ?? 'نامشخص';
        $adminName = $this->adminName();

        // پورت متد reply قدیمی (Comment::create با ip_address/likes_count/dislikes_count)
        $reply = Comment::create([
            'body'             => $this->replyBody,
            'commentable_id'   => $comment->commentable_id,
            'commentable_type' => $comment->commentable_type,
            'admin_id'         => auth('adminPanel')->id(),
            'comment_id'       => $comment->id,
            'status'           => 'accepted',
            'ip_address'       => request()->ip(),
            'likes_count'      => 0,
            'dislikes_count'   => 0,
        ]);

        $logMessage = "مدیر {$adminName} به نظر «{$commentText}» در {$commentType} «{$commentableTitle}» پاسخ داد: «{$replyText}»";

        activity()
            ->performedOn($comment)
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action'         => 'reply_to_comment',
                'reply_id'       => $reply->id,
                'reply_text'     => $replyText,
                'parent_comment' => $commentText,
            ])
            ->log($logMessage);

        $this->replyBody = '';

        $this->dispatch('toast', message: 'پاسخ با موفقیت ثبت شد.', type: 'success');
    }

    /* ---------------- ویرایش پاسخ (پورت updateReply قدیمی) ---------------- */

    public function openEditReply(int $id): void
    {
        $this->authorizeType();

        $reply = Comment::find($id);

        if (! $reply || is_null($reply->comment_id)) {
            return;
        }

        $this->editingReply = $reply->id;
        $this->replyEditBody = (string) $reply->body;
    }

    public function updateReply(): void
    {
        $this->authorizeType();

        if (! $reply = $this->findReply()) {
            return;
        }

        $this->validate([
            'replyEditBody' => 'required|string|min:3|max:1000',
        ], [], [
            'replyEditBody' => 'متن پاسخ',
        ]);

        $oldBody = $reply->body;
        $newBody = $this->replyEditBody;
        $replyText = mb_substr($reply->body ?? '', 0, 50);
        $parentComment = Comment::find($reply->comment_id);
        $parentText = mb_substr($parentComment->body ?? '', 0, 50);
        $adminName = $this->adminName();

        $reply->update([
            'body' => $newBody,
        ]);

        // لاگ دستی برای ویرایش پاسخ — پورت دقیق
        activity()
            ->performedOn($reply)
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action'         => 'update_reply',
                'old_body'       => mb_substr($oldBody, 0, 50),
                'new_body'       => mb_substr($newBody, 0, 50),
                'parent_comment' => $parentText,
                'ip'             => request()->ip(),
            ])
            ->log("مدیر {$adminName} پاسخ خود به نظر «{$parentText}» را از «{$replyText}» به «" . mb_substr($newBody, 0, 50) . '» ویرایش کرد');

        $this->editingReply = null;
        $this->replyEditBody = '';

        $this->dispatch('toast', message: 'پاسخ با موفقیت ویرایش شد.', type: 'success');
    }

    /* ---------------- تغییر وضعیت پاسخ (پورت updateReplyStatus قدیمی) ---------------- */

    public function changeReplyStatus(int $replyId, string $newStatus): void
    {
        $this->authorizeType();

        $reply = Comment::find($replyId);

        if (! $reply || is_null($reply->comment_id)) {
            $this->dispatch('toast', message: 'این آیتم پاسخ نیست.', type: 'error');

            return;
        }

        // پورت اعتبارسنجی قدیمی (status => required|in:pending,accepted,unconfirmed)
        if (! in_array($newStatus, ['pending', 'accepted', 'unconfirmed'], true)) {
            $this->dispatch('toast', message: 'وضعیت پاسخ نامعتبر است.', type: 'error');

            return;
        }

        $oldStatus = $reply->status;
        $replyText = mb_substr($reply->body ?? '', 0, 50);
        $adminName = $this->adminName();

        $reply->update([
            'status' => $newStatus,
        ]);

        // لاگ دستی برای تغییر وضعیت پاسخ — پورت دقیق
        $statusMap = [
            'pending'     => 'در انتظار',
            'accepted'    => 'تایید شده',
            'unconfirmed' => 'تایید نشده',
        ];

        $oldStatusText = $statusMap[$oldStatus] ?? $oldStatus;
        $newStatusText = $statusMap[$newStatus] ?? $newStatus;

        activity()
            ->performedOn($reply)
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action'     => 'change_reply_status',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reply_text' => $replyText,
                'ip'         => request()->ip(),
            ])
            ->log("مدیر {$adminName} وضعیت پاسخ «{$replyText}» را از {$oldStatusText} به {$newStatusText} تغییر داد");

        $this->dispatch('toast', message: 'وضعیت پاسخ با موفقیت تغییر کرد.', type: 'success');
    }

    /* ---------------- حذف پاسخ (پورت destroyReply قدیمی) ---------------- */

    public function confirmDeleteReply(int $id): void
    {
        $this->authorizeType();

        $this->confirmingReplyDelete = $id;
    }

    public function destroyReply(): void
    {
        $this->authorizeType();

        if (! $reply = Comment::find($this->confirmingReplyDelete)) {
            $this->confirmingReplyDelete = null;

            return;
        }

        if (is_null($reply->comment_id)) {
            $this->confirmingReplyDelete = null;
            $this->dispatch('toast', message: 'این آیتم پاسخ نیست.', type: 'error');

            return;
        }

        $replyText = mb_substr($reply->body ?? '', 0, 50);
        $parentComment = Comment::find($reply->comment_id);
        $parentText = mb_substr($parentComment->body ?? '', 0, 50);
        $adminName = $this->adminName();
        $commentType = $parentComment && $parentComment->commentable_type === Product::class ? 'محصول' : 'مقاله';

        $reply->delete();

        // لاگ دستی برای حذف پاسخ — پورت دقیق (قدیمی performedOn نداشت؛ حفظ شد)
        activity()
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action'         => 'delete_reply',
                'reply_text'     => $replyText,
                'parent_comment' => $parentText,
                'comment_type'   => $commentType,
            ])
            ->log("مدیر {$adminName} پاسخ «{$replyText}» به نظر «{$parentText}» را حذف کرد");

        $this->confirmingReplyDelete = null;

        $this->dispatch('toast', message: 'پاسخ حذف شد.', type: 'success');
    }

    /* ---------------- حذف دیدگاه (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        $this->authorizeType();

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        $this->authorizeType();

        $comment = Comment::find($this->confirmingDelete);

        if ($comment && $comment->commentable_type === $this->commentableClass()) {
            $commentText = mb_substr($comment->body ?? '', 0, 50);
            $commentType = $this->commentTypeLabel();
            $commentableTitle = $comment->commentable->title ?? $comment->commentable->name ?? 'نامشخص';
            $adminName = $this->adminName();

            // برابری FK قدیمی (comments.comment_id onDelete cascade) — قابل‌حمل بین SQLite/MySQL:
            // پاسخ‌های همین دیدگاه حذف می‌شوند (متن مودال حذف قدیمی هم همین را وعده داده بود)
            Comment::query()->where('comment_id', $comment->id)->delete();

            $comment->delete();

            $logMessage = "مدیر {$adminName} نظر «{$commentText}» را در {$commentType} «{$commentableTitle}» حذف کرد";

            activity()
                ->performedOn($comment)
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action'       => 'delete_comment',
                    'comment_text' => $commentText,
                ])
                ->log($logMessage);
        }

        $this->confirmingDelete = null;

        if ($this->viewingComment !== null && ! Comment::find($this->viewingComment)) {
            $this->showDetail = false;
            $this->viewingComment = null;
        }

        $this->dispatch('toast', message: 'دیدگاه با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    protected function findComment(): ?Comment
    {
        $comment = Comment::find($this->editingComment ?? $this->viewingComment);

        if (! $comment || $comment->commentable_type !== $this->commentableClass()) {
            return null;
        }

        return $comment;
    }

    protected function findReply(): ?Comment
    {
        $reply = Comment::find($this->editingReply);

        if (! $reply || is_null($reply->comment_id)) {
            return null;
        }

        return $reply;
    }

    public function commentTypeLabel(): string
    {
        return $this->type === self::TYPE_POST ? 'مقاله' : 'محصول';
    }

    /** پورت getStatusText قدیمی */
    public function statusText(?string $status): string
    {
        return self::STATUS_TEXTS[$status] ?? (string) $status;
    }

    /** برچسب بج وضعیت — پورت index قدیمی (منتظر تایید / در انتظار پاسخ / تایید شده / تایید نشده) */
    public function badgeLabel(?string $status): string
    {
        return match ($status) {
            'pending'  => 'منتظر تایید',
            'noanswer' => 'در انتظار پاسخ',
            'accepted' => 'تایید شده',
            default    => 'تایید نشده',
        };
    }

    public function badgeClass(?string $status): string
    {
        return match ($status) {
            'accepted'            => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400',
            'pending', 'noanswer' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400',
            default               => 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400',
        };
    }

    /** نام نویسنده — پورت ستون «نام» در index و show قدیمی (user/admin/seller) */
    public function authorLabel(Comment $comment): string
    {
        if ($comment->user) {
            return 'کاربر: ' . ($comment->user->fullname ?? $comment->user->name ?? '');
        }

        if ($comment->admin) {
            return 'مدیر سایت: ' . ($comment->admin->fullname ?? '');
        }

        if ($comment->seller) {
            return 'فروشنده: ' . ($comment->seller->business_name ?? '');
        }

        return (string) $comment->name;
    }

    protected function adminName(): string
    {
        $admin = auth('adminPanel')->user();

        return $admin?->fullname ?? $admin?->name ?? 'مدیر';
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
        return view('admin-panel::livewire.admin.comments.comments')
            ->layout('admin-panel::layout');
    }
}
