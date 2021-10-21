<?php

namespace Cybex\QueryTracer\Classes;

use Cybex\QueryTracer\Interfaces\ArgumentFormatterInterface;
use Illuminate\Database\Eloquent\Model;

class ArgumentFormatter implements ArgumentFormatterInterface
{

    /**
     * Formats a single argument according to its type.
     *
     * @param $argument
     * @return string
     */
    public function formatArgument($argument): string
    {
        switch (gettype($argument)) {
            case 'string':
                return sprintf("'%s'", $argument);
            case 'integer':
            case 'double':
                return $argument;
            case 'array':
                return 'Array[' . count($argument) . ']';
            case 'object':
                return get_class($argument) . ($argument instanceof Model ? ' (' . $argument->getKey() . ')' : '');
            default:
                return gettype($argument);
        }
    }


    /**
     * Formats the individual arguments for display in the call string.
     *
     * @param array $arguments
     * @return string
     */
    public function formatStackFrameArguments(array $arguments): string
    {
        return implode(
            ', ',
            array_map(
                function ($argument) {
                    return $this->formatArgument($argument);
                },
                $arguments
            )
        );
    }
}
