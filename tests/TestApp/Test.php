<?php
/**
 *
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\TestApp;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Components\AppComponent;

class Test extends AppComponent
{
    public function getName()
    {
        return 'testapp';
    }

    /**
     * This method is invoked after the orm registry is ready .
     *
     * This means the models can be accessed within this model without any
     * issues.
     *
     * @param \Eddmash\PowerOrm\BaseOrm $baseOrm
     *
     * @return mixed
     */
    public function ready(BaseOrm $baseOrm)
    {
        // TODO: Implement ready() method.
    }
}
