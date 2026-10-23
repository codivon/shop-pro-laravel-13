<?php

namespace Themes\Saniar\src\Controllers;

use App\Models\Admin;
use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Widget;
use App\Notifications\Post\CommentPostCreated;
use App\Traits\LogsSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    use LogsSearch;
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

}
