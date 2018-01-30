<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/29/18
 * Time: 10:37 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestApp;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Components\Application;

class Test extends Application
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
