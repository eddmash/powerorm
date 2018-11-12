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

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Components\AppInterface;
use Eddmash\PowerOrm\Exception\CommandError;
use Eddmash\PowerOrm\Exception\ComponentException;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Migration\FormatFileContent;
use Eddmash\PowerOrm\Model\Model;
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
                'app_name',
                InputArgument::REQUIRED,
                'The app to which this model will belong.',
                null
            )
            ->addArgument(
                'model_name',
                InputArgument::REQUIRED,
                'The name of the model to generate. ' .
                'Use the name "zero" to unapply all migrations.'
            )
            ->addOption(
                'force',
                '-f',
                null,
                'force overwrite if model already exists.'
            );
    }

    /**@inheritdoc */
    public function handle(InputInterface $input, OutputInterface $output)
    {
//        $vendorHome = dirname(dirname(dirname(POWERORM_HOME)));

        $orginalModelName = $modelName = $input->getArgument('model_name');

        $modelName = ucfirst($modelName);
        $app_name = $input->getArgument('app_name');
        $force = $input->getOption('force');

        try {
            $component = BaseOrm::getInstance()->getComponent($app_name);
        } catch (ComponentException $e) {
            throw new CommandError(sprintf("Could not find an application with the name %s", $app_name));
        }
        if (!$component instanceof AppInterface) {
            throw new CommandError(sprintf("is not an application %s, it does not implement %s", $app_name,
                AppInterface::class));
        }

        $path = $component->getModelsPath();

        if (!file_exists($path)) {
            throw new CommandError(sprintf("directory '%s' does not exist", $path));
        }

        // assumes the app is using composer to autload using psr-4
        $namespace = sprintf("%s\%s", $component->getNamespace(), substr($path,
            strrpos($path, DIRECTORY_SEPARATOR) + 1));
        $namespace = ltrim($namespace, "\\");

        $modelFile = $this->getModelFile($namespace, $modelName);
        $fileName = rtrim(sprintf('%s.php', $modelName), '\\');

        $filePath = realpath($path . DIRECTORY_SEPARATOR . $fileName);

        if (file_exists($filePath) && !$force) {
            throw new CommandError(sprintf("Models '%s' already exists, use -f option to overwrite", $filePath));
        }

        $handler = new FileHandler($path, $fileName);

        if ($handler->write($modelFile)) {
            $output->write(sprintf("Models '%s' created at '%s' " . PHP_EOL, $orginalModelName, $path));
        }
    }

    private function getModelFile($namespace, $modelName)
    {
        $content = FormatFileContent::createObject();

        $content->addItem('<?php' . PHP_EOL);

        if ($namespace) {
            $content->addItem(sprintf('namespace %s;', $namespace) . PHP_EOL);
        }

//        $content->addItem(sprintf('use %s;', Model::class) . PHP_EOL);

        $content->addItem('/**');
        $content->addItem(sprintf('* Class %s', $modelName));
        $content->addItem('*/');
        $content->addItem(sprintf('class %s extends \%s', $modelName, ltrim(Model::class, "\\")));
        $content->addItem('{');
        $content->addIndent();
        $content->addItem('public function unboundFields()');
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
