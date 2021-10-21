<?php

namespace Cybex\QueryTracer\Classes;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class AbstractSourceCodeFormatter
{
    /**
     * Cache for source code listings.
     * @var array
     */
    protected $sourceCache;

    /**
     * @var Config
     */
    protected $config;


    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }


    /**
     * Retrieves the formatted source code from the specified file around the given line.
     *
     * @param string $fileName
     * @param int $line
     *
     * @return string|null
     */
    public function getSourceAt(string $fileName, int $line): ?string
    {
        if ($this->config->shouldIncludeSource()) {
            if ($cached = $this->sourceCache[$fileName][$line] ?? false) {
                return $cached;
            }

            $sourceCode = file($fileName);
            $numSourceLines = $this->config->getSourceLineCount();
            $startingLine = $line - round($numSourceLines / 2);

            while ($startingLine > 1 && trim($sourceCode[$startingLine - 1] ?? '') === '') {
                $startingLine--;
                $numSourceLines++;
            }

            $lineCounter = $startingLine;
            $codeSlice   = collect(array_slice($sourceCode, $startingLine, $numSourceLines))->mapWithKeys(
                function ($line) use (&$lineCounter) {
                    return [++$lineCounter => $line];
                }
            );

            return $this->sourceCache[$fileName][$line] = $this->getFormattedCode($codeSlice, $line);
        }

        return null;
    }


    /**
     * Formats the source code listing to include in the trace.
     *
     * @param Collection $code
     * @param int $highlightedLine
     *
     * @return string
     */
    public function getFormattedCode(Collection $code, int $highlightedLine): string
    {
        $code = $this->removeExpendableIndentation($code);
        $code = $this->removeSurroundingEmptyLines($code);
        $code = $this->addLineNumbers($code, $highlightedLine);

        return rtrim($code->implode(''));
    }


    /**
     * Removes all indentation that can safely be trimmed off the beginning of the given code.
     *
     * @param Collection $code
     *
     * @return Collection
     */
    protected function removeExpendableIndentation(Collection $code): Collection
    {
        $indentation = null;

        return $code
            ->transform(function ($line) {
                return $this->formatLine($line, false);
            })
            ->each(function ($line) use (&$minIndentation) {
                $formatted = $this->formatLine($line, false);

                if (trim($formatted) !== '') {
                    $trimmedLength = strlen(ltrim($formatted, ' '));
                    $indentation   = strlen($formatted) - $trimmedLength;

                    if (is_null($minIndentation) || $indentation < $minIndentation) {
                        $minIndentation = $indentation;
                    }
                }

                return $formatted;
            })
            ->transform(function ($line) use ($indentation) {
                return substr($line, $indentation ?? 0);
            });
    }


    /**
     * Formats the given line according to the maximum line length.
     *
     * @param string $line
     * @param bool $trim
     * @param bool $highlightSpaces
     *
     * @return string
     */
    protected function formatLine(string $line, bool $trim = true, bool $highlightSpaces = false): string
    {
        // Decrease the maximum line length by 11 to take into account the added ... and line numbers.
        $maxLineLength = config('query-tracer.trace.sqlComment.lineLength', 80) - 11;

        if ($highlightSpaces) {
            $line = str_replace('  ', '⸱⸱', $line);
        }

        return Str::finish(Str::limit($trim ? trim($line) : rtrim($line), $maxLineLength), PHP_EOL);
    }


    /**
     * Adds line numbers to the given code lines.
     *
     * @param Collection $code
     * @param int $highlightedLine
     *
     * @return mixed
     */
    protected function addLineNumbers(Collection $code, int $highlightedLine): Collection
    {
        return $code->map(function ($line, $lineNumber) use ($highlightedLine) {
            $decoration = $lineNumber === $highlightedLine ? $this->config->getHighlightLineDecoration() : "\u{200B} ";

            return sprintf('%s %04d  %s', $decoration, $lineNumber, $line);
        });
    }


    /**
     * Removes all leading and trailing empty lines from the given code.
     *
     * @param Collection $code
     *
     * @return Collection
     */
    public function removeSurroundingEmptyLines(Collection $code): Collection
    {
        // Remove all leading empty lines.
        while ($code->count() && trim($code->first()) === '') {
            $code->shift();
        }

        // Remove all trailing empty lines.
        do {
            $line = $code->pop();
        } while ($line !== null && trim($line) === '');

        // Push back the last line that was non-empty.
        if (trim($line) !== '') {
            $code->push($line);
        }

        return $code;
    }
}
