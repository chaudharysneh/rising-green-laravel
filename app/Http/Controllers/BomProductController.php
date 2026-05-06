<?php

namespace App\Http\Controllers;

use App\Models\BomProduct;
use App\Models\Category;
use App\Models\Tax;
use App\Models\Technology;
use App\Models\Warranty;
use Illuminate\Support\Facades\Storage;

class BomProductController extends Controller
{
    public function index()
    {
        return view('crm.bom.index');
    }

    public function create()
    {
        return view('crm.bom.create', $this->formData());
    }

    public function show(BomProduct $bomProduct)
    {
        $bomProduct->load(['category', 'technology', 'warranty', 'creator']);

        return view('crm.bom.show', compact('bomProduct'));
    }

    public function edit(BomProduct $bomProduct)
    {
        $bomProduct->load(['category', 'technology', 'warranty']);

        return view('crm.bom.edit', array_merge($this->formData(), compact('bomProduct')));
    }

    public function image(BomProduct $bomProduct)
    {
        if (!$bomProduct->image || !Storage::disk('public')->exists($bomProduct->image)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($bomProduct->image));
    }

    private function formData(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(),
            'technologies' => Technology::query()->orderBy('title')->get(),
            'warranties' => Warranty::query()->orderBy('title')->get(),
            'taxes' => Tax::active()->orderBy('name')->orderBy('rate')->get(),
        ];
    }
}
