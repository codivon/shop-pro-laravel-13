<?php

namespace Themes\Saniar\src\Controllers;

use App\Helpers\ShortcodeHelper;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show($page)
    {
        $page = Page::where('slug', $page)->orWhere('id', $page)->firstOrFail();
        $page->content = ShortcodeHelper::parse($page->content);
        return view('front::pages.show', compact('page'));
    }
}
