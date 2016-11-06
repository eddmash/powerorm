<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field;

use Eddmash\PowerOrm\Helpers\ArrayHelper;

class URLField extends CharField
{
    public function __construct($config = [])
    {
        $config['maxLength'] = ArrayHelper::getValue($config, 'maxLength', 200);
        parent::__construct($config);
    }
}
