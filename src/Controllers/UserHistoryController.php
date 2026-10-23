<?php

namespace Themes\Saniar\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use App\Models\Viewer;
use Illuminate\Http\Request;

class UserHistoryController extends Controller
{
    public function index()
    {
        $views = Viewer::select('user_id','product_path','page_path')->where(['user_id'=>auth()->user()->id,'page_path'=>'product','status'=>'1'])->with('product')->distinct()->get();
        $products_id=[];
        foreach ($views as $view){
            $products_id[]=$view->product_path;
        }

        $products=Product::whereIn('slug',$products_id)->paginate(10);

        $active="user-history";
        return view('front::user.viewers', compact('products','active'));
    }


    public function destroy(Request $request)
    {
        Viewer::where(['user_id'=>auth()->user()->id,'page_path'=>'products','product_path'=>$request->slug])->Update(['status'=>'0']);

        return redirect()->route('front.user.user-history');
    }
}
