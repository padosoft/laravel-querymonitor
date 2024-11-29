<?php

namespace Padosoft\QueryMonitor\Builders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class QueryMonitorBuilder extends EloquentBuilder
{
    /**
     * Executes the laravel parent method $methodName with timing and performance logging.
     *
     * @param  string $methodName The name of the laravel parent method to call.
     * @param  array  $arguments  The arguments to pass to the laravel parent method.
     * @return mixed  The result of the laravel parent method.
     */
    private function executeWithTiming(string $methodName, array $arguments)
    {
        // Start the execution timer
        $startTime = microtime(true);

        // Execute the original parent method with the provided arguments
        $results = call_user_func_array([parent::class, $methodName], $arguments);

        // Calculate the total execution time in milliseconds
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Retrieve monitoring settings from configuration
        try {
            $builderConfig = Config::get('querymonitor.query_builder');
        } catch (\Exception $e) {
            // Log any exceptions while retrieving configuration and return results
            Log::error('QueryMonitor: Error retrieving configuration: querymonitor.query_builder. ' . $e->getMessage());

            return $results;
        }

        // Exit if monitoring is not active
        if (!$builderConfig['attiva']) {
            return $results;
        }

        // Exit if execution time is below the threshold
        if ($builderConfig['maxExecutionTime'] && $executionTime < $builderConfig['maxExecutionTime']) {
            return $results;
        }

        // Exit if method name does not match the regex pattern
        if ($builderConfig['methodRegEx'] && !preg_match("/{$builderConfig['methodRegEx']}/i", $methodName)) {
            return $results;
        }

        // Build the SQL query with bindings for logging
        $sql = $this->toSql();
        $bindings = $this->getBindings();

        foreach ($bindings as $binding) {
            // Safely add quotes around string bindings
            $value = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        // Log the slow Eloquent method execution
        Log::info('QueryMonitor: Slow Eloquent method detected', [
            'method' => $methodName,
            'execution_time' => $executionTime . ' ms',
            'query' => $sql,
        ]);

        // Return the result of the parent method
        return $results;
    }

    /**
     * Overrides the get method to include performance monitoring.
     *
     * @param  array|string $columns The columns to retrieve.
     * @return Collection   The result collection.
     */
    public function get($columns = ['*'])
    {
        return $this->executeWithTiming('get', func_get_args());
    }

    /**
     * Overrides the first method to include performance monitoring.
     *
     * @param  array|string      $columns The columns to retrieve.
     * @return Model|object|null The first result.
     */
    public function first($columns = ['*'])
    {
        return $this->executeWithTiming('first', func_get_args());
    }

    /**
     * Overrides the firstOrFail method to include performance monitoring.
     *
     * @param  array|string $columns The columns to retrieve.
     * @return Model|object The first result.
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail($columns = ['*'])
    {
        return $this->executeWithTiming('firstOrFail', func_get_args());
    }

    /**
     * Overrides the pluck method to include performance monitoring.
     *
     * @param  string      $column The column to pluck.
     * @param  string|null $key    The key to use.
     * @return Collection  The plucked values.
     */
    public function pluck($column, $key = null)
    {
        return $this->executeWithTiming('pluck', func_get_args());
    }

    /**
     * Overrides the paginate method to include performance monitoring.
     *
     * @param  int|null                                              $perPage  Items per page.
     * @param  array|string                                          $columns  The columns to retrieve.
     * @param  string                                                $pageName The page query string parameter.
     * @param  int|null                                              $page     The current page.
     * @return LengthAwarePaginator The paginator instance.
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return $this->executeWithTiming('paginate', func_get_args());
    }

    /**
     * Overrides the count method to include performance monitoring.
     *
     * @param  string $columns The column to count.
     * @return int    The count result.
     */
    public function count($columns = '*')
    {
        return $this->executeWithTiming('count', func_get_args());
    }

    /**
     * Overrides the exists method to include performance monitoring.
     *
     * @return bool Whether any records exist.
     */
    public function exists()
    {
        return $this->executeWithTiming('exists', func_get_args());
    }

    /**
     * Overrides the doesntExist method to include performance monitoring.
     *
     * @return bool Whether no records exist.
     */
    public function doesntExist()
    {
        return $this->executeWithTiming('doesntExist', func_get_args());
    }

    /**
     * Overrides the max method to include performance monitoring.
     *
     * @param  string $column The column to find the max value.
     * @return mixed  The maximum value.
     */
    public function max($column)
    {
        return $this->executeWithTiming('max', func_get_args());
    }

    /**
     * Overrides the min method to include performance monitoring.
     *
     * @param  string $column The column to find the min value.
     * @return mixed  The minimum value.
     */
    public function min($column)
    {
        return $this->executeWithTiming('min', func_get_args());
    }

    /**
     * Overrides the sum method to include performance monitoring.
     *
     * @param  string $column The column to sum.
     * @return mixed  The sum result.
     */
    public function sum($column)
    {
        return $this->executeWithTiming('sum', func_get_args());
    }

    /**
     * Overrides the avg method to include performance monitoring.
     *
     * @param  string $column The column to average.
     * @return mixed  The average result.
     */
    public function avg($column)
    {
        return $this->executeWithTiming('avg', func_get_args());
    }

    /**
     * Overrides the aggregate method to include performance monitoring.
     *
     * @param  string $function The aggregate function.
     * @param  array  $columns  The columns to aggregate.
     * @return mixed  The aggregate result.
     */
    public function aggregate($function, $columns = ['*'])
    {
        return $this->executeWithTiming('aggregate', func_get_args());
    }

    /**
     * Overrides the value method to include performance monitoring.
     *
     * @param  string $column The column to retrieve the value from.
     * @return mixed  The value of the column.
     */
    public function value($column)
    {
        return $this->executeWithTiming('value', func_get_args());
    }

    /**
     * Overrides the find method to include performance monitoring.
     *
     * @param  mixed             $id      The primary key.
     * @param  array|string      $columns The columns to retrieve.
     * @return Model|object|null The found model.
     */
    public function find($id, $columns = ['*'])
    {
        return $this->executeWithTiming('find', func_get_args());
    }

    /**
     * Overrides the findOrFail method to include performance monitoring.
     *
     * @param  mixed        $id      The primary key.
     * @param  array|string $columns The columns to retrieve.
     * @return Model|object The found model.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*'])
    {
        return $this->executeWithTiming('findOrFail', func_get_args());
    }
}
