<?php

namespace Cybex\QueryTracer\Tests;

use Cybex\QueryTracer\QueryTracerServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected $modelDir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDatabase();
    }

    protected function getPackageProviders($app)
    {
        $this->prePackageServiceProviderLoad($app);

        return [
            QueryTracerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'query-tracer-sqlite');
        config()->set('database.connections.query-tracer-sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        config()->set('query-tracer.backtrace.excludeFilesContaining', ['QueryTracer']);
    }

    protected function setUpDatabase()
    {
        $this->app->get('db')->connection()->getSchemaBuilder()
            ->create('test_models', function (Blueprint $table) {
                $table->increments('id');
                $table->string('message')->nullable();
                $table->timestamps();
            });
    }

    /**
     * Copies the Models directory in some namespace under the app folder. This is for testing Model detection
     * and initialization via the Package Service Provider and thus must be done before loading the Provider.
     */
    protected function initializeModels($app)
    {
        putenv('QUERY_TRACER_MODEL_NAMESPACE=' . str_replace('\\', '/', Str::after($app->path(), $this->modelDir)));

        $file = new Filesystem();

        if (!$file->exists($this->modelDir)) {
            $file->copyDirectory(
                implode(DIRECTORY_SEPARATOR, [__DIR__, 'Models']),
                $this->modelDir
            );
        }
    }

    protected function prePackageServiceProviderLoad(Application $app)
    {
        // Make app()->environment return whatever we set in APP_ENV.
        $app->detectEnvironment(function () {
            return env('APP_ENV');
        });

        Config::set(
            'query-tracer.allowedEnvironments',
            explode(',', env('QUERY_TRACER_ALLOWED_ENVIRONMENTS', 'testing'))
        );

        $this->modelDir = $app->path('Some/Obscure/Path/To/Models');

        $this->initializeModels($app);
    }
}
