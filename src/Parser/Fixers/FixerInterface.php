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

use Eddmash\PowerOrm\Parser\Tokens;
use Eddmash\PowerOrm\Model\Model;

interface FixerInterface
{
    /**
     * Returns the priority of the fixer.
     *
     * The default priority is 0 and higher priorities are executed first.
     *
     * @return int
     */
    public function getPriority();

    /**
     * Fixes a file.
     *
     * @param \SplFileInfo $file   A \SplFileInfo instance
     * @param Tokens       $tokens Tokens collection
     * @param Model        $model
     *
     * @return
     */
    public function fix(\SplFileInfo $file, Tokens $tokens, Model $model);

    public function getName();
}
