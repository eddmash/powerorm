<?php

// if we are not in testing environment load the bootstrap,
// other the bootstrap is loaded by phpunit.
if (ENVIRONMENT != 'testing'):
    require_once 'bootstrap.php';
endif;

use Eddmash\PowerOrm\BaseOrm;

/**
 * This class makes the orm available to codeigniter since the orm uses namespaces.
 *
 * <h4>Version 1.0.1 Documentation</h4>
 *
 * visit {@link http://eddmash.github.io/powerorm/docs/v1_0_1}
 *
 * <h4>Version 1.1.0 Documentation</h4>
 *
 * visit {@link http://eddmash.github.io/powerorm/docs/v1_1_0}
 *
 * Class Orm.
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Orm extends BaseOrm
{
}
