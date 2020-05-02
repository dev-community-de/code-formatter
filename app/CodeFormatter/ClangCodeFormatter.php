<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

use DevCommunityDE\CodeFormatter\CodeFormatter\CodeFormatter;

/**
 * Class ClangCodeFormatter
 *
 * @package DevCommunityDE\CodeFormatter\CodeFormatter
 */
class ClangCodeFormatter extends CodeFormatter
{

    /**
     * @var array
     */
    protected static $supported_languages = [
        'c',
        'cpp',
    ];

    /**
     * @param string $file
     * @return string
     */
    protected function getShellCommand(string $file) : string
    {
        return 'clang-format -style=file -i ' . $file;
    }

}
