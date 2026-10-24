<?php

namespace Themes\Saniar\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Brand;

class BrandController extends Controller
{
    public function show(Brand $brand)
    {
        $products = $brand->products()
            ->published()
            ->orderByStock()
            ->latest()
            ->paginate(20);

        return view('front::brands.show', compact('brand', 'products'));
    }
}
