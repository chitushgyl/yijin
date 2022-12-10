<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $admin_namespace = 'App\Http\Admin';
    protected $api_namespace = 'App\Http\Api';
    protected $namespace = 'App\Http\Controllers';

    //protected $namespace = 'App\Http\Controllers';
    //protected $namespace = 'App\Http\Api';
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapPublicRoutes();

        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
//        Route::middleware('web')
//            ->namespace($this->admin_namespace)
//            ->group(base_path('routes/web.php'));
        foreach(glob(base_path("routes/web/")."*.php")as $file){
            Route::middleware('web')
                ->namespace($this->admin_namespace)
                ->group($file);
        }
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
//        Route::prefix('api')
//            ->middleware('api')
//            ->namespace($this->namespace)
//            ->group(base_path('routes/api.php'));
        foreach(glob(base_path("routes/api/")."*.php")as $file){
            Route::middleware('api')
                ->namespace($this->api_namespace)
                ->group($file);
        }
    }

    /**
     * Define the "pb" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapPublicRoutes()
    {
        Route::namespace($this->namespace)
            ->group(base_path('routes/public.php'));
    }

}
