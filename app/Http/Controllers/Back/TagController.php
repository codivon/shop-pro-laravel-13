<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Tag::class, 'tags');
    }

    public function index(Request $request)
    {
        $tags = Tag::query()
            ->when($request->search, function($query) use ($request) {
                $query->search($request->search);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // آمار کلی
        $statistics = [
            'total' => Tag::count(),
            'total_views' => Tag::sum('view_count'),
            'most_used' => Tag::withCount('taggables')->orderBy('taggables_count', 'desc')->first(),
            'most_viewed' => Tag::orderBy('view_count', 'desc')->first(),
        ];

        return view('back.tags.index', compact('tags', 'statistics'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
        ]);

        $tag = Tag::create([
            'name' => $request->name,
            'slug' => sluggable_helper_function($request->name), // فاز ۲۵ — اسلاگ فارسی
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'tag' => $tag]);
        }

        return redirect()->route('admin.tags.index')->with('success', 'تگ با موفقیت ایجاد شد');
    }

    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tags,name,' . $tag->id,
        ]);

        $tag->update([
            'name' => $request->name,
            'slug' => sluggable_helper_function($request->name), // فاز ۲۵ — اسلاگ فارسی
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.tags.index')->with('success', 'تگ با موفقیت به‌روزرسانی شد');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response('success');
    }

    public function multipleDestroy(Request $request)
    {
        $this->authorize('tags.delete');
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:tags,id',
        ]);

        foreach ($request->ids as $id) {
            $story = Tag::find($id);
            $this->destroy($story);
        }

        return response('success');
    }


    public function details(Tag $tag)
    {
        // دریافت جزئیات استفاده
        $usageDetails = $tag->getUsageDetails();

        // دریافت تمام مدل‌هایی که از این تگ استفاده کرده‌اند
        $taggables = $tag->taggables()->with('taggable')->get()->groupBy('taggable_type');

        $data = [
            'tag' => $tag,
            'total_usage' => $tag->taggables()->count(),
            'usage_details' => $usageDetails,
            'taggables' => $taggables,
        ];

        $html = view('back.tags.tag-details', $data)->render();

        return response()->json(['html' => $html]);
    }
    public function export(Request $request)
    {
        $tags = Tag::all();

        $filename = 'tags_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($tags) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['شناسه', 'عنوان', 'اسلاگ', 'تعداد استفاده', 'بازدید', 'تاریخ ایجاد']);

            foreach ($tags as $tag) {
                fputcsv($file, [
                    $tag->id,
                    $tag->name,
                    $tag->slug,
                    $tag->taggables()->count(),
                    $tag->view_count,
                    jdate($tag->created_at)->format('Y/m/d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
