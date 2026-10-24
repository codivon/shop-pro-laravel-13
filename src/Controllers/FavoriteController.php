<?php

namespace Themes\Saniar\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index()
    {
        $favorites = auth()->user()->favorites()->latest()->paginate(20);
        $active = "favorites";
        return view('front::user.favorites.index', compact('favorites', 'active'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $user = auth()->user();
        $product = Product::find($request->product_id);
        $productTitle = $product->title ?? "#{$product->id}";
        $userName = $user->full_name ?? $user->name ?? 'کاربر';

        $favorite = Favorite::where('user_id', $user->id)->where('product_id', $request->product_id)->first();

        if (!$favorite) {
            $user->favorites()->create([
                'product_id' => $request->product_id
            ]);
            $action = 'create';

            // ثبت لاگ افزودن به علاقه‌مندی‌ها
            activity()
                ->performedOn($product)
                ->causedBy($user)
                ->event('favorited')
                ->withProperties([
                    'action' => 'علاقه‌مندی‌های خود اضافه کرد',
                    'product_title' => $productTitle,
                    'product_id' => $product->id,
                    'ip' => request()->ip()
                ])
                ->log("{$userName} محصول «{$productTitle}» را به لیست علاقه‌مندی‌های خود اضافه کرد");

        } else {
            $favorite->delete();
            $action = 'delete';

            // ثبت لاگ حذف از علاقه‌مندی‌ها
            activity()
                ->performedOn($product)
                ->causedBy($user)
                ->event('unfavorited')
                ->withProperties([
                    'action' => ' علاقه‌مندی‌های خود حذف کرد',
                    'product_title' => $productTitle,
                    'product_id' => $product->id,
                    'ip' => request()->ip()
                ])
                ->log("{$userName} محصول «{$productTitle}» را از لیست علاقه‌مندی‌های خود حذف کرد");
        }

        return response()->json(['action' => $action]);
    }

    public function destroy(Favorite $favorite)
    {
        if ($favorite->user_id != auth()->user()->id) {
            abort(404);
        }

        $product = $favorite->product;
        $productTitle = $product->title ?? "#{$product->id}";
        $userName = auth()->user()->full_name ?? auth()->user()->name ?? 'کاربر';

        $favorite->delete();

        // ثبت لاگ حذف از علاقه‌مندی‌ها
        activity()
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->event('unfavorited')
            ->withProperties([
                'action' => ' علاقه‌مندی‌های خود حذف کرد',
                'product_title' => $productTitle,
                'product_id' => $product->id,
                'ip' => request()->ip()
            ])
            ->log("{$userName} محصول «{$productTitle}» را از لیست علاقه‌مندی‌های خود حذف کرد");

        return redirect()->route('front.favorites.index');
    }
}
