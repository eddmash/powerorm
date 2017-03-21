<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Eddmash\PowerOrm\Model\Field\Inverse;

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\OneToManyRel;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\ManyReverseQueryset;

class HasManyField extends InverseField
{
    public function __construct(array $kwargs)
    {
        $kwargs['rel']= OneToManyRel::createObject([
            'fromField' => $this,
            'to' => ArrayHelper::getValue($kwargs, 'to'),
        ]);
        parent::__construct($kwargs);
        $this->toField = ArrayHelper::getValue($kwargs, 'toField');
        $this->fromField = 'this';
    }

    /**
     * {@inheritdoc}
     */
    public function createManyQueryset(ForeignObjectRel $rel, $modelClass, $reverse = false)
    {
        $querysetClass = $modelClass::getQuerysetClass();
        if (!class_exists('Eddmash\PowerOrm\Model\Query\BaseManyReverseQueryset')) :
            eval(sprintf('namespace Eddmash\PowerOrm\Model\Query;class BaseManyReverseQueryset extends \%s{}', $querysetClass));
        endif;

        return function (Model $instance) use ($rel, $reverse) {

            $queryset = ManyReverseQueryset::createObject(null, null, null,
                [
                    'rel' => $rel,
                    'instance' => $instance,
                ]
            );
//            $cond = $queryset->filters;
//
//            $queryset = $queryset->filter($cond);

            return $queryset;
        };
    }

}