<?php
/**
 * @see https://github.com/cybex/laravel-query-tracer
 */

use Cybex\QueryTracer\Classes\ArgumentFormatter;
use Cybex\QueryTracer\Classes\LogArrayFormatter;
use Cybex\QueryTracer\Classes\SourceCodeFormatter;
use Cybex\QueryTracer\Classes\SqlCommentFormatter;

return [

    /*
    |--------------------------------------------------------------------------
    | Main Switch
    |--------------------------------------------------------------------------
    |
    | Query Tracer will only be active if enabled is set to true.
    |
    | If set to false, Query Tracer will not initialize at all during app
    | bootstrapping. Setting this to true during runtime will not start
    | logging. If you want to dynamically start and stop logging, set
    | this to true and try the trace.enabled settings instead.
    |
    */

    'enabled' => env('QUERY_TRACER_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | Query Tracer will only work in environments specified here. For all other
    | environments, Query Tracer will always be disabled, regardless of its
    | enabled state.
    |
    | It is not recommended to use Query Tracer in production environments!
    |
    */

    'allowedEnvironments' => [
        'local',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mode of Operation
    |--------------------------------------------------------------------------
    |
    | By default, Query Tracer will not touch the queries that are sent to the
    | servers, but will add an SQL comment just before the QueryExecuted event
    | is dispatched.
    |
    | There is an alternative mode using scopes that will alter every query
    | before it is sent off to the SQL server. If you want to include the
    | traces in SQL server logs (for example in slow query logs), use the
    | alternative 'scoped' mode.
    |
    | Supported modes: 'default', 'scoped'.
    */

    'mode' => env('QUERY_TRACER_MODE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Model Namespace
    |--------------------------------------------------------------------------
    |
    | This option is only required when running in scoped mode. Query Tracer
    | will add a global scope to all Models, and will fetch all Models using
    | the file system.
    |
    | The model namespace is relative to \App, and it will be expected that
    | the models' namespaces follow file locations in compliance with PSR-4.
    |
    | If your app follows the current Laravel convention to put the models in
    | the App/Models namespace (and folder), change this to this value to
    | 'Models' to speed up the scan process. If you are not sure what to
    | set, leave this setting empty.
    |
    */

    'modelNamespace' => env('QUERY_TRACER_MODEL_NAMESPACE', ''),

    /*
    |--------------------------------------------------------------------------
    | Restrict to Driver                                    (default mode only)
    |--------------------------------------------------------------------------
    |
    | QueryTracer only attaches to one database driver per script run.
    |
    | This option is used to have Query Tracer explicitly attach to the
    | specified driver only. For example, you can set this to 'sqlite'
    | to trace all queries to any sqlite database while ignoring any
    | queries to mysql, pgsql or sqlsrv databases.
    |
    | By default, Query Tracer will restrict itself to the driver of the
    | application's default connection. If set to '*', it will restrict
    | itself to the first established database connection instead.
    |
    */

    'restrictToDriver' => env('QUERY_TRACER_DRIVER', '*'),


    /*
    |--------------------------------------------------------------------------
    | Debug Backtrace Configuration
    |--------------------------------------------------------------------------
    |
    | These settings allow you to fine-tune the backtrace that is chosen for
    | inclusion in the queries.
    |
    | These settings have fundamental effect on the quality of the trace and if
    | there is any trace appearing in your queries at all, so use with caution.
    |
    */

    'backtrace' => [

        /*
        |--------------------------------------------------------------------------
        | Backtrace Frame Limit
        |--------------------------------------------------------------------------
        |
        | Setting this limit to any non-null value greater than 0 will limit the
        | amount of stack frames to parse. Depending on the application and your
        | restriction settings, a good value might be between 8 and 25. If in
        | doubt, leave this setting unlimited as long as you don't experience
        | serious performance or memory consumption issues.
        |
        */
        'limit' => null,

        /*
        |--------------------------------------------------------------------------
        | Include Arguments
        |--------------------------------------------------------------------------
        |
        | If set to true, this setting will include a call's arguments in the
        | trace. If you don't care for the arguments and only want to see the
        | called functions and their locations, you can set this false to
        | save memory.
        |
        */

        'withArgs' => true,

        /*
        |--------------------------------------------------------------------------
        | Backtrace Restrictions
        |--------------------------------------------------------------------------
        |
        | This setting restricts the stack frame selection to files within the
        | application frame.
        |
        | You will usually want to include the app directory as well as the
        | storage directories for queries fired from compiled views.
        |
        */

        'includeFilesContaining' => [
            app_path(),
            storage_path(),
        ],

        /*
        |--------------------------------------------------------------------------
        | Backtrace Exclusions
        |--------------------------------------------------------------------------
        |
        | This setting controls which files are excluded by specifying terms that
        | must not appear in the path or file name within the stack frame.
        |
        */

        'excludeFilesContaining' => [base_path('vendor')],

        /*
        |--------------------------------------------------------------------------
        | Stack Frame Call Argument Formatter
        |--------------------------------------------------------------------------
        |
        | This setting excludes files containing any of the configured terms in
        | their name from the backtrace.
        |
        */

        'argumentFormatter' => ArgumentFormatter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Trace Configuration
    |--------------------------------------------------------------------------
    |
    | This section configures the structure and appearance of the actual trace
    | that will be included in the queries.
    |
    */

    'trace' => [

        /*
        |--------------------------------------------------------------------------
        | Include Compiled View Filename
        |--------------------------------------------------------------------------
        |
        | If set to true, the file name of the compiled view will be shown
        | additionally to the source template file name.
        |
        | This can help finding the source of a query in a view, since the line
        | numbers always refer to the compiled view, which may be completely
        | differently structured that the original blade templates.
        |
        */

        'includeCompiledView' => true,

        /*
        |--------------------------------------------------------------------------
        | List Source Code
        |--------------------------------------------------------------------------
        |
        | Here you may enable the inclusion of the actual source code of and around
        | the originating call in the query trace by setting includeSource to true.
        |
        | The includeSourceLines settings controls how many source code lines
        | should be displayed around the line with the originating call.
        |
        | The actual number of lines displayed may be lower than the setting,
        | because surrounding empty lines are removed for brevity.
        |
        */

        'includeSource'     => true,

        /*
        |--------------------------------------------------------------------------
        | Number of Source Code Lines Around Target Line
        |--------------------------------------------------------------------------
        |
        | Here you can specify how many lines of source code should be displayed
        | before and after the target line of the stack frame.
        |
        */

        'sourceLinesAround' => 4,

        /*
        |--------------------------------------------------------------------------
        | Source Code Formatter
        |--------------------------------------------------------------------------
        |
        | This setting excludes files containing any of the configured terms in
        | their name from the backtrace.
        |
        */

        'sourceCodeFormatter' => SourceCodeFormatter::class,

        /*
        |--------------------------------------------------------------------------
        | Originating Source Line Highlighting
        |--------------------------------------------------------------------------
        |
        | This sets the character to use to highlight the originating source line
        | in the source code listing.
        |
        */

        'highlightLineDecoration' => '*',

        /*
        |--------------------------------------------------------------------------
        | Query Log Array Trace                                 (default mode only)
        |--------------------------------------------------------------------------
        |
        | Here you may specify whether you wish to include info in the DB QueryLog
        | array, how the key should be named, and which info to log. The available
        | selectors are named like the placeholders used in the Trace Template.
        |
        */

        'logArray' => [

            'enabled' => env('QUERY_TRACER_ENABLE_ARRAY_TRACE', true),

            /*
            |--------------------------------------------------------------------------
            | Log Array Formatter
            |--------------------------------------------------------------------------
            |
            | This option specifies the class to use for formatting the array key which
            | is added to the DB Query Log.
            |
            | The class must extend AbstractTraceFormatter and return an array.
            |
            */

            'formatter' => LogArrayFormatter::class,

            /*
            |--------------------------------------------------------------------------
            | Log Array Key
            |--------------------------------------------------------------------------
            |
            | Here you may specify the name of the key to use inside the DB Query Log
            | for adding the trace array.
            |
            */

            'key' => env('QUERY_TRACER_LOG_KEY', 'trace'),


            /*
            |--------------------------------------------------------------------------
            | Log Array Values
            |--------------------------------------------------------------------------
            |
            | Here you may set the trace values to include in the Query Log. You can
            | use '*' to always include all available values. Please consult the
            | documentation for a full list of recognized placeholders.
            |
            | @see https://github.com/cybex/laravel-query-tracer
            |
            */

            'values' => [
                'call',
                'class',
                'file',
                'compiled',
                'function',
                'line',
                'source',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | SQL Comment Trace
        |--------------------------------------------------------------------------
        |
        | Here you can enable or disable adding SQL comments to your queries and
        | configure their appearance.
        |
        | In default mode these will be added to the logged queries only,
        | while in scoped mode they will be added to the original queries
        | sent to the SQL server.
        |
        */

        'sqlComment' => [

            'enabled' => env('QUERY_TRACER_ENABLE_COMMENT_TRACE', true),

            /*
            |--------------------------------------------------------------------------
            | SQL Comment Formatter
            |--------------------------------------------------------------------------
            |
            | This option specifies the class to use for formatting the SQL comments.
            | The class must extend AbstractTraceFormatter and return a string.
            |
            */

            'formatter' => SqlCommentFormatter::class,

            /*
            |--------------------------------------------------------------------------
            | Character Replacements
            |--------------------------------------------------------------------------
            |
            | Here you can specify how problematic characters within the SQL will be
            | replaced. It is important to replace any comment closing sequence, as
            | these would break your queries otherwise. Also, you may want to
            | replace all question marks in your trace, so that they will not
            | be accidentally replaced in QueryExceptions or Telescope.
            |
            */

            'replacements' => [
                'questionMark' => "\u{FF1F}",
                'comment'      => '* /',
            ],

            /*
            |--------------------------------------------------------------------------
            | Maximum line length
            |--------------------------------------------------------------------------
            |
            | This specifies the maximum length in characters of a single line within
            | the multi-line comment. Longer lines will be shortened.
            |
            */

            'lineLength' => 120,

            /*
            |--------------------------------------------------------------------------
            | Tag
            |--------------------------------------------------------------------------
            |
            | You may specify a tag that will be appended right behind the opening /*
            | of the comment block, followed by a newline.
            |
            */

            'tag' => env('QUERY_TRACER_TAG', ''),

            /*
            |--------------------------------------------------------------------------
            | Trace SQL Comment Template
            |--------------------------------------------------------------------------
            |
            | You may customize the output included in each SQL query. Please consult
            | the documentation for a full list of recognized placeholders.
            |
            | @see https://github.com/cybex/laravel-query-tracer
            |
            */

            'template' => '
            @file
            @separator
            function @function
            Line: @line

            [@class]
            @call

            ······
            @source
            ······

            @separator
            ',
        ],
    ],
];
