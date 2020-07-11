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
    protected const LANGUAGES = [
        'c',
        'cpp',
        'csharp',
        'java',
        'objectivec',
    ];

    /**
     * @return string
     */
    protected function getShellCommand(): string
    {
        $filename = 'format.';

        switch ($this->language) {
            case 'csharp':
                $filename .= 'cs';
                break;
            case 'objectivec':
                $filename .= 'm';
                break;
            default:
                $filename .= $this->language;
        }

        return 'clang-format --style=file --assume-filename=' . $filename;
    }
}
