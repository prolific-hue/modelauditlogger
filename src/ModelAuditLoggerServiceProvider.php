<?php

namespace ProlificHue\ModelAuditLogger;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use ProlificHue\ModelAuditLogger\Console\ArchiveAuditLogsCommand;

class ModelAuditLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/modelauditlogger.php', 'modelauditlogger'
        );      
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Filesystem $filesystem)
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ArchiveAuditLogsCommand::class
            ]);
        }

        $this->publishes([
            __DIR__.'/config/modelauditlogger.php' => config_path('modelauditlogger.php', 'config'),
        ]);

        $this->publishes([
            __DIR__.'/Models/AuditTrailLog.php.stub' => app_path('AuditTrailLog.php', 'model'),
        ]);

        if(config('modelauditlogger.default') === 'database'){
            $this->publishes([
                __DIR__.'/database/migrations/create_table_audit_trail_logs.php.stub' => $this->getMigrationFileName($filesystem),
            ], 'migrations');   
        }

        Helpers::driverCheck();
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @param Filesystem $filesystem
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path.'*_create_table_audit_trail_logs.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_table_audit_trail_logs.php")
            ->first();
    }
}
