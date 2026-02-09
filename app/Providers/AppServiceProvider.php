<?php

namespace App\Providers;

use App\Livewire\Admin\Ess\TimeAdjustments\Index as AdminEssTimeAdjustmentsIndex;
use App\Observers\ModelActivityObserver;
use App\Services\DailyTimeRecordService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DailyTimeRecordService::class, function ($app) {
            return new DailyTimeRecordService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('admin.ess.time-adjustments.index', AdminEssTimeAdjustmentsIndex::class);

        view()->share('product', config('app.product'));
        $provider = env('APP_PROVIDER', 'novulutions');
        view()->share('provider', config('meta')[$provider] ?? config('meta')['novulutions']);

        
        $except = [
            'EmployeeTimelogs'
        ];

        $modelsPath = app_path('Models');
        if (File::exists($modelsPath)) {
            foreach (File::files($modelsPath) as $file) {
                $modelName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                if (in_array($modelName, $except)) {
                    continue;
                }

                $model = 'App\\Models\\' . $modelName;
                if (class_exists($model) && is_subclass_of($model, Model::class)) {
                    $model::observe(ModelActivityObserver::class);
                }
            }
        }
    }
}
