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
        if (!ArrayHelper::hasKey($config, 'dbIndex')) {
            $config['dbIndex'] = true;
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValidators()
    {
        $validators = parent::getDefaultValidators();
        $validators[] = SlugValidator::instance();

        return $validators;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructorArgs()
    {
        $kwargs = parent::getConstructorArgs();
        $maxLength = ArrayHelper::getValue($kwargs, 'maxLength');
        if (50 === $maxLength) {
            unset($kwargs['maxLength']);
        }
        if (!ArrayHelper::hasKey($kwargs, 'dbIndex')) {
            $config['dbIndex'] = true;
        }

        return $kwargs;
    }

    /**
     * {@inheritdoc}
     */
    public function formField($kwargs = [])
    {
        $kwargs['fieldClass'] = \Eddmash\PowerOrm\Form\Fields\SlugField::class;

        return parent::formField($kwargs);
    }
}
