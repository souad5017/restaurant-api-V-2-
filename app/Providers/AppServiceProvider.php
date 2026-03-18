<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Plat;
use App\Policies\CategoryPolicy;
use App\Policies\IngredientPolicy;
use App\Policies\PlatPolicy;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{

    protected $policies = [
        Category::class => CategoryPolicy::class,
        Plat::class => PlatPolicy::class,
        Ingredient::class => IngredientPolicy::class,
    ];
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }



}
