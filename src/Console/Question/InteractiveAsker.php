<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Console\Question;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

class InteractiveAsker extends Asker
{
    public function ask(Question $question)
    {
        $questionHelper = new QuestionHelper();

        return $questionHelper->ask($this->input, $this->output, $question);
    }
}
