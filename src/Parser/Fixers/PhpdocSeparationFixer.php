<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Eddmash\PowerOrm\Parser\Fixers;

use Eddmash\PowerOrm\Parser\DocBlock\Annotation;
use Eddmash\PowerOrm\Parser\DocBlock\DocBlock;
use Eddmash\PowerOrm\Parser\DocBlock\TagComparator;
use Eddmash\PowerOrm\Parser\Token;
use Eddmash\PowerOrm\Parser\Tokens;
use Eddmash\PowerOrm\Model\Model;

/**
 * @author Graham Campbell <graham@alt-three.com>
 */
class PhpdocSeparationFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, Tokens $tokens, Model $model = null)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $doc = new DocBlock($token->getContent());
            $this->fixDescription($doc);
            $this->fixAnnotations($doc);

            $tokens[$index] = new Token([T_DOC_COMMENT, $doc->getContent()]);
        }
    }

    /**
     * Make sure the description is separated from the annotations.
     *
     * @param DocBlock $doc
     */
    private function fixDescription(DocBlock $doc)
    {
        foreach ($doc->getLines() as $index => $line) {
            if ($line->containsATag()) {
                break;
            }

            if ($line->containsUsefulContent()) {
                $next = $doc->getLine($index + 1);

                if ($next->containsATag()) {
                    $line->addBlank();

                    break;
                }
            }
        }
    }

    /**
     * Make sure the annotations are correctly separated.
     *
     * @param DocBlock $doc
     *
     * @return string
     */
    private function fixAnnotations(DocBlock $doc)
    {
        foreach ($doc->getAnnotations() as $index => $annotation) {
            $next = $doc->getAnnotation($index + 1);

            if (null === $next) {
                break;
            }

            if (true === $next->getTag()->valid()) {
                if (TagComparator::shouldBeTogether($annotation->getTag(), $next->getTag())) {
                    $this->ensureAreTogether($doc, $annotation, $next);
                } else {
                    $this->ensureAreSeparate($doc, $annotation, $next);
                }
            }
        }

        return $doc->getContent();
    }

    /**
     * Force the given annotations to immediately follow each other.
     *
     * @param DocBlock   $doc
     * @param Annotation $first
     * @param Annotation $second
     */
    private function ensureAreTogether(DocBlock $doc, Annotation $first, Annotation $second)
    {
        $pos = $first->getEnd();
        $final = $second->getStart();

        for ($pos = $pos + 1; $pos < $final; ++$pos) {
            $doc->getLine($pos)->remove();
        }
    }

    /**
     * Force the given annotations to have one empty line between each other.
     *
     * @param DocBlock   $doc
     * @param Annotation $first
     * @param Annotation $second
     */
    private function ensureAreSeparate(DocBlock $doc, Annotation $first, Annotation $second)
    {
        $pos = $first->getEnd();
        $final = $second->getStart() - 1;

        // check if we need to add a line, or need to remove one or more lines
        if ($pos === $final) {
            $doc->getLine($pos)->addBlank();

            return;
        }

        for ($pos = $pos + 1; $pos < $final; ++$pos) {
            $doc->getLine($pos)->remove();
        }
    }

    public function getName()
    {
        return static::class;
    }

    public function getPriority()
    {
        return 1;
    }
}
