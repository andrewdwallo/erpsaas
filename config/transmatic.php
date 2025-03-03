<?php

use Wallo\Transmatic\Services\Translators\AwsTranslate;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Translation Service
    |--------------------------------------------------------------------------
    |
    | Controls the default service to use for translations. The "timeout"
    | option limits the wait time, in seconds, for a response from the
    | translation service. The "placeholder_format" option specifies the
    | format that your translation service uses for placeholders. For example,
    | if you set this to "#placeholder", then the translation service will
    | look for placeholders such as "#name", "#age", etc. Note that this format
    | is only for the translation service; you should continue using Laravel's
    | default ":placeholder" format when passing text to be translated. The
    | "supports_placeholders" flag indicates whether your translation service
    | is capable of supporting placeholders. Set to "true" if the service can
    | handle placeholders, or "false" otherwise.
    |
    */

    'translator' => [
        'default' => AwsTranslate::class,
        'timeout' => env('TRANSMATIC_TRANSLATOR_TIMEOUT', 30),
        'placeholder_format' => '#placeholder',
        'supports_placeholders' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Source Locale
    |--------------------------------------------------------------------------
    |
    | The source locale to be used for all translations. This is the language
    | code from which all translations to other languages will be made. This
    | must be the language that your application is written in.
    |
    */

    'source_locale' => env('TRANSMATIC_SOURCE_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Translation Storage
    |--------------------------------------------------------------------------
    |
    | The mechanism used for storing translations. You can choose between
    | either storing translations in the cache or in JSON language files.
    |
    | Supported: "cache", "file"
    |
    */

    'storage' => env('TRANSMATIC_STORAGE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for caching translations. The
    | "duration" specifies the number of days that translations should be
    | cached for. This can help improve performance by reducing redundant
    | translation operations. The "key" is the name of the base cache key that
    | will be used to store the translations. The locale will be appended to
    | this key.
    |
    */

    'cache' => [
        'key' => env('TRANSMATIC_CACHE_KEY', 'translations'),
        'duration' => env('TRANSMATIC_CACHE_DURATION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for storing translations in JSON. The
    | "path" specifies the directory where the JSON language files will be
    | stored. Defaults to "resources/data/lang" to bypass Laravel's auto-reload
    | feature for "lang" directories.
    |
    */

    'file' => [
        'path' => env('TRANSMATIC_FILE_PATH', 'resources/data/lang'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options for translation jobs. The "chunk_size"
    | specifies the number of text string per job, "max_attempts" specifies
    | the retry limit before marking a job as failed, and "retry_duration"
    | specifies the number of seconds to wait before retrying a failed job.
    |
    */

    'job' => [
        'chunk_size' => 200,
        'max_attempts' => 3,
        'retry_duration' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Batching Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection and queue for translation batches.
    | You may also specify whether or not to allow failures for the batch.
    |
    */

    'batching' => [
        'name' => 'TransmaticBatch',
        'connection' => env('TRANSMATIC_BATCHING_CONNECTION', 'database'),
        'queue' => env('TRANSMATIC_BATCHING_QUEUE', 'translations'),
        'allow_failures' => env('TRANSMATIC_BATCHING_ALLOW_FAILURES', true),
    ],
];
