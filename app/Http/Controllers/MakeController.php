<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class MakeController extends Controller
{
    public function index()
    {
        return view('crm.makes.index');
    }

    public function image(Category $category)
    {
        if (!$category->image || !Storage::disk('public')->exists($category->image)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($category->image));
    }
}
