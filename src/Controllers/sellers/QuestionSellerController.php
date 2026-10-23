<?php

namespace Themes\Saniar\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\City;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\NotificationManage;
use App\Models\Province;
use App\Models\SellerVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuestionSellerController extends Controller
{
    public function index(Request $request)
    {
        $ids=[];
        foreach (seller()->products()->get() as $product){
            $questions=$product->comments()->get();
            foreach ($questions as $question){
                $ids[]=$question->id;
            }
        }
        $questions=Comment::whereIn('id',$ids)->filter($request)->paginate(20);
        $questions_count=Comment::whereIn('id',$ids)->filter($request)->get();

        return view('front::sellers.panel.questions.index',compact(['questions','questions_count']));
    }

    public function show(Comment $question)
    {
        return view('front::sellers.panel.questions.show', compact('question'))->render();
    }

    public function update(Comment $question, Request $request)
    {
        $this->validate($request, [
            'body'   => 'required',
            'replay' => 'nullable|string',
        ]);
        if ($question->comment_id!=""){
            $question->update([
                'body'   => $request->body,
                'status'   => 'pending',
            ]);
        }else{
            $question->update([
                'body'   => $request->body,
            ]);
        }


        if ($request->replay) {
            $question->commentable->comments()->create([
                'body'       => $request->replay,
                'seller_id'    => sellerID(),
                'comment_id' => $question->id,
            ]);
            $question->update([
                'status'   => 'accepted',
            ]);
        }

        return response($question);
    }


}
