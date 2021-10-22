<?php

namespace Cybex\QueryTracer\Classes;

class Config
{
    public function includeCompiledView(): bool
    {
        return config('query-tracer.trace.includeCompiledView');
    }

    public function getLogArrayValues(): array
    {
        return (array)config('query-tracer.trace.logArray.values');
    }

    public function shouldIncludeSource(): bool
    {
        return config('query-tracer.trace.includeSource') === true;
    }

    public function getCommentTag(): string
    {
        return config('query-tracer.trace.sqlComment.tag');
    }

    public function getCommentTemplate(): ?string
    {
        return config('query-tracer.trace.sqlComment.template');
    }

    public function getLineLength(): int
    {
        return config('query-tracer.trace.sqlComment.lineLength', 80);
    }

    public function getSourceLinesAround(): int
    {
        return config('query-tracer.trace.sourceLinesAround');
    }

    public function getHighlightLineDecoration(): string
    {
        return substr(config('query-tracer.trace.highlightLineDecoration', '*'), 0, 1);
    }

    public function getCommentReplacement(): string
    {
        return str_replace('*/', '* /', config('query-tracer.trace.sqlComment.replacements.comment'));
    }

    public function getQuestionMarkReplacement(): string
    {
        return config('query-tracer.trace.sqlComment.replacements.questionMark', '?');
    }

}
