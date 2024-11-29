<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SQL Query Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the monitoring of raw SQL queries executed by the
    | application. You can enable or disable monitoring, set the maximum
    | execution time threshold, and define a regular expression to filter
    | which queries are logged.
    |
    */

    'query' => [
        /*
         * Enable or disable SQL query monitoring.
         *
         * If set to true, the application will monitor and log SQL queries
         * that meet the specified criteria.
         */
        'attiva' => env('QUERYMONITOR_QUERY_ATTIVA', false),

        /*
         * Maximum execution time threshold in milliseconds.
         *
         * Only queries that take longer than this threshold will be logged.
         * Set to null to disable the threshold.
         */
        'maxExecutionTime' => env('QUERYMONITOR_QUERY_MAX_EXECUTION_TIME', null),

        /*
         * Regular expression to filter SQL queries.
         *
         * Only queries that match this regex pattern will be considered for logging.
         * For example, use '^SELECT.*$' to match only SELECT statements.
         */
        'sqlRegEx' => env('QUERYMONITOR_QUERY_SQL_REGEX', '^.*$'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Eloquent Query Builder Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the monitoring of Eloquent Builder methods that
    | execute queries. You can enable or disable monitoring, set the maximum
    | execution time threshold, and define a regular expression to filter
    | which methods are logged.
    |
    */

    'query_builder' => [
        /*
         * Enable or disable Eloquent Builder method monitoring.
         *
         * If set to true, the application will monitor and log method executions
         * that meet the specified criteria.
         */
        'attiva' => env('QUERYMONITOR_BUILDER_ATTIVA', false),

        /*
         * Maximum execution time threshold in milliseconds.
         *
         * Only method executions that take longer than this threshold will be logged.
         * Set to null to disable the threshold.
         */
        'maxExecutionTime' => env('QUERYMONITOR_BUILDER_MAX_EXECUTION_TIME', null),

        /*
         * Regular expression to filter Eloquent Builder methods.
         *
         * Only methods that match this regex pattern will be considered for logging.
         * For example, use '^(get|first)$' to match only 'get' and 'first' methods.
         */
        'methodRegEx' => env('QUERYMONITOR_BUILDER_METHOD_REGEX', '^.*$'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Miscellaneous Settings
    |--------------------------------------------------------------------------
    |
    */

    /*
     * Maximum stack trace depth to include in the logs.
     * Set to 0 to disable stack trace logging.
     */
    'maxStackDepth' => env('QUERYMONITOR_BUILDER_MAX_STACK_DEPTH', 5),
];
