<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
            'category_id' => 'required|exists:categories,id'
        ]);

        $plat = Plat::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'category_id' => $request->category_id,
            'user_id' => $request->user()->id
        ]);

        return response()->json($plat, 201);
    }

    public function update(Request $request, Plat $plat)
    {
        $this->authorize('update', $plat); 

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric'
        ]);

        $plat->update(
            $request->only('name', 'description', 'price', 'category_id')
        );

        return response()->json($plat);
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
