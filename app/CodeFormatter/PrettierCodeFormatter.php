<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class PrettierCodeFormatter.
 */
class PrettierCodeFormatter extends CodeFormatter
{
    protected const LANGUAGES = [
        'css',
        'html',
        'js',
        'json',
        'less',
        'md',
        'scss',
        'yaml',
    ];

    /**
     * Config is taken automatically from <code>.prettierrc</code> file.
     *
     * {@internal <code>--loglevel silent</code> does not override exit codes}
     *
     * @param string $file
     *
     * @return string
     */
    protected function getShellCommand(string $file): string
    {
        $fileArg = escapeshellarg($file);
        $confArg = escapeshellarg(realpath(__DIR__ . '/../../.prettierrc'));
        return 'npx prettier --loglevel silent --config ' . $confArg . ' --write ' . $fileArg;
    }
}
