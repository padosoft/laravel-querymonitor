# Laravel QueryMonitor

![Laravel QueryMonitor](./resources/images/logo.webp)

Laravel QueryMonitor is a package for Laravel that allows you to monitor and log:

[![Latest Version on Packagist](https://img.shields.io/packagist/v/padosoft/laravel-querymonitor.svg?style=flat-square)](https://packagist.org/packages/padosoft/laravel-querymonitor)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![CircleCI](https://circleci.com/gh/padosoft/laravel-querymonitor.svg?style=shield)](https://circleci.com/gh/padosoft/laravel-querymonitor)
[![Quality Score](https://img.shields.io/scrutinizer/g/padosoft/laravel-querymonitor.svg?style=flat-square)](https://scrutinizer-ci.com/g/padosoft/laravel-querymonitor)
[![Total Downloads](https://img.shields.io/packagist/dt/padosoft/laravel-querymonitor.svg?style=flat-square)](https://packagist.org/packages/padosoft/laravel-querymonitor)


- **Slow SQL Queries**: Monitors the actual execution time of SQL queries on the database.
- **Slow Eloquent Methods**: Monitors the total time taken by Eloquent methods, including PHP processing.

## Requirements
- "php": ">=8.1",
- "illuminate/support": "^10.0|^11.0",
- "illuminate/database": "^10.0|^11.0",
- "illuminate/log": "^10.0|^11.0",
- "illuminate/config": "^10.0|^11.0"

## Installation
You can install the package via Composer:

```bash
composer require padosoft/laravel-querymonitor
```
Publish the configuration and migrations:

```bash
php artisan vendor:publish --provider="Padosoft\QueryMonitor\QueryMonitorServiceProvider" --tag="config"
```

## Configuration
The package configuration file is located at config/querymonitor.php. 
You can adjust the settings to suit your application's needs:
```php
return [

    'query' => [
        'attiva' => env('QUERYMONITOR_QUERY_ATTIVA', true),
        'maxExecutionTime' => env('QUERYMONITOR_QUERY_MAX_EXECUTION_TIME', 100), // in milliseconds
        'sqlRegEx' => env('QUERYMONITOR_QUERY_SQL_REGEX', '^SELECT.*$'),
    ],

    'query_builder' => [
        'attiva' => env('QUERYMONITOR_BUILDER_ATTIVA', true),
        'maxExecutionTime' => env('QUERYMONITOR_BUILDER_MAX_EXECUTION_TIME', 200), // in milliseconds
        'methodRegEx' => env('QUERYMONITOR_BUILDER_METHOD_REGEX', '^(get|first)$'),
    ],

];
```


## Usage

Once installed and configured, the package will automatically monitor and log SQL queries and Eloquent methods based on the provided settings.

### Monitoring SQL Queries
- **What it monitors**: The execution time of SQL queries on the database.
- **How it works**: Uses DB::listen() to listen to all executed queries.
- **When it logs**: If the execution time exceeds maxExecutionTime and the query matches sqlRegEx.

**Example Log Entry:**

```bash
[2024-11-28 12:34:56] local.INFO: QueryMonitor Slow SQL query detected {"query":"SELECT * FROM `users` WHERE `id` = '1'","execution_time":"150 ms"}
```

### Monitoring Eloquent Methods
- **What it monitors**: The total execution time of Eloquent methods, including PHP processing.
- **How it works**: Extends Eloquent's Builder and overrides key methods.
- **When it logs**: If the execution time exceeds maxExecutionTime and the method name matches methodRegEx.

**Example Log Entry:**

```bash
[2024-11-28 12:34:56] local.INFO: QueryMonitor: Slow Eloquent method detected {"method":"get","execution_time":"250 ms"}
```

## Difference Between Monitoring SQL Queries and Eloquent Methods
**SQL Queries**
- **Database-Focused**: Measures the time taken by the database to execute a query.
- **Usefulness**: Identifies unoptimized queries or database-level issues.
- **Example**: A query that takes 500 ms to execute on the database will be logged if it exceeds the threshold.

**Eloquent Methods**
- **Application-Focused**: Measures the total time taken by an Eloquent method, including PHP processing.
- **Usefulness**: Identifies bottlenecks in the application due to heavy PHP processing.
- **Example**: A get() method that retrieves many records and takes 800 ms to complete will be logged if it exceeds the threshold, even if the underlying SQL query is fast.

**Why Both?**
- **Complete Visibility**: By monitoring both SQL queries and Eloquent methods, you get a full view of performance, from the database to the application.
- **Effective Optimization**: You can identify if performance issues are due to the database or PHP code.

## Practical Example
Suppose we have the following code:

```php
$users = User::all();
```

- **SQL Query Execution Time**: 50 ms.
- **Total Eloquent Method Time**: 600 ms.

**Analysis:**

- **Fast SQL Query**: The database returns the data quickly.
- **Slow PHP Processing**: Creating User objects for many records takes time.
- **Action**: Consider using pagination or limiting the selected fields.


## Final Notes
- **Performance Optimization**: Remember that enabling monitoring can impact performance. It's advisable to use it in development environments or carefully monitor the impact in production.
- **Dynamic Configuration**: You can modify the settings in real-time using environment variables or by updating the configuration file.
- **Extensibility**: The package can be extended to include additional features or to suit specific needs.


## Testing
The package includes unit tests to ensure all components function correctly. 
Run tests using PHPUnit:

```bash
composer test
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email instead of using the issue tracker.

## Credits
- [Lorenzo Padovani](https://github.com/lopadova)
- [All Contributors](../../contributors)

## About Padosoft
Padosoft (https://www.padosoft.com) is a software house based in Florence, Italy. Specialized in E-commerce and web sites.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
