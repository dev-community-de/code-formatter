<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class PrettierCodeFormatter
 *
 * @package DevCommunityDE\CodeFormatter\CodeFormatter
 */
class PrettierCodeFormatter extends CodeFormatter
{

    /**
     * @var array
     */
    protected static $supported_languages = [
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
     * Config is taken automatically from <code>.prettierrc</code> file
     * @param string $file
     * {@internal <code>--loglevel silent</code> does not override exit codes}
     * @return string
     */
    protected function getShellCommand(string $file) : string
    {
        return __DIR__ . '/../../node_modules/.bin/prettier --loglevel silent --write \'' . $file . '\'';
    }

}
