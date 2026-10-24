<?php

namespace Themes\Saniar\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Review;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index()
    {
        $comments = auth()->user()->reviews()->latest()->paginate(10);
        $active="comments";
        return view('front::user.comments', compact('comments','active'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $user = auth()->user();

        $favorite = Favorite::where('user_id', $user->id)->where('product_id', $request->product_id)->first();
        if (!$favorite) {
            $user->favorites()->create([
                'product_id' => $request->product_id
            ]);

            $action = 'create';
        } else {
            $favorite->delete();
            $action = 'delete';
        }

        return response()->json(['action' => $action]);
    }

    public function destroy($id=null)
    {
        $review=Review::find($id);
        if ($review->user_id != auth()->user()->id) {
            abort(404);
        }

        $review->delete();

        return redirect()->route('front.comments.index');
    }
}
