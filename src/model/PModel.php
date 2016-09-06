<?php

/**
 * The Orm Model that adds power to the CI Model
 */

/**
 *
 */
defined('BASEPATH') or exit('No direct script access allowed');

use eddmash\powerorm\model\BaseModel;
use eddmash\powerorm\model\field\AutoField;
use eddmash\powerorm\model\field\BooleanField;
use eddmash\powerorm\model\field\CharField;
use eddmash\powerorm\model\field\DateField;
use eddmash\powerorm\model\field\DateTimeField;
use eddmash\powerorm\model\field\DecimalField;
use eddmash\powerorm\model\field\EmailField;
use eddmash\powerorm\model\field\FileField;
use eddmash\powerorm\model\field\HasManyField;
use eddmash\powerorm\model\field\HasOneField;
use eddmash\powerorm\model\field\ImageField;
use eddmash\powerorm\model\field\IntegerField;
use eddmash\powerorm\model\field\ManyToManyField;
use eddmash\powerorm\model\field\ManyToOneField;
use eddmash\powerorm\model\field\OneToOneField;
use eddmash\powerorm\model\field\TextField;
use eddmash\powerorm\model\field\TimeField;

/**
 * this makes the Orm Model available for use without namespaces
 *
 * @package eddmash\powerorm\model
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class PModel extends BaseModel
{

    // ********************* Model Fields ************************************

    /**
     * @ignore
     * @param array $opts
     * @return AutoField
     */
    public static function AutoField($opts = [])
    {
        return AutoField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return CharField
     */
    public static function CharField($opts = [])
    {
        return CharField::instance($opts);
    }


    /**
     * @ignore
     * @param array $opts
     * @return FileField
     */
    public static function FileField($opts = [])
    {
        return FileField::instance($opts);
    }


    /**
     * @ignore
     * @param array $opts
     * @return ImageField
     */
    public static function ImageField($opts = [])
    {
        return ImageField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return BooleanField
     */
    public static function BooleanField($opts = [])
    {
        return BooleanField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return EmailField
     */
    public static function EmailField($opts = [])
    {
        return EmailField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return TextField
     */
    public static function TextField($opts = [])
    {
        return TextField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return DecimalField
     */
    public static function DecimalField($opts = [])
    {
        return DecimalField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return IntegerField
     */
    public static function IntegerField($opts = [])
    {
        return IntegerField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return DateTimeField
     */
    public static function DateTimeField($opts = [])
    {
        return DateTimeField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return DateField
     */
    public static function DateField($opts = [])
    {
        return DateField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return TimeField
     */
    public static function TimeField($opts = [])
    {
        return TimeField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return ManyToOneField
     */
    public static function ManyToOneField($opts = [])
    {
        return ManyToOneField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return OneToOneField
     */
    public static function OneToOneField($opts = [])
    {
        return OneToOneField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return ManyToManyField
     */
    public static function ManyToManyField($opts = [])
    {
        return ManyToManyField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return HasManyField
     */
    public static function HasManyField($opts = [])
    {
        return HasManyField::instance($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return HasOneField
     */
    public static function HasOneField($opts = [])
    {
        return HasOneField::instance($opts);
    }
}
