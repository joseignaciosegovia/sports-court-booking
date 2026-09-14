<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
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
        // La paginación usará el sistema de Bootstrap 5
        Paginator::useBootstrapFive();

        // Datos automáticos para determinadas vistas
        View::composer(['client.*', 'manager.*', 'admin.*'], function ($view) {
            static $todayLong = null;

            $todayLong ??= ucfirst(
                Carbon::now()->locale(app()->getLocale())->isoFormat('dddd, D [de] MMMM [de] YYYY')
            );

            // Le pasamos la fecha actual a las vistas de los usuarios registrados
            $view->with('todayLong', $todayLong);
            // Le pasamos el usuario autenticado a las vistas de los usuarios registrados
            $view->with('authUser', Auth::user());
        });

        // directivas Blade personalizadas
        Blade::directive('sortUrl', function ($expression) {
            return "<?php echo \\App\\Helpers\\SortHelper::url(...{$expression}); ?>";
        });

        Blade::directive('sortIcon', function ($expression) {
            return "<?php echo \\App\\Helpers\\SortHelper::icon(...{$expression}); ?>";
        });
    }
}