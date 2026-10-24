<?php

namespace Themes\Saniar\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Widget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index()
    {
        $widgets = Widget::with('options')
            ->where('theme', current_theme_name())
            ->where('is_active', true)
            ->where('page', 'posts')
            ->orderBy('ordering')
            ->get();

        return view('front::blogs.index', compact('widgets'));
    }

    /**
     * جستجوی وبلاگ — سانیار فاز K:
     * در WeblakShop این روت (front.blog.search) به متد ناموجود search پوینت می‌کرد و
     * هر فراخوانی BadMethodCall/500 می‌داد (روت مرده). آرشیو مقالات سانیار خودش
     * پارامتر q را می‌فهمد، پس جستجو با حفظ روت موجود به آنجا ریدایرکت می‌شود.
     */
    public function search(Request $request)
    {
        $data = $request->validate([
            'q'   => ['nullable', 'string', 'max:191'],
            'cat' => ['nullable', 'string', 'max:191'],
            'tag' => ['nullable', 'string', 'max:191'],
        ]);

        $params = array_filter($data, fn ($value) => $value !== null && $value !== '');

        return redirect()->to(route('front.articles.index', $params, false));
    }
}
