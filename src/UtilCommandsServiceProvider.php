<?php

namespace Laits\Util;

use Illuminate\Support\ServiceProvider;
use Laits\Util\Commands\CreateUser;
use Laits\Util\Commands\ReportCourseRetention;

class UtilCommandsServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateUser::class,
                ReportCourseRetention::class,
            ]);
        }
    }
}
