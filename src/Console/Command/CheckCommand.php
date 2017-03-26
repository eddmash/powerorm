<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/2/16.
 */

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\CommandError;
use Eddmash\PowerOrm\Exception\SystemCheckError;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class CheckCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public $systemCheck = false;

    public $help = 'Runs systems check for potential problems';

    public function handle(InputInterface $input, OutputInterface $output)
    {
        $availableTags = BaseOrm::getCheckRegistry()->tagsAvailable();

        if ($input->getOption('list-tags')):
            $output->writeln(implode(PHP_EOL, $availableTags));

            return;
        endif;

        $tags = $input->getOption('tag');
        if ($tags):
            $invalidTags = [];
            foreach ($tags as $tag) :
                if (!BaseOrm::getCheckRegistry()->tagExists($tag)):
                    $invalidTags[] = $tag;
                endif;
            endforeach;

            if ($invalidTags):
                throw new CommandError(
                    sprintf(
                        'There is no system check with the "%s" tag(s).',
                        implode(', ', $invalidTags)
                    )
                );
            endif;
        endif;

        $failLevel = $input->getOption('fail-level');
        if ($failLevel):
            if (!in_array(strtoupper($failLevel), ['ERROR', 'WARNING', 'INFO', 'DEBUG', 'CRITICAL'])):
                throw new CommandError(
                    sprintf(
                        "--fail-level: invalid choice: '%s' ".
                        "(choices are 'CRITICAL', 'ERROR', 'WARNING', 'INFO', 'DEBUG')",
                        $failLevel
                    )
                );
            endif;

        endif;

        try {
            $this->check($input, $output, $tags, true, $failLevel);
        } catch (SystemCheckError $e) {
            // we get a system check error, stop further processing
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName($this->guessCommandName())
            ->setDescription($this->help)
            ->setHelp($this->help)
            ->addOption('list-tags', null, InputOption::VALUE_NONE, 'List available tags.')
            ->addOption(
                'fail-level',
                null,
                InputOption::VALUE_OPTIONAL,
                'Message level that will cause the command to exit with a non-zero status.'.
                '{CRITICAL, ERROR, WARNING, INFO, DEBUG}.',
                'ERROR'
            )
            ->addOption(
                'tag',
                '-t',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Run only checks labeled with given tag.'
            );
    }
}
