<?php

namespace Padosoft\QueryMonitor\Test\Unit;

use Illuminate\Log\Logger;
use Monolog\Handler\StreamHandler;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Padosoft\QueryMonitor\QueryMonitorServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QueryMonitorTest extends TestCase
{
    use RefreshDatabase; // Use the trait to refresh the database between tests

    protected function getPackageProviders($app)
    {
        return [
            QueryMonitorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Configure the in-memory SQLite database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Configurazione per i test (da inserire qui perchè poi il serviceProvider lo ha già caricato idem il builder)
        Config::set('querymonitor.query.attiva', true);
        Config::set('querymonitor.query.maxExecutionTime', 0);
        Config::set('querymonitor.query.sqlRegEx', '^.*$');

        Config::set('querymonitor.query_builder.attiva', true);
        Config::set('querymonitor.query_builder.maxExecutionTime', 0);
        Config::set('querymonitor.query_builder.methodRegEx', '^.*$');
    }

    /** @test */
    public function it_logs_slow_sql_queries()
    {
        // Create a test table
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        DB::table('tests')->insert(['name' => 'Test']);

        // Redirect logs to a temporary file
        $logFile = storage_path('logs/querymonitor_test.log');
        echo $logFile;
        File::delete($logFile);
        Log::swap(new Logger(
            new \Monolog\Logger('test', [new StreamHandler($logFile)])
        ));

        // Execute a SELECT query
        DB::table('tests')->where('id', 1)->get();

        // Verify that the log was written
        $this->assertFileExists($logFile);
        $logContents = File::get($logFile);
        $this->assertStringContainsString('Slow SQL query detected', $logContents);

        // Clean up the log file
        File::delete($logFile);
    }

    /** @test */
    public function it_logs_slow_eloquent_methods()
    {
        // Create a test table
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        DB::table('test_models')->insert(['name' => 'Test']);

        // Define a temporary model
        $modelCode = <<<EOD
        <?php

        namespace Padosoft\QueryMonitor\Tests\Models;

        use Illuminate\Database\Eloquent\Model;
        use Padosoft\QueryMonitor\Builders\QueryMonitorBuilder;

        class TestModel extends Model
        {
            protected \$table = 'test_models';
            protected \$guarded = [];

            public function newEloquentBuilder(\$query): QueryMonitorBuilder
            {
                return new QueryMonitorBuilder(\$query);
            }
        }
        EOD;

        // Save the temporary model
        $modelsPath = __DIR__ . '/Models';
        if (!file_exists($modelsPath)) {
            mkdir($modelsPath, 0777, true);
        }
        file_put_contents($modelsPath . '/TestModel.php', $modelCode);

        // Autoload the model namespace
        spl_autoload_register(function ($class) use ($modelsPath) {
            $class = str_replace('Padosoft\\QueryMonitor\\Tests\\Models\\', '', $class);
            $file = $modelsPath . '/' . $class . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });

        // Redirect logs to a temporary file
        $logFile = storage_path('logs/querymonitor_test.log');
        File::delete($logFile);
        Log::swap(new Logger(
            new \Monolog\Logger('test', [new StreamHandler($logFile)])
        ));

        // Execute an Eloquent method
        $model = \Padosoft\QueryMonitor\Tests\Models\TestModel::where('id', 1)->get();

        // Verify that the log was written
        $this->assertFileExists($logFile);
        $logContents = File::get($logFile);
        $this->assertStringContainsString('Slow Eloquent method detected', $logContents);

        // Clean up the log file and model file
        File::delete($logFile);
        File::delete($modelsPath . '/TestModel.php');
        rmdir($modelsPath);
    }
}
