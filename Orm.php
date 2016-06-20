<?php
require_once("BaseOrm.php");

use powerorm\BaseOrm;
use powerorm\model\field\AutoField;
use powerorm\model\field\BooleanField;
use powerorm\model\field\CharField;
use powerorm\model\field\DateField;
use powerorm\model\field\DateTimeField;
use powerorm\model\field\DecimalField;
use powerorm\model\field\EmailField;
use powerorm\model\field\FileField;
use powerorm\model\field\ForeignKey;
use powerorm\model\field\HasMany;
use powerorm\model\field\HasOne;
use powerorm\model\field\ImageField;
use powerorm\model\field\IntegerField;
use powerorm\model\field\ManyToMany;
use powerorm\model\field\OneToOne;
use powerorm\model\field\TextField;
use powerorm\model\field\TimeField;


/**
 * This class makes the orm available to codeigniter since the orm uses namespaces.
 * Class Orm
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Orm extends BaseOrm{
    // ********************* ORM Fields ************************************

    /**
     * @ignore
     * @param array $opts
     * @return AutoField
     */
    public static function AutoField($opts=[]){
        return new AutoField($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return CharField
     */
    public static function CharField($opts=[]){
        return new CharField($opts);
    }


    /**
     * @ignore
     * @param array $opts
     * @return FileField
     */
    public static function FileField($opts=[]){
        return new FileField($opts);
    }


    /**
     * @ignore
     * @param array $opts
     * @return ImageField
     */
    public static function ImageField($opts=[]){
        return new ImageField($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return BooleanField
     */
    public static function BooleanField($opts=[]){
        return new BooleanField($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return EmailField
     */
    public static function EmailField($opts=[]){
        return new EmailField($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return TextField
     */
    public static function TextField($opts=[]){
        return new TextField($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return DecimalField
     */
    public static function DecimalField($opts=[]){
        return new DecimalField($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return IntegerField
     */
    public static function IntegerField($opts=[]){
        return new IntegerField($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return DateTimeField
     */
    public static function DateTimeField($opts=[]){
        return new DateTimeField($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return DateField
     */
    public static function DateField($opts=[]){
        return new DateField($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return TimeField
     */
    public static function TimeField($opts=[]){
        return new TimeField($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return ForeignKey
     */
    public static function ForeignKey($opts=[]){
        return new ForeignKey($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return OneToOne
     */
    public static function OneToOne($opts=[]){
        return new OneToOne($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return ManyToMany
     */
    public static function ManyToMany($opts=[]){
        return new ManyToMany($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return HasMany
     */
    public static function HasMany($opts=[]){
        return new HasMany($opts);
    }

    /**
     * @ignore
     * @param array $opts
     * @return HasOne
     */
    public static function HasOne($opts=[]){
        return new HasOne($opts);
    }


}
