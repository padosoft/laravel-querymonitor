<?php

namespace Padosoft\QueryMonitor;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\CommandFinished;

class QueryMonitorServiceProvider extends ServiceProvider
{
    protected function setEloquentQueryMonitor(): void
    {
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

    protected function logExcessiveQueries(int $count, int $max, array $context): void
    {
        $message = "Exceeded maximum total queries: {$count} queries (max: {$max}).";
        $extra = [];

        if (!isset($context['type'])) {
            // Nessun tipo => CLI generico
            $extra['context'] = 'cli-service';
            $script = $_SERVER['argv'][0] ?? 'unknown-script';
            $extra['script'] = $script;
            $extra['arguments'] = array_slice($_SERVER['argv'], 1);

            Log::warning($message, $extra);

            return;
        }


        if ($context['type'] === 'command') {
            $extra['context'] = 'command';
            $extra['command'] = $context['command'] ?? 'unknown';
            $extra['arguments'] = $context['arguments'] ?? [];

            Log::warning($message, $extra);

            return;
        }

        if ($context['type'] === 'request') {
            $extra['context'] = 'request';
            $extra['url'] = $context['url'] ?? 'unknown';
            $extra['method'] = $context['method'] ?? 'unknown';

            Log::warning($message, $extra);

            return;
        }

        // Contesto CLI generico
        $extra['context'] = 'cli-service';
        $traceRegEx = Config::get('querymonitor.total_queries.traceRegEx', null);
        $script = $_SERVER['argv'][0] ?? 'unknown-script';
        $extra['script'] = $script;
        $extra['arguments'] = array_slice($_SERVER['argv'], 1);

        Log::warning($message, $extra);

        // Se c'è una regex e il nome script non matcha, non tracciamo
        // Nota: questo controllo è stato omesso all'inizio, ma se si vuole applicarlo anche qui,
        // in realtà andrebbe fatto prima di fare il reset su QueryCounter
    }

    protected function setTotalQueriesCount(): void
    {
        $totalQueriesConfig = Config::get('querymonitor.total_queries', []);

        DB::listen(function ($query) use ($totalQueriesConfig) {
            if (empty($totalQueriesConfig['attiva']) || $totalQueriesConfig['attiva'] === false) {
                return;
            }

            // Incrementa solo se abbiamo attivato il tracking in precedenza
            // Controlliamo se QueryCounter::getContextInfo() non è vuoto per capire se stiamo tracciando
            $context = QueryCounter::getContextInfo();

            if (empty($context)) {
                return;
            }
            QueryCounter::increment();
        });

        // Se stiamo in console, registriamo i listener per i comandi
        if ($this->app->runningInConsole()) {
            $this->app['events']->listen(CommandStarting::class, function (CommandStarting $event) use ($totalQueriesConfig) {
                //Event::listen(CommandStarting::class, function ($event) use ($totalQueriesConfig) {

                if (empty($totalQueriesConfig['attiva']) || $totalQueriesConfig['attiva'] === false) {
                    return;
                }

                $traceRegEx = $totalQueriesConfig['traceRegEx'] ?? null;
                $command = $event->command;
                $arguments = $_SERVER['argv'] ?? [];

                // Se c'è una regex e il nome comando non matcha, non tracciamo
                if ($traceRegEx && !preg_match("/{$traceRegEx}/", $command)) {
                    // Non facciamo reset -> non tracciamo
                    return;
                }

                QueryCounter::reset([
                    'type' => 'command',
                    'command' => $command,
                    'arguments' => $arguments,
                ]);
            });

            // Listener per il comando terminato
            $this->app['events']->listen(CommandFinished::class, function (CommandFinished $event) use ($totalQueriesConfig) {
                //Event::listen(CommandFinished::class, function ($event) use ($totalQueriesConfig) {

                if (empty($totalQueriesConfig['attiva']) || $totalQueriesConfig['attiva'] === false) {
                    return;
                }

                $context = QueryCounter::getContextInfo();
                if (empty($context)) {
                    // Non stavamo tracciando
                    return;
                }

                $count = QueryCounter::getCount();
                $max = $totalQueriesConfig['maxTotalQueries'] ?? 500;

                if ($count <= $max) {
                    // Non superiamo il limite, non facciamo nulla
                    return;
                }

                $this->logExcessiveQueries($count, $max, $context);
            });

            return;
        }

        // Contesto CLI non-command (ad es. Worker, script generico)
        // Puoi gestire questo scenario nell'AppServiceProvider o dove preferisci iniziare il tracking.
        // Esempio: se vuoi tracciare anche questi, fallo qui sotto:
        if (!empty($totalQueriesConfig['attiva']) && $totalQueriesConfig['attiva'] === true && !app()->runningInConsole()) {
            // In questo caso runningInConsole è false, quindi è una request o altro.
            // Già gestito dal middleware.
        }
    }

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

        $this->setEloquentQueryMonitor();

        $this->setTotalQueriesCount();
    }
}
