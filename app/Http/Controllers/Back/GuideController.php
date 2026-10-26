<?php

namespace Modules\CustomerClub\Http\Controllers\Back;

use App\Http\Controllers\Controller;

class GuideController extends Controller
{
    /**
     * راهنما و آموزش — «این پکیج کارش چیه + طرز استفاده» (قرارداد فاز ۸۶).
     */
    public function index()
    {
        return view('customer-club::back.guide.index');
    }
}
