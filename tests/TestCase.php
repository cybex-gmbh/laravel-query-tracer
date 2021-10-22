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
    /**
     * @var string
     */
    protected $modelDir = '';


    /**
     * Set up the TestCase.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDatabase();
    }

    /**
     * Registers the QueryTracerServiceProvider and calls the pre-load handler.
     *
     * @param Application $app
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        $this->prePackageServiceProviderLoad($app);

        return [
            QueryTracerServiceProvider::class,
        ];
    }

    /**
     * Sets up the config and in-memory sqlite test database.
     *
     * @param Application $app
     */
    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'query-tracer-sqlite');
        config()->set('database.connections.query-tracer-sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        config()->set('query-tracer.backtrace.excludeFilesContaining', ['QueryTracer']);
    }


    /**
     * Creates the TestModel database.
     */
    protected function setUpDatabase(): void
    {
        $this->app->get('db')->connection()->getSchemaBuilder()
            ->create('test_models', function (Blueprint $table) {
                $table->increments('id');
                $table->string('message')->nullable();
                $table->timestamps();
            });
    }

    /**
     * Set the required environment before the QueryTracerServiceProvider is registered.
     *
     * @param Application $app
     */
    protected function prePackageServiceProviderLoad(Application $app): void
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

    /**
     * Copies the Models directory in some namespace under the app folder. This is for testing Model detection
     * and initialization via the Package Service Provider and thus must be done before loading the Provider.
     */
    protected function initializeModels($app): void
    {
        putenv('QUERY_TRACER_MODEL_NAMESPACE=' . str_replace('\\', '/', Str::after($app->path(), $this->modelDir)));

        $file = new Filesystem();

        if (!$file->exists($this->modelDir)) {
            $file->copyDirectory(
                $this->getDataDir('Models'),
                $this->modelDir
            );
        }
    }

    /**
     * Helper method to return the test data directory.
     *
     * @param string $directory
     * @return string
     */
    public function getDataDir(string $target = ''): string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, 'Data', $target]);
    }
}
