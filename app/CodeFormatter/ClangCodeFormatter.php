<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class ClangCodeFormatter.
 */
class ClangCodeFormatter extends CodeFormatter
{
    /**
     * @var array
     */
    protected static $supported_languages = [
        'c',
        'cpp',
        'csharp',
        'java',
        'objectivec',
    ];

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getShellCommand(string $file): string
    {
        return 'clang-format -style=file -i ' . $file;
    }
}
