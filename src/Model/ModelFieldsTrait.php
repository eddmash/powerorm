<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model;

use Eddmash\PowerOrm\Model\Field\AutoField;
use Eddmash\PowerOrm\Model\Field\BooleanField;
use Eddmash\PowerOrm\Model\Field\CharField;
use Eddmash\PowerOrm\Model\Field\DateField;
use Eddmash\PowerOrm\Model\Field\DateTimeField;
use Eddmash\PowerOrm\Model\Field\DecimalField;
use Eddmash\PowerOrm\Model\Field\EmailField;
use Eddmash\PowerOrm\Model\Field\ForeignKey;
use Eddmash\PowerOrm\Model\Field\IntegerField;
use Eddmash\PowerOrm\Model\Field\Inverse\HasManyField;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Field\OneToOneField;
use Eddmash\PowerOrm\Model\Field\TextField;
use Eddmash\PowerOrm\Model\Field\TimeField;

trait ModelFieldsTrait
{
// ********************* Model Fields ************************************

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return AutoField
     */
    public static function AutoField($opts = [])
    {
        return AutoField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return CharField
     */
    public static function CharField($opts = [])
    {
        return CharField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return FileField
     */
    public static function FileField($opts = [])
    {
        return FileField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return ImageField
     */
    public static function ImageField($opts = [])
    {
        return ImageField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return BooleanField
     */
    public static function BooleanField($opts = [])
    {
        return BooleanField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return EmailField
     */
    public static function EmailField($opts = [])
    {
        return EmailField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return TextField
     */
    public static function TextField($opts = [])
    {
        return TextField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return DecimalField
     */
    public static function DecimalField($opts = [])
    {
        return DecimalField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return IntegerField
     */
    public static function IntegerField($opts = [])
    {
        return IntegerField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return DateTimeField
     */
    public static function DateTimeField($opts = [])
    {
        return DateTimeField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return DateField
     */
    public static function DateField($opts = [])
    {
        return DateField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return TimeField
     */
    public static function TimeField($opts = [])
    {
        return TimeField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return ForeignKey
     */
    public static function ForeignKey($opts = [])
    {
        return ForeignKey::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return OneToOneField
     */
    public static function OneToOneField($opts = [])
    {
        return OneToOneField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return \Eddmash\PowerOrm\Model\Field\Field
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public static function ManyToManyField($opts = [])
    {
        return ManyToManyField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return HasManyField
     */
    public static function HasManyField($opts = [])
    {
        return HasManyField::createObject($opts);
    }

    /**
     * @ignore
     *
     * @param array $opts
     *
     * @return HasOneField
     */
    public static function HasOneField($opts = [])
    {
        return HasOneField::createObject($opts);
    }
}
