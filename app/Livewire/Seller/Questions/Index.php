<?php

namespace App\Livewire\Seller\Questions;

use App\Models\Comment;
use App\Models\Product;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — پرسش‌های محصولات فروشنده (پورت QuestionSellerController@index).
 *
 * مبنای کوئری عیناً قدیم: همهٔ کامنت‌های محصولات فروشنده (products × comments)
 * → Comment::whereIn('id', $ids)->filter($request) — فیلتر وضعیت/مرتب‌سازی
 * با همان کلیدهای فرم قدیمی (status / ordering) در URL نگه داشته می‌شود.
 * (کالکشن idها یک‌بار و ایدمپوتنت ساخته می‌شود — هم‌الگوی ساختار قدیمی.)
 */
#[Layout('sellers.layouts.panel')]
#[Title('پرسش و پاسخ')]
class Index extends Component
{
    use WithPagination;

    /* ---------------- فیلترها — همان کلیدهای فرم قدیمی، در URL ---------------- */

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all';

    #[Url(as: 'ordering', except: 'latest')]
    public string $ordering = 'latest';

    public function updating(string $property): void
    {
        if (in_array($property, ['status', 'ordering'], true)) {
            $this->resetPage();
        }
    }

    /**
     * شناسهٔ همهٔ پرسش/پاسخ‌های محصولات فروشنده — پورت حلقهٔ دوگانهٔ قدیمی
     * با یک کوئری گروه‌بندی‌شده (commentable_type قفل روی Product).
     */
    private function questionIds(): array
    {
        return Comment::query()
            ->where('commentable_type', (new Product)->getMorphClass())
            ->whereIn('commentable_id', seller()->products()->select('products.id'))
            ->pluck('id')
            ->all();
    }

    /**
     * پورت scopeFilter مدل Comment — با Request ساختگی همان مسیر کد قدیمی اجرا می‌شود.
     */
    private function filtered()
    {
        $request = Request::create('/', 'GET', [
            'status' => $this->status,
            'ordering' => $this->ordering,
        ]);

        return Comment::whereIn('id', $this->questionIds())->filter($request);
    }

    public function render()
    {
        $questions = $this->filtered()->paginate(20);
        $questionsCount = $this->filtered()->count();

        return view('sellers.panel.questions.index', [
            'questions' => $questions,
            'questionsCount' => $questionsCount,
        ]);
    }
}
