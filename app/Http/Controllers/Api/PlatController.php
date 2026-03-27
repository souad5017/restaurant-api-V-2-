<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use App\Models\Plat;
use App\Models\Category;
use Illuminate\Http\Request;

class PlatController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        return response()->json(
            Plat::with('category')->get()
        );
    }

    public function store(Request $request)
    {
        $this->authorize('create', Plat::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'ingredient_ids' => 'array',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = $request->only('name', 'description', 'price', 'category_id');
        $data['user_id'] = $request->user()->id;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('plates', 'public');
        }

        $plat = Plat::create($data);

        if ($request->has('ingredient_ids')) {
            $plat->ingredients()->attach($request->ingredient_ids);
        }

        return response()->json($plat->load('ingredients'), 201);
    }

    public function update(Request $request, Plat $plat)
    {
        $this->authorize('update', $plat);

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'ingredient_ids' => 'array',
            'image' => 'nullable|image|max:2048', 
        ]);

        $data = $request->only('name', 'description', 'price', 'category_id');

        if ($request->hasFile('image')) {
            if ($plat->image) {
                Storage::disk('public')->delete($plat->image);
            }

            $data['image'] = $request->file('image')->store('plates', 'public');
        }

        $plat->update($data);

        if ($request->has('ingredient_ids')) {
            $plat->ingredients()->sync($request->ingredient_ids);
        }

        return response()->json($plat->load('ingredients'));
    }

    public function destroy(Plat $plat)
    {
        $this->authorize('delete', $plat);
        $plat->delete();

        return response()->json([
            'message' => 'Plat deleted'
        ]);
    }

    public function show(Plat $plat)
    {
        $this->authorize('view', $plat);
        return response()->json($plat);
    }

    public function storeByCategory(Request $request, Category $category)
    {
        $this->authorize('create', Plat::class);

        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric'
        ]);

        $plat = $category->plats()->create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'user_id' => $request->user()->id
        ]);

        return response()->json($plat, 201);
    }
}
