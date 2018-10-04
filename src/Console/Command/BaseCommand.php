<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Checks\CheckMessage;
use Eddmash\PowerOrm\Exception\CommandError;
use Eddmash\PowerOrm\Exception\SystemCheckError;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class BaseCommand extends Command
{
    /**
     * If true the command will perfom check before it runs.
     *
     * @var bool
     */
    public $systemCheck = true;

    public $headerMessage = '
    **********************************************************%4$s****
        *    ___   ___           ___  ___  ___  ___           %3$s*
        *   /___/ /  / \  /\  / /__  /__/ /  / /__/ /\  /\    %3$s*
        *  /     /__/   \/  \/ /__  /  \ /__/ /  \ /  \/  \(%1$s) %2$s*
        * / by Eddilbert Macharia (http://www.eddmash.com) \  %3$s*
        *                                                     %3$s*
    **********************************************************%4$s****

    ';

    protected function configure()
    {
        $this->setName($this->guessCommandName());
    }

    /**
     * Place all you command logic here.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return CommandError
     */
    public function handle(InputInterface $input, OutputInterface $output)
    {
        return new CommandError(
            'Subclasses of the class '.
            'Command must implement the handle()'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = sprintf('<info>%s</info>', $this->headerMessage);

        $pad = 2;
        $maxLength = strlen(POWERORM_VERSION) + $pad;
        $padLength = $maxLength - strlen(POWERORM_VERSION);
        $versionPad = str_pad('', $padLength, ' ');
        $inLinePad = str_pad('', $maxLength, ' ');
        $outLinePad = str_pad('', $maxLength, '*');

        $output->writeln(
            sprintf(
                $message,
                POWERORM_VERSION,
                $versionPad,
                $inLinePad,
                $outLinePad
            )
        );

        if ($this->systemCheck) {
            try {
                $this->check($input, $output);
            } catch (SystemCheckError $e) {
                // we get a system check error, stop further processing
                return;
            }
        }

        $this->handle($input, $output);
    }

    public function check(
        InputInterface $input,
        OutputInterface $output,
        $tags = null,
        $showErrorCount = null,
        $failLevel = null
    ) {
        $checks = BaseOrm::getCheckRegistry()->runChecks($tags);

        $debugs = [];
        $info = [];
        $warning = [];
        $errors = [];
        $critical = [];
        $serious = [];

        $header = $body = $footer = '';

        /** @var $check CheckMessage */
        foreach ($checks as $check) {
            if ($check->isSerious($failLevel) && !$check->isSilenced()) {
                $serious[] = $check;
            }

            if ($check->level < CheckMessage::INFO && !$check->isSilenced()) {
                $debugs[] = $check;
            }

            // info
            if ($check->level >= CheckMessage::INFO &&
                $check->level < CheckMessage::WARNING && !$check->isSilenced()) {
                $info[] = $check;
            }

            // warning
            if ($check->level >= CheckMessage::WARNING &&
                $check->level < CheckMessage::ERROR && !$check->isSilenced()) {
                $warning[] = $check;
            }

            //error
            if ($check->level >= CheckMessage::ERROR &&
                $check->level < CheckMessage::CRITICAL && !$check->isSilenced()
            ) {
                $errors[] = $check;
            }

            //critical
            if ($check->level >= CheckMessage::CRITICAL && !$check->isSilenced()) {
                $critical[] = $check;
            }
        }

        // get the count of visible issues only, hide the silenced ones

        $visibleIssues = count($errors) + count($warning) + count($info)
            + count($debugs) + count($critical);

        if ($visibleIssues) {
            $header = 'System check identified issues: '.PHP_EOL;
        }

        $errors = array_merge($critical, $errors);

        $categorisedIssues = [
            'critical' => $critical,
            'errors' => $errors,
            'warning' => $warning,
            'info' => $info,
            'debug' => $debugs,
        ];

        /* @var $catIssue CheckMessage */
        foreach ($categorisedIssues as $category => $categoryIssues) {
            if (empty($categoryIssues)) {
                continue;
            }
            $body .= sprintf(PHP_EOL.' %s'.PHP_EOL, strtoupper($category));

            foreach ($categoryIssues as $catIssue) {
                if ($catIssue->isSerious()) {
                    $msg = ' <fg=red>%s</>'.PHP_EOL;
                } else {
                    $msg = ' <warning>%s</warning>'.PHP_EOL;
                }
                $body .= sprintf($msg, $catIssue);
            }
        }

        if ($showErrorCount) {
            $issueText = (1 === $visibleIssues) ? 'issue' : 'issues';
            $silenced = count($checks) - $visibleIssues;
            if ($visibleIssues) {
                $footer .= PHP_EOL;
            }
            $footer .= sprintf(
                ' System check identified %s %s (%s silenced) ',
                $visibleIssues,
                $issueText,
                $silenced
            );
        }

        if (!empty($serious)) {
            $header = sprintf('<fg=red;options=bold> SystemCheckError: %s</>', $header);
            $message = $header.$body.$footer;
            $output->writeln($message);

            throw new SystemCheckError();
        }

        $message = $header.$body.$footer;
        $output->writeln($message);
    }

    /**
     * Returns the name of the current class in lower case and strips off the "Command".
     *
     * @return string
     */
    public function guessCommandName()
    {
        $name = get_class($this);
        $name = substr($name, strripos($name, '\\') + 1);
        $name = (false === strripos($name, 'Command')) ? $name : substr($name, 0, strripos($name, 'Command'));

        return strtolower($name);
    }
}
