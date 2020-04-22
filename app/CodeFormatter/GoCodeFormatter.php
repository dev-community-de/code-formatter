<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class GoCodeFormatter
 *
 * @package DevCommunityDE\CodeFormatter\CodeFormatter
 */
class GoCodeFormatter extends CodeFormatter
{

    /**
     * @var array
     */
    protected static $supported_languages = [
        'go',
    ];

    /**
     * @param string $file
     * @return string
     */
    protected function getShellCommand(string $file) : string
    {
        return 'gofmt -w ' . $file;
    }
}
