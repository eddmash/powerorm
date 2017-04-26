<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field;

use Eddmash\PowerOrm\Helpers\ArrayHelper;

/**
 * A CharField that checks that the value is a valid email address.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class EmailField extends CharField
{
    public function __construct($config = [])
    {
        // max_length=254 to be compliant with RFCs 3696 and 5321
        $config['maxLength'] = ArrayHelper::getValue($config, 'maxLength', 254);
        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function formField($kwargs = [])
    {
        $kwargs['fieldClass'] = \Eddmash\PowerOrm\Form\Fields\EmailField::class;
        return parent::formField($kwargs);
    }


}
