<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Parser;

use Eddmash\PowerOrm\Parser\Fixers\FixerInterface;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Model;

class Fixer
{
    private static $fixerOrder;

    /**
     * @param Model[] $models
     * @param array   $stats
     *
     * @author Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
     */
    public static function run($models, &$stats = [])
    {
        static::loadFixers();

        foreach ($models as $path => $model) {
            $file = new \SplFileInfo($path);
            list($content, $changed) = static::doRun($file, $model);
            if ($changed) {
                static::updateFile($file, $content);
                $stats[] = sprintf('%s:%s', $model->getMeta()->getAppName(), $model->getMeta()->getModelName());
            }
        }
    }

    private static function updateFile(\SplFileInfo $file, $new)
    {
        if (false === @file_put_contents($file->getRealPath(), $new)) {
            $error = error_get_last();

            throw new IOException(
                sprintf('Failed to write file "%s", "%s".', $file->getPathname(), $error ? $error['message'] : 'no reason available'),
                0,
                null,
                $file->getRealPath()
            );
        }
    }

    private static function doRun(\SplFileInfo $file, Model $model)
    {
        $tokens = Tokens::fromCode(static::readRaw($file->getRealPath()));

        $oldHash = $tokens->getCodeHash();

        /** @var $fixer FixerInterface* */
        foreach (static::$fixerOrder as $fixers) {
            foreach ($fixers as $fixer) {
                $fixer->fix($file, $tokens, $model);
            }
        }
        $newHash = $tokens->getCodeHash();

        return [$tokens->generateCode(), $newHash === $oldHash];
    }

    /**
     * @param string $realPath
     *
     * @return string
     */
    private static function readRaw($realPath)
    {
        $content = @file_get_contents($realPath);

        if (false === $content) {
            $error = error_get_last();

            throw new \RuntimeException(sprintf(
                'Failed to read content from "%s".%s',
                $realPath,
                $error ? ' '.$error['message'] : ''
            ));
        }

        return $content;
    }

    private static function loadFixers()
    {
        $fixers = BaseOrm::getInstance()->getSettings()->getFixers();

        /** @var $fixer FixerInterface* */
        foreach ($fixers as $fixerClass) {
            $fixer = new $fixerClass();

            $prio = $fixer->getPriority();
            static::$fixerOrder[$prio] = ArrayHelper::getValue(static::$fixerOrder, $prio, []);

            static::$fixerOrder[$prio][] = $fixer;
        }
    }
}
