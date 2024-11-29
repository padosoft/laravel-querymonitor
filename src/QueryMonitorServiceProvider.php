<?php

namespace Padosoft\QueryMonitor;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class QueryMonitorServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/querymonitor.php',
            'querymonitor'
        );
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/querymonitor.php' => config_path('querymonitor.php'),
        ], 'config');

        // Retrieve the configuration settings
        $queryConfig = Config::get('querymonitor.query');

        // The service provider registers a listener for all SQL queries if querymonitor.query.attiva is set to true
        if (!$queryConfig['attiva']) {
            return;
        }

        // Register the DB::listen() method for SQL query monitoring
        DB::listen(function ($query) use ($queryConfig) {
            // Check if the query execution time exceeds the maximum threshold
            if ($queryConfig['maxExecutionTime'] && $query->time < $queryConfig['maxExecutionTime']) {
                return;
            }

            // Check if the SQL matches the provided regular expression
            if ($queryConfig['sqlRegEx'] && !preg_match("/{$queryConfig['sqlRegEx']}/", $query->sql)) {
                return;
            }

            // Build the full SQL query with bindings
            $sql = $query->sql;
            foreach ($query->bindings as $binding) {
                $value = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
                $sql = preg_replace('/\?/', $value, $sql, 1);
            }

            // Log the query and execution time
            Log::info('QueryMonitor: Slow SQL query detected', [
                'query' => $sql,
                'execution_time' => $query->time . ' ms',
            ]);
        });
    }
}
