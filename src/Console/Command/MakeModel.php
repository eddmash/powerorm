<?php

/**
 * This file is part of the ci306 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\Exception\CommandError;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Migration\FormatFileContent;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModel extends BaseCommand
{
    public $help = 'Generate a model class.';

    protected function configure()
    {
        $this->setName($this->guessCommandName())
            ->setDescription($this->help)
            ->setHelp($this->help)
            ->addArgument(
                'model_name',
                InputArgument::REQUIRED,
                'The name of the model to generate. ' .
                'Use the name "zero" to unapply all migrations.'
            )
            ->addOption(
                'path',
                '-p',
                InputOption::VALUE_OPTIONAL,
                'The location the generated model will be place relative to vendor folder,
                 defaults to same level as vendor folder.',
                null
            )
            ->addOption(
                'force',
                '-f',
                null,
                'force overwrite if model already exists.'
            );
    }

    public function handle(InputInterface $input, OutputInterface $output)
    {
        $vendorHome = dirname(dirname(dirname(POWERORM_HOME)));

        $orginalModelName = $modelName = $input->getArgument('model_name');
        list($namespace, $modelName) = ClassHelper::getNamespaceNamePair($modelName);
        $modelName = ucfirst($modelName);
        $modelPath = $input->getOption('path');

        $force = $input->getOption('force');

        $path = rtrim($vendorHome, '\\') . DIRECTORY_SEPARATOR . ltrim($modelPath, '\\');
        $path = realpath($path);

        if (!file_exists($path)):
            throw new CommandError(sprintf("directory '%s' does not exist", $path));
        endif;

        $modelFile = $this->getModelFile($namespace, $modelName);
        $fileName = rtrim(sprintf('%s.php', $modelName), '\\');

        $filePath = realpath($path . DIRECTORY_SEPARATOR . $fileName);

        if (file_exists($filePath) && !$force):
            throw new CommandError(sprintf("Models '%s' already exists, use -f option to overwrite", $filePath));
        endif;

        $handler = new FileHandler($path, $fileName);

        if ($handler->write($modelFile)) :
            $output->write(sprintf("Models '%s' created at '%s' " . PHP_EOL, $orginalModelName, $path));
        endif;
    }

    private function getModelFile($namespace, $modelName)
    {
        $content = FormatFileContent::createObject();

        $content->addItem('<?php' . PHP_EOL);

        if ($namespace):
            $content->addItem(sprintf('namespace %s;', $namespace) . PHP_EOL);
        endif;

        $content->addItem('use Eddmash\\PowerOrm\\Models\\Models;' . PHP_EOL);

        $content->addItem('/**');
        $content->addItem(sprintf('* Class %s', $modelName));
        $content->addItem('*/');
        $content->addItem(sprintf('class %s extends Models', $modelName));
        $content->addItem('{');

        $content->addIndent();
        $content->addItem('private function unboundFields()');
        $content->addItem('{');

        $content->addIndent();
        $content->addItem('return [];');
        $content->reduceIndent();

        $content->addItem('}' . PHP_EOL);
        $content->reduceIndent();
        $content->addItem('}');

        return $content->toString();
    }
}
