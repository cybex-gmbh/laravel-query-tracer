<?php

namespace Cybex\QueryTracer\Classes;

use Closure;
use Illuminate\Support\Collection;

class SqlCommentFormatter extends AbstractTraceFormatter
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Generates an SQL comment with all debug info ready to use in a query.
     *
     * @param Collection|null $trace
     *
     * @return string
     */
    public function format(?Collection $trace): ?string
    {
        if ($trace = $this->buildSqlCommentFromTrace($trace)) {
            $tag = $this->sanitizeCommentContent($this->config->getCommentTag());

            return implode(PHP_EOL, [
                '/*' . $tag,
                $this->sanitizeCommentContent(
                    $trace,
                    $this->config->getCommentReplacement(),
                    $this->config->getQuestionMarkReplacement()
                ),
                '*/',
            ]);
        }

        return null;
    }


    /**
     * Builds the SQL comment for the given trace using the configured template.
     *
     * @param Collection|null $trace
     *
     * @return string
     */
    protected function buildSqlCommentFromTrace(?Collection $trace): string
    {
        $template = '';

        if ($trace !== null &&
            ($template = $this->config->getCommentTemplate())) {
            $trace
                ->filter()
                ->each(function (&$traceValue, $traceKey) use (&$template, $trace) {
                    if (str_contains($template, '@' . $traceKey)) {
                        if ($traceValue instanceof Closure) {
                            $traceValue = $this->resolveClosure($traceKey, $traceValue, $trace);
                        }

                        if ($traceKey === 'file' && $trace->has('compiled') && $this->config->includeCompiledView()) {
                            $traceValue .= "\n(" . $trace->get('compiled') . ')';
                        }

                        $template = str_ireplace('@' . $traceKey, $traceValue ?? '', $template);
                    }
                });
        }

        return str_ireplace('@separator', str_repeat('-', $this->config->getLineLength()), $template);
    }

    /**
     * Sanitizes content for use within multi-line comments by replacing any comment closing sequence with $replaceWith.
     *
     * @param string $content
     * @param string $commentReplacement
     * @param string|null $questionMarkReplacement
     *
     * @return string
     */
    protected function sanitizeCommentContent(
        string $content,
        string $commentReplacement = '',
        string $questionMarkReplacement = '?'
    ): string {
        return str_replace(['*/', '?'], [$commentReplacement, $questionMarkReplacement], $content);
    }
}
