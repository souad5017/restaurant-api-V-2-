<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        return Ingredient::all();
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ingredient::class);
        $request->validate([
            'name' => 'required|string|max:100',
            'tags' => 'array'
        ]);

        $ingredient = Ingredient::create($request->all());
        return response()->json($ingredient, 201);
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $this->authorize('update', $ingredient);
        $request->validate([
            'name' => 'string|max:100',
            'tags' => 'array'
        ]);

        $ingredient->update($request->all());
        return response()->json($ingredient);
    }

    public function destroy(Ingredient $ingredient)
    {
        $this->authorize('delete', $ingredient);
        $ingredient->delete();
        return response()->json(null, 204);
    }
}