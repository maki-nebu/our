<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use App\Models\FaqCategory;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // $this->app->bind('path.public', function() {
        //     return base_path().'/../public_html';
        // });
        Schema::defaultStringLength(191);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->app->bind('path.public', function() {
        //     return base_path().'/../public_html';
        // });
          $setting = Setting::first();
    View::share('setting', $setting);
        Paginator::useBootstrap();


            View::composer('front.faq', function ($view) {
        $view->with('faqCategories', FaqCategory::withCount('faqs')->orderBy('order')->get());
    });
    }
}
