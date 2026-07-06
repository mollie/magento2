<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Sniffs\Legal;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class CopyrightNoticeSniff implements Sniff
{
    private const ERROR_MESSAGE = 'Missing or invalid Magmodules copyright notice at the top of the file';

    private const REQUIRED_LINES = [
        'Copyright Magmodules.eu. All rights reserved.',
        'See COPYING.txt for license details.',
    ];

    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        if ($phpcsFile->findPrevious(T_OPEN_TAG, $stackPtr - 1) !== false) {
            return;
        }

        $commentPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);

        if ($this->isComment($phpcsFile, $commentPtr) === false) {
            $phpcsFile->addError(self::ERROR_MESSAGE, $stackPtr, 'Missing');

            return;
        }

        $comment = $this->getCommentText($phpcsFile, $commentPtr);

        foreach (self::REQUIRED_LINES as $requiredLine) {
            if (strpos($comment, $requiredLine) === false) {
                $phpcsFile->addError(self::ERROR_MESSAGE, $commentPtr, 'Invalid');

                return;
            }
        }
    }

    private function isComment(File $phpcsFile, $commentPtr): bool
    {
        if ($commentPtr === false) {
            return false;
        }

        $tokens = $phpcsFile->getTokens();

        return in_array($tokens[$commentPtr]['code'], [T_COMMENT, T_DOC_COMMENT_OPEN_TAG], true);
    }

    private function getCommentText(File $phpcsFile, int $commentPtr): string
    {
        $tokens = $phpcsFile->getTokens();
        $text = '';

        if ($tokens[$commentPtr]['code'] === T_COMMENT) {
            for ($i = $commentPtr; isset($tokens[$i]) && $tokens[$i]['code'] === T_COMMENT; $i++) {
                $text .= $tokens[$i]['content'];
            }

            return $text;
        }

        $closePtr = $phpcsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $commentPtr);

        for ($i = $commentPtr; $i <= $closePtr; $i++) {
            $text .= $tokens[$i]['content'];
        }

        return $text;
    }
}
