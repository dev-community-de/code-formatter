<?php

/*
 * This file is part of the dev-community code formatter
 * (c) The dev-community.de authors
 */

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class ClangCodeFormatter.
 */
class ClangCodeFormatter extends CodeFormatter
{
    protected const LANGUAGES = [
        'c',
        'cpp',
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
