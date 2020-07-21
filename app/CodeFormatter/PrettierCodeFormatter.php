<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class PrettierCodeFormatter.
 */
class PrettierCodeFormatter extends CodeFormatter
{
    private const USE_PRETTIERD = true;

    /**
     * @var array
     */
    protected const LANGUAGES = [
        'css',
        'html',
        'javascript',
        'json',
        'less',
        'markdown',
        'scss',
        'yaml',
        'php',
    ];

    /**
     * Config is taken automatically from <code>.prettierrc</code> file.
     *
     * @internal <code>--loglevel silent</code> does not override exit codes
     *
     * @return string
     */
    protected function getShellCommand(): string
    {
        $filename = 'format.';

        switch ($this->language) {
            case 'javascipt':
                $filename .= 'js';
                break;
            case 'markdown':
                $filename .= 'md';
                break;
            default:
                $filename .= $this->language;
        }

        if (self::USE_PRETTIERD) {
            return __DIR__ . '/../../prettierd.js -c -f ' . $filename;
        }

        return 'npx prettier --loglevel silent --stdin --stdin-filepath ' . $filename;
    }
}
