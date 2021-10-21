<?php

namespace Cybex\QueryTracer\Interfaces;

interface ArgumentFormatterInterface
{
    public function formatArgument($argument): string;

    public function formatStackFrameArguments(array $arguments): string;
}
