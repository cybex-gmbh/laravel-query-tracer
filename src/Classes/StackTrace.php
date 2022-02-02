<?php

namespace Cybex\QueryTracer\Classes;

use ArrayIterator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StackTrace
{
    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }


    /**
     * Picks the most useful entry from the debug backtrace and returns the data in a Collection.
     *
     * @return Collection|null
     */
    public function getTrace(): ?Collection
    {
        if ($stackFrameIterator = $this->getLastMatchingStackFrame()) {
            $stackFrame = $stackFrameIterator->current();

            $stackFrameIterator->next();

            $previousStackFrame = $stackFrameIterator->valid() ? $stackFrameIterator->current() : null;

            $line         = $stackFrame['line'];
            $filePath     = $stackFrame['file'];
            $compiledFile = null;

            if ($this->isCompiledView($filePath)) {
                $compiledFile = $filePath;
                $filePath     = $this->getOriginalTemplateFromCompiledView($compiledFile);

                // Resolve from stack trace if this didn't work.
                if ($this->isCompiledView($filePath) || !file_exists($filePath)) {
                    $filePath = $this->getOriginalTemplate($stackFrameIterator);
                }
            }

            [$file, $compiledFile] = str_replace(base_path(), '', [$filePath, $compiledFile]);

            return collect(
                array_filter(
                    [
                        'call'     => $this->getCall($stackFrame),
                        'class'    => $stackFrame['class'] ?? null,
                        'file'     => $file,
                        'compiled' => $compiledFile,
                        'function' => ($previousStackFrame && !$compiledFile) ? $this->getCall(
                            $previousStackFrame,
                            false
                        ) : '--',
                        'line'     => $line,
                        'source'   => function () use ($filePath, $compiledFile, $line) {
                            return $filePath ? app('trace.formatter.sourcecode')->getSourceAt(
                                $filePath,
                                $line
                            ) : null;
                        },
                    ]
                )
            );
        }

        return null;
    }


    /**
     * Searches for the latest stack frame that matches the criteria and returns the iterator at that position.
     *
     * @return ArrayIterator|null
     */
    public function getLastMatchingStackFrame(): ?ArrayIterator
    {
        $includeContaining = (array)config('query-tracer.backtrace.includeFilesContaining');
        $excludeContaining = (array)config('query-tracer.backtrace.excludeFilesContaining');

        $fullBacktrace = debug_backtrace(
            DEBUG_BACKTRACE_PROVIDE_OBJECT | (config('query-tracer.backtrace.withArgs') ? false : DEBUG_BACKTRACE_IGNORE_ARGS),
            config('query-tracer.backtrace.limit')
        );

        $stackFrameIterator = new ArrayIterator($fullBacktrace);

        while ($stackFrameIterator->valid()) {
            $stackFrame = $stackFrameIterator->current();

            if (($stackFrame['class'] ?? '') !== static::class &&
                array_key_exists('file', $stackFrame) &&
                (!count($includeContaining) || Str::contains($stackFrame['file'], $includeContaining)) &&
                !Str::contains($stackFrame['file'], $excludeContaining)
            ) {
                return $stackFrameIterator;
            }

            $stackFrameIterator->next();
        }

        return null;
    }


    /**
     * Combines the object, function and arguments into a single call string.
     *
     * @param array $stackFrame
     * @param bool $withClass
     * @return string
     */
    protected function getCall(array $stackFrame, bool $withClass = true): string
    {
        if ($withClass && ($object = $stackFrame['object'] ?? '')) {
            if (is_subclass_of($object, Relation::class)) {
                $object = $object->getRelated();
            } elseif (is_a($object, Builder::class)) {
                $object = $object->getModel();
            }

            $object = get_class($object) . '::';
        }

        $args = '';

        if (is_array($stackFrame['args']) && count($stackFrame['args'])) {
            $args = app('trace.formatter.argument')->formatStackFrameArguments(array_values($stackFrame['args']));
        }

        return implode('', [$object ?? '', $stackFrame['function'], '(', $args, ')']);
    }


    /**
     * Resolves the last used non-compiled template file from the trace.
     *
     * @param ArrayIterator $stackFrameIterator
     * @return string|null
     */
    protected function getOriginalTemplate(ArrayIterator $stackFrameIterator): ?string
    {
        while ($stackFrameIterator->valid()) {
            $stackFrameIterator->next();

            $stackFrame = $stackFrameIterator->current();

            if (str_contains($stackFrame['file'] ?? '', '/Illuminate/View/View.php')) {
                return Arr::first(array_values($stackFrame['args'] ?? [])) ?? null;
            }
        }

        return null;
    }


    /**
     * Returns true if the given file represents a compiled view, false if not.
     *
     * @param string $file
     * @return bool
     */
    protected function isCompiledView(string $file): bool
    {
        return str_contains($file, 'storage/framework/views');
    }


    /**
     * Tries to extract the original template filename from the compiled view.
     *
     * @param $compiledFile
     * @return string
     */
    protected function getOriginalTemplateFromCompiledView($compiledFile): string
    {
        $fh = fopen($compiledFile, 'r');
        fseek($fh, -255, SEEK_END);
        $lastLine = fread($fh, 255);

        return trim(Str::between($lastLine, '/**PATH', 'ENDPATH**/'));
    }
}
