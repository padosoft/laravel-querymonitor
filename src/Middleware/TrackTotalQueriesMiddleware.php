<?php

namespace Padosoft\QueryMonitor\Middleware;

use Closure;
use Padosoft\QueryMonitor\QueryCounter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TrackTotalQueriesMiddleware
{
    protected function logExcessiveQueries(int $count, int $max, array $context): void
    {
        $message = "Exceeded maximum total queries: {$count} queries (max: {$max}).";
        $extra = [
            'context' => 'request',
            'url' => $context['url'] ?? 'unknown',
            'method' => $context['method'] ?? 'unknown',
        ];

        Log::warning($message, $extra);
    }

    public function handle($request, Closure $next)
    {
        $totalQueriesConfig = Config::get('querymonitor.total_queries', []);

        if (empty($totalQueriesConfig['attiva']) || $totalQueriesConfig['attiva'] === false) {
            return $next($request);
        }

        $traceRegEx = $totalQueriesConfig['traceRegEx'] ?? null;

        $url = $request->fullUrl();

        // Se c'è una regex e la url non match, non tracciamo questa request
        if ($traceRegEx && !preg_match("/{$traceRegEx}/", $url)) {
            // Non attiviamo il tracking, quindi niente reset
            return $next($request);
        }

        QueryCounter::reset([
            'type' => 'request',
            'url' => $url,
            'method' => $request->method(),
        ]);

        return $next($request);
    }

    public function terminate($request, $response)
    {
        $totalQueriesConfig = Config::get('querymonitor.total_queries', []);

        if (empty($totalQueriesConfig['attiva']) || $totalQueriesConfig['attiva'] === false) {
            // Il tracking non è attivo, non facciamo nulla
            return;
        }

        // Controlla se il tracking è stato attivato controllando se getCount > 0 o se QueryCounter::getContextInfo() non è vuoto
        $context = QueryCounter::getContextInfo();
        if (empty($context)) {
            // Non è stato attivato nessun tracking per questa request
            return;
        }

        $count = QueryCounter::getCount();
        $max = $totalQueriesConfig['maxTotalQueries'] ?? 500;

        if ($count <= $max) {
            // Non superiamo il limite, non facciamo nulla
            return;
        }

        $this->logExcessiveQueries($count, $max, $context);
    }
}
