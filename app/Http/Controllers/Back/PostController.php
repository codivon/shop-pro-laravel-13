<?php

namespace App\Http\Controllers\Back;

use App\Imports\PostsImport;
use App\Imports\UsersImport;
use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Back\Post\StorePostRequest;
use App\Http\Requests\Back\Post\UpdatePostRequest;
use App\Models\FieldValue;
use App\Models\Fild;
use App\Models\Post;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Maatwebsite\Excel\Facades\Excel;
use Morilog\Jalali\Jalalian;
use Spatie\Activitylog\Models\Activity;

class PostController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Post::class, 'post');
    }

    public function index()
    {
        $posts = Post::detectLang()->latest()->paginate(10);

        return view('back.posts.index', compact('posts'));
    }

    public function create(Request $request)
    {
        $categories = Category::detectLang()->where('type', 'postcat')->orderBy('ordering')->get();
        $copy_product = $request->product ? Post::where('slug', $request->product)->first() : null;
        $filds = Fild::where('belongs_to', 'posts')->orderBy('created_at', 'desc')->get();
        return view('back.posts.create', compact('categories', 'copy_product','filds'));
    }

    public function store(StorePostRequest $request)
    {
        // اعتبار سنجی فیلد های اختصاصی
        $requiredFilds = Fild::where('belongs_to', 'posts')->where('required', 1)->get();
        $validationRules = [];
        $messagesValidationRules = [];
        foreach ($requiredFilds as $requiredFild) {
            $validationRules["filds.$requiredFild->id"] = 'required';
            $messagesValidationRules["filds.$requiredFild->id.required"] = "فیلد {$requiredFild->title} اجباری است.";
        }
        $request->validate($validationRules, $messagesValidationRules);

        $posts = Post::where('slug', sluggable_helper_function($request['slug'] ?: $request['title']))->get();
        if (!count($posts)) {
            $data = $request->validated();
            // امنیتی: پاک‌سازی فیلدهای HTML غنی (ضد XSS)
            foreach (['description', 'content', 'short_description'] as $richTextKey) {
                if (array_key_exists($richTextKey, $data)) {
                    $data[$richTextKey] = sanitize_rich_text($data[$richTextKey]);
                }
            }

            $data['published'] = $request->has('published');
            $data['is_editor_pick'] = $request->has('is_editor_pick');
            $data['allow_comments'] = $request->has('allow_comments');
            $data['status'] = 'end';

            if ($data['publish_date']) {
                $data['publish_date'] = Jalalian::fromFormat('Y-m-d H:i:s', $request->publish_date)->toCarbon();
            }
            $data['created_by']=    $request->created_by;
            $data['slug']      = sluggable_helper_function($data['slug'] ?: $data['title']);
            $data['admin_id']   = auth('adminPanel')->user()->id;
            $data['lang']      = app()->getLocale();


            if ($request->hasFile('image')) {

                $data['image'] = uploadOptimizedImage($request->image, 'posts');
            }

            $post = Post::create($data);
            $post->Categories()->attach($request->categories);

            // store Filds
            if (Fild::where('belongs_to', 'posts')->count() > 0) {
                if (isset($request->filds) and count($request->filds)) {
                    saveFieldValues($request->filds, 'posts', $post->id);
                }
            }

            session()->put('toast-success', 'نوشته با موفقیت ایجاد شد.');
            return response("success");
        } else {
            return response(["Repetition-slug", sluggable_helper_function($request['slug'] ?: $request['title'])]);
        }
    }

    public function storeWithAi(Request $request) {}

    public function edit(Post $post)
    {
        $categories = Category::detectLang()->where('type', 'postcat')->orderBy('ordering')->get();
        $filds = Fild::where('belongs_to', 'posts')->orderBy('created_at', 'desc')->get();
        $fieldValues = FieldValue::where('related_id', $post->id)->get();

        return view('back.posts.edit', compact('post', 'categories' ,'filds','fieldValues'));
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        // اعتبار سنجی فیلد های اختصاصی
        $requiredFilds = Fild::where('belongs_to', 'posts')->where('required', 1)->get();
        $validationRules = [];
        $messagesValidationRules = [];
        foreach ($requiredFilds as $requiredFild) {
            $validationRules["filds.$requiredFild->id"] = 'required';
            $messagesValidationRules["filds.$requiredFild->id.required"] = "فیلد {$requiredFild->title} اجباری است.";
        }
        $request->validate($validationRules, $messagesValidationRules);

        $data = $request->validated();

        // امنیتی: پاک‌سازی فیلدهای HTML غنی (ضد XSS)
        foreach (['description', 'content', 'short_description'] as $richTextKey) {
            if (array_key_exists($richTextKey, $data)) {
                $data[$richTextKey] = sanitize_rich_text($data[$richTextKey]);
            }
        }

        if ($data['publish_date']) {
            $data['publish_date'] = Jalalian::fromFormat('Y-m-d H:i:s', $request->publish_date)->toCarbon();
        }

        $data['slug']      = $data['slug'] ?: $data['title'];
        $data['published'] = $request->has('published');
        $data['is_editor_pick'] = $request->has('is_editor_pick');
        $data['allow_comments'] = $request->has('allow_comments');

        if ($request->hasFile('image')) {
            $data['image'] = uploadOptimizedImage($request->image, 'posts', $post->id);
        } else {
            $data['image'] = $post->image;
        }

        $post->update($data);
        $post->Categories()->sync($request->categories);

        // store Filds
        if (isset($request->filds) and count($request->filds)) {
            saveFieldValues($request->filds, 'posts', $post->id);
        }

        session()->put('toast-success', 'نوشته با موفقیت ویرایش شد.');
        return response("success");
    }

    public function destroy(Post $post)
    {
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }
        $post->tags()->detach();
        $post->Categories()->sync([]);
        $post->delete();
    }

    public function generate_slug(Request $request)
    {
        $request->validate([
            'title' => 'required',
        ]);

        $slug = SlugService::createSlug(Post::class, 'slug', $request->title);

        return response()->json(['slug' => $slug]);
    }

    //------------- Category methods

    public function categories()
    {
        $this->authorize('posts.category');

        $categories = Category::detectLang()->where('type', 'postcat')->whereNull('category_id')
            ->with('childrenCategories')
            ->orderBy('ordering')
            ->get();

        return view('back.posts.categories', compact('categories'));
    }


}
