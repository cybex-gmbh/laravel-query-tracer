<?php

namespace Cybex\QueryTracer;

use Cybex\QueryTracer\Classes\AbstractSourceCodeFormatter;
use Cybex\QueryTracer\Classes\AbstractTraceFormatter;
use Cybex\QueryTracer\Classes\ArgumentFormatter;
use Cybex\QueryTracer\Classes\Config as QueryTracerConfig;
use Cybex\QueryTracer\Classes\LogArrayFormatter;
use Cybex\QueryTracer\Classes\SourceCodeFormatter;
use Cybex\QueryTracer\Classes\SqlCommentFormatter;
use Cybex\QueryTracer\Interfaces\ArgumentFormatterInterface;
use Cybex\QueryTracer\Scopes\QueryTracerScope;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;

class QueryTracerServiceProvider extends ServiceProvider
{

    /**
     * Boots the Query Tracer.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../config/query-tracer.php' => config_path('query-tracer.php'),
            ],
            'config'
        );
    }

    /**
     * Register the Query Tracer services.
     *
     * @return void
     */
    public function register(): void
    {
        $config = new QueryTracerConfig();

        $this->mergeConfigFrom(
            __DIR__ . '/../config/query-tracer.php',
            'query-tracer'
        );

        if ($this->shouldRun()) {
            $this->initializeFormatters($config);

            if (Config::get('query-tracer.mode') === 'scoped') {
                if (Config::get('query-tracer.trace.sqlComment.enabled')) {
                    $this->registerScopeOnAllModels();
                }
            } else {
                $this->replaceConnectionFactory();
            }
        }
    }

    /**
     * Registers the QueryTracerScope on all models in the application.
     *
     * @return void
     */
    protected function registerScopeOnAllModels(): void
    {
        $this->getAllModels()
            ->each(function ($model) {
                $model::addGlobalScope(new QueryTracerScope());
            });
    }

    /**
     * Returns a Collection of all available Models via the Filesystem.
     *
     * @param bool $withoutAbstract if true, do not include abstract classes in the Collection.
     * @param bool $withoutLeadingBackslash if true, the namespaces will not be prefixed by a backslash (what SomeClass::class would return).
     *
     * @return Collection
     */
    protected function getAllModels(bool $withoutAbstract = true, bool $withoutLeadingBackslash = false): Collection
    {
        $appNamespace = Container::getInstance()->getNamespace();
        $modelNamespace = config('query-tracer.model-namespace', '');

        return collect(
            File::allFiles(app_path(str_replace('\\', DIRECTORY_SEPARATOR, $modelNamespace)))
        )->map(
            function ($item) use ($appNamespace, $modelNamespace, $withoutAbstract, $withoutLeadingBackslash) {
                $class = sprintf(
                    ($withoutLeadingBackslash ? '' : '\\') . '%s%s%s',
                    $appNamespace,
                    $modelNamespace ? $modelNamespace . '\\' : '',
                    implode('\\', explode('/', Str::beforeLast($item->getRelativePathname(), '.')))
                );

                return class_exists($class) && is_subclass_of($class, Model::class) &&
                ($withoutAbstract === false || (new ReflectionClass($class))->isAbstract() === false) ? $class : null;
            }
        )->filter();
    }

    /**
     * Replaces the original db.factory with our own implementation.
     *
     * @return void
     */
    protected function replaceConnectionFactory(): void
    {
        app()->singleton('db.factory', function ($app) {
            return new QueryTracerConnectionFactory($app);
        });
    }

    /**
     * Returns true if the Query Tracer should be initialized, false if not.
     *
     * @return bool
     */
    protected function shouldRun(): bool
    {
        return Config::get('query-tracer.enabled') && in_array(
                app()->environment(),
                Config::get('query-tracer.allowedEnvironments')
            );
    }

    /**
     * Registers the configured trace formatters.
     *
     * @param QueryTracerConfig $config
     *
     * @return void
     */
    protected function initializeFormatters(QueryTracerConfig $config): void
    {
        $this->app->singleton(
            'trace.formatter.array',
            function () use ($config) {
                $arrayFormatter = Config::get('query-tracer.trace.logArray.formatter', LogArrayFormatter::class);

                $this->guardValidFormatterClass($arrayFormatter, AbstractTraceFormatter::class, 'array');

                return new $arrayFormatter($config);
            }
        );

        $this->app->singleton(
            'trace.formatter.sql',
            function () use ($config) {
                $sqlFormatter = Config::get('query-tracer.trace.sqlComment.formatter', SqlCommentFormatter::class);

                $this->guardValidFormatterClass($sqlFormatter, AbstractTraceFormatter::class, 'sql');

                return new $sqlFormatter($config);
            }
        );

        $this->app->singleton('trace.formatter.argument', function () use ($config) {
            $argumentFormatter = Config::get('query-tracer.backtrace.argumentFormatter', ArgumentFormatter::class);

            $this->guardValidFormatterClass($argumentFormatter, ArgumentFormatterInterface::class, 'argument');

            return new $argumentFormatter($config);
        });

        $this->app->singleton('trace.formatter.sourcecode', function () use ($config) {
            $sourceCodeFormatter = Config::get('query-tracer.trace.sourceCodeFormatter', SourceCodeFormatter::class);

            $this->guardValidFormatterClass($sourceCodeFormatter, AbstractSourceCodeFormatter::class, 'argument');

            return new $sourceCodeFormatter($config);
        });
    }

    /**
     * Throws an exception if the given formatter is not of the expected class.
     *
     * @param $formatterClass
     * @param string $expectedClass
     * @param string $type
     *
     * @return void
     */
    protected function guardValidFormatterClass($formatterClass, string $expectedClass, string $type): void
    {
        if (!(class_exists($formatterClass) && is_subclass_of($formatterClass, $expectedClass))) {
            throw new InvalidArgumentException(
                sprintf('Invalid query tracer %s formatter class %s.', $type, $formatterClass)
            );
        }
    }
}
