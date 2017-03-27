<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Console\Command;

use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     *
     * @var bool
     */
    public $systemCheck = false;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('list')
            ->setDefinition($this->createDefinition())
            ->setDescription('Lists commands')
            ->setHelp(
                <<<'EOF'
                The <info>%command.name%</info> command lists all commands:

  <info>php %command.full_name%</info>

You can also display the commands for a specific namespace:

  <info>php %command.full_name% test</info>

You can also output the information in other formats by using the <comment>--format</comment> option:

  <info>php %command.full_name% --format=xml</info>

It's also possible to get raw list of commands (useful for embedding command runner):

  <info>php %command.full_name% --raw</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getNativeDefinition()
    {
        return $this->createDefinition();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(InputInterface $input, OutputInterface $output)
    {
        $helper = new DescriptorHelper();
        $helper->describe(
            $output,
            $this->getApplication(),
            array(
                'format' => $input->getOption('format'),
                'raw_text' => $input->getOption('raw'),
                'namespace' => $input->getArgument('namespace'),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    private function createDefinition()
    {
        return new InputDefinition(
            array(
                new InputArgument('namespace', InputArgument::OPTIONAL, 'The namespace name'),
                new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw command list'),
                new InputOption(
                    'format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)',
                    'txt'
                ),
            )
        );
    }
}
