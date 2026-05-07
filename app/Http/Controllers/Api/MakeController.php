<?php

namespace App\Http\Controllers\Api;

use App\Models\BomProduct;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MakeController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $makes = Category::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        $makes->getCollection()->transform(fn (Category $make) => $this->serialize($make));

        return response()->json([
            'success' => true,
            'message' => 'Makes retrieved successfully.',
            'data' => $makes,
        ]);
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $limit = max(1, min((int) $request->get('limit', 10), 100));

        $makes = Category::query()
            ->when($query !== '', fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Category $make) => $this->serialize($make));

        return response()->json($makes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('make', 'public');
        }

        $make = Category::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Make created successfully.',
            'data' => $this->serialize($make),
        ], 201);
    }

    public function show(Category $make)
    {
        return response()->json([
            'success' => true,
            'message' => 'Make retrieved successfully.',
            'data' => $this->serialize($make),
        ]);
    }

    public function update(Request $request, Category $make)
    {
        $validator = Validator::make($request->all(), $this->rules($make), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            if ($make->image) {
                Storage::disk('public')->delete($make->image);
            }

            $data['image'] = $request->file('image')->store('make', 'public');
        }

        $make->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Make updated successfully.',
            'data' => $this->serialize($make->fresh()),
        ]);
    }

    public function destroy(Category $make)
    {
        if (BomProduct::where('category_id', $make->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This make is already in use.',
            ], 422);
        }

        $make->delete();

        return response()->json([
            'success' => true,
            'message' => 'Make deleted successfully.',
        ]);
    }

    private function rules(?Category $make = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('category', 'name')->ignore($make?->id)->whereNull('deleted_at'),
            ],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,svg', 'max:2048'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Please enter make name.',
            'name.unique' => 'This make name already exists.',
            'image.mimes' => 'Please select a valid image. Allowed types: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Please select an image smaller than 2MB.',
        ];
    }

    private function serialize(Category $make): array
    {
        return [
            'id' => $make->id,
            'name' => $make->name,
            'image' => $make->image,
            'image_url' => $make->image ? route('make.image', ['category' => $make->id]) . '?v=' . optional($make->updated_at)->timestamp : null,
            'created_at' => optional($make->created_at)?->toIso8601String(),
            'updated_at' => optional($make->updated_at)?->toIso8601String(),
        ];
    }
}
