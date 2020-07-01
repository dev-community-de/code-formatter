<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class ClangCodeFormatter
 *
 * @package DevCommunityDE\CodeFormatter\CodeFormatter
 */
class PrettierCodeFormatter extends CodeFormatter
{

    /**
     * @var array
     */
    protected static $supported_languages = [
        // Official support
        'css', // Todo
        'html', // Todo
        'javascript', // Todo
        'json', // Todo
        'less', // Todo
        'markdown', // Todo
        'scss', // Todo
        'yaml', // Todo
        // Official plugins
        'php', // Todo
        'ruby', // Todo
        'xml', // Todo
        // Unofficial plugins
        'java', // Todo
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
