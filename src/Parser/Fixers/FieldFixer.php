<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Parser\Fixers;

use Eddmash\PowerOrm\Model\Field\AutoField;
use Eddmash\PowerOrm\Model\Field\CharField;
use Eddmash\PowerOrm\Model\Field\DateField;
use Eddmash\PowerOrm\Model\Field\DecimalField;
use Eddmash\PowerOrm\Model\Field\Descriptors\RelationDescriptor;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\FloatField;
use Eddmash\PowerOrm\Model\Field\IntegerField;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Parser\DocBlock\DocBlock;
use Eddmash\PowerOrm\Parser\Token;
use Eddmash\PowerOrm\Parser\Tokens;

class FieldFixer extends AbstractFixer
{
    /**
     * @var Model
     *
     * @author Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
     */
    private $model;

    public function fix(\SplFileInfo $file, Tokens $tokens, Model $model = null)
    {
        $this->model = $model;

        foreach ($tokens as $index => $token) {
            if (T_OPEN_TAG === $token->getId()) {
                $classIndex = $tokens->getNextTokenOfKind($index, [new Token([T_CLASS, 'class'])]);

                $prevTokenID = $tokens->getPrevNonWhitespace($classIndex);

                $prevToken = $tokens[$prevTokenID];
                if ($prevToken->isGivenKind(T_DOC_COMMENT)) {
                    $docToken = $prevToken;
                } else {
                    // no doc available
                    $content = "\n/**\n*/\n";
                    $docToken = new Token([T_DOC_COMMENT, $content]);
                    $tokens->insertAt($classIndex, $docToken);
                }

                $doc = new DocBlock($docToken->getContent());

                $nn = $doc->getAnnotationsOfType('property');

                $start = 0;
                foreach ($nn as $i => $item) {
                    if (0 === $i) {
                        $start = $item->getStart();
                    }
                    $item->remove();
                }

                if (!$start) {
                    foreach ($doc->getLines() as $l => $line) {
                        if ($line->containsATag()) {
                            $start = $l;
                            break;
                        }

                        if (!$start && $line->isTheEnd()) {
                            $start = $l;
                        }
                    }
                }

                $fields = $this->model->getMeta()->getFields(true, true);

                // reverse so the related fields are processed last.
                foreach ($fields as $field) {
                    list($type, $name) = $this->getFieldInfo($field);
                    $doc->addLine($start, sprintf(" * @property %s $%s\n", $type, $name));
                    ++$start;
                }

                $docToken->setContent($doc->getContent());
            }
        }
    }

    /**
     * @param Field|ForeignObjectRel $field
     *
     * @return string[]
     *
     * @author Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
     */
    public function getFieldInfo($field)
    {
        $name = $field->getName();
        $type = '';
        if ($field instanceof IntegerField || $field instanceof AutoField) {
            $type = 'int';
        }
        if ($field instanceof CharField) {
            $type = 'string';
        }

        if ($field instanceof FloatField || $field instanceof DecimalField) {
            $type = 'float';
        }

        if ($field instanceof DateField) {
            $type = '\\'.\DateTime::class;
        }

        if ($field instanceof RelatedField) {
            $field->setNull(true);
            /** @var $desc RelationDescriptor* */
            $desc = $field->getDescriptor();
            $manger = $desc->getManagerClass();
            $type = '\\'.$manger;
        }
        if ($field instanceof ForeignObjectRel) {
            $name = $field->getAccessorName();

            /** @var $desc RelationDescriptor* */
            $desc = $this->model->{$name};
            $manger = $desc->getManagerClass();
            $type = '\\'.$manger;
        }

        return [$type, $name];
    }

    public function getName()
    {
        return static::class;
    }
}
