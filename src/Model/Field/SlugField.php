<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Eddmash\PowerOrm\Model\Field;


use Eddmash\PowerOrm\Form\Validations\SlugValidator;
use Eddmash\PowerOrm\Helpers\ArrayHelper;

class SlugField extends CharField
{
    public function __construct(array $config = [])
    {

        $config['maxLength'] = ArrayHelper::getValue($config, 'maxLength', 50);
        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultValidators()
    {
        $validators = parent::getDefaultValidators();
        $validators[] = SlugValidator::instance();
        return $validators;
    }

    /**
     * @inheritDoc
     */
    public function getConstructorArgs()
    {
        $kwargs = parent::getConstructorArgs();
        $maxLength = ArrayHelper::getValue($kwargs, 'maxLength');
        if ($maxLength===50) :
            unset($kwargs['maxLength']);
        endif;
        return $kwargs;
    }


    /**
     * @inheritDoc
     */
    public function formField($kwargs = [])
    {
        $kwargs['fieldClass'] = \Eddmash\PowerOrm\Form\Fields\SlugField::class;
        return parent::formField($kwargs);
    }


}