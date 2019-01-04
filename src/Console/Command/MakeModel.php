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
use Eddmash\PowerOrm\Components\AppComponent;
use Eddmash\PowerOrm\Components\AppInterface;
use Eddmash\PowerOrm\Exception\CommandError;
use Eddmash\PowerOrm\Exception\ComponentException;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Migration\FormatFileContent;
use Eddmash\PowerOrm\Model\Model;
use http\Exception\UnexpectedValueException;
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
                'appname',
                InputArgument::REQUIRED,
                'The name application to which generated model will belong.'
            )
            ->addArgument(
                'modelname',
                InputArgument::REQUIRED,
                'The name of the model to generate.'
            )
            ->addOption(
                'extends',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Model generated model will extend.in the form `appname:modelname` ' .
                'e.g. app:user or school:teacher'
            )
            ->addOption(
                'force',
                'f',
                null,
                'force overwrite if model already exists.'
            );
    }

    public function handle(InputInterface $input, OutputInterface $output)
    {
        $appName = $input->getArgument('appname');
        $orginalModelName = $input->getArgument('modelname');
        $orginalModelName = $modelName = ucfirst($orginalModelName);
        $extends = $input->getOption('extends');
        $force = $input->getOption('force');

        $extendComponent = null;

        if (!$extends) {
            $extendNamespace = Model::class;
            $extendModelName = basename(str_replace("\\", "/", Model::class));
        } else {
            $extends = explode(":", $extends);
            if (count($extends) == 1) {
                throw new CommandError("extends should be in the form of `appname:modelname`" .
                    " e.g. app:user or school:teacher");
            }
            /**@var $extendComponent AppComponent */
            list($extendAppName, $extendModelName) = $extends;
            try {
                $extendComponent = BaseOrm::getInstance()->getComponent($extendAppName);
                if (!$extendComponent instanceof AppInterface) {
                    throw new CommandError(
                        sprintf("%s does not implement AppInterface, it needs to be an AppComponent",
                            $extendAppName));
                }
            } catch (ComponentException $e) {
                throw new CommandError($e->getMessage());
            }
            $extendModelFolder = basename($extendComponent->getModelsPath());
            $extendNamespace = ltrim(sprintf("%s\%s", $extendComponent->getNamespace(), $extendModelFolder), "\\");


        }

        /**@var $component AppComponent */
        try {
            $component = BaseOrm::getInstance()->getComponent($appName);
            if (!$component instanceof AppInterface) {
                throw new CommandError(
                    sprintf("%s does not implement AppInterface, it needs to be an AppComponent",
                        $appName));
            }
        } catch (ComponentException $e) {
            throw new CommandError($e->getMessage());
        }


        $path = $component->getModelsPath();

        if (!file_exists($path)) {
            throw new CommandError(sprintf("directory '%s' does not exist", $path));
        }

        $modelFolder = basename($path);
        $namespace = ltrim(sprintf("%s\%s", $component->getNamespace(), $modelFolder), "\\");

        $modelFile = $this->getModelFile($namespace, $modelName, $extendNamespace, $extendModelName);
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

    private function getModelFile($namespace, $modelName, $extendNamespace, $extendModel)
    {
        $extendNamespace = $extendNamespace === $namespace ? '' : $extendNamespace;
        $content = FormatFileContent::createObject();

        $content->addItem('<?php' . PHP_EOL);
        $content->addItem(PHP_EOL . sprintf(
                '/** Model file generated at %s on %s by PowerOrm(%s)*/',
                date('h:m:i'),
                date('D, jS F Y'),
                POWERORM_VERSION
            ) . PHP_EOL);

        if ($namespace) {
            $content->addItem(sprintf('namespace %s;', $namespace) . PHP_EOL);
        }

        if ($extendNamespace) {
            $content->addItem(sprintf('use %s;', $extendNamespace) . PHP_EOL);
        }

        $content->addItem('/**');
        $content->addItem(sprintf('* Class %s', $modelName));
        $content->addItem('*/');
        $content->addItem(sprintf('class %s extends %s', ucfirst($modelName), ucfirst($extendModel)));
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
