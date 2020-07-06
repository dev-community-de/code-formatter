<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class ClangCodeFormatter.
 */
class ClangCodeFormatter extends CodeFormatter
{
    protected const LANGUAGES = [
        'c',
        'cpp',
        'cs',
        'java',
        'm',
    ];

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getShellCommand(string $file): string
    {
        $fileArg = escapeshellarg($file);
        return 'clang-format -style=file -i ' . $fileArg;
    }
}
