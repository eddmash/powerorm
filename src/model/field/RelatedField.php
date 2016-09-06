<?php
/**
 * The Relationship fields.
 */

/**
 *
 */
namespace eddmash\powerorm\model\field;

use eddmash\powerorm\exceptions\OrmExceptions;
use eddmash\powerorm\model\BaseModel;
use Orm;

/**
 * @param callback $callback
 * @param BaseModel $model
 * @param array $related_models
 * @param array $kwargs
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function lazy_related_operation($callback, $model, $related_models, $kwargs)
{

//    $model_names = [$model->meta->model_name];
    $registry = $model->meta->registry;

//    $model_names = array_merge($model_names, $related_models);
    $related_model_names = [];
    foreach ($related_models as $related_model) :
        if ($related_model instanceof BaseModel):
            $related_model_names[] = $related_model->meta->model_name; else:
            $related_model_names[] = $related_model;
    endif;
    endforeach;

    $registry->lazy_model_ops($callback, $related_model_names, $kwargs);
}

/**
 * Creates a Relationship column or table depending on the type of relationship.
 *
 * {@inheritdoc}
 *
 * @package eddmash\powerorm\model\field
 */
class RelatedField extends Field
{

    /**
     * Controls whether or not a constraint should be created in the database for this foreign key.
     *
     * The default is True, and that’s almost certainly what you want; setting this to False can be very bad for data
     * integrity. That said, here are some scenarios where you might want to do this:
     * - You have legacy data that is not valid.
     * - You’re sharing your database.
     *
     * If this is set to False, accessing a related object that doesn’t exist will raise its DoesNotExist exception.
     * @var
     */
    public $db_constraint = false;

    /**
     * This is the model to create a relationship with.
     * @var BaseModel
     */
    protected $model;

    /**todo
     * When an object referenced by a ForeignKey is deleted, Powerorm by default emulates the behavior of the SQL
     * constraint ON DELETE CASCADE and also deletes the object containing the ForeignKey.
     *
     * - on_delete
     *
     *      When an object referenced by a ForeignKey is deleted,
     *
     *      Powerorm by default emulates the behavior of the SQL constraint ON DELETE CASCADE and also deletes the object
     *      containing the ForeignKey.
     *
     *      This behavior can be overridden by specifying the on_delete argument.
     *      For example, if you have a nullable ForeignKey and you want it to be set null when the referenced object is deleted:
     *
     *      user = ORM::ForeignKey(['model_name'=>'user_model', 'blank'=>True, 'null'=>True, 'on_delete'=>ORM::SET_NULL])
     *
     *      The possible values for on_delete are found in ORM:
     *      -   CASCADE
     *
     *          Cascade deletes; the default.
     *
     *      -   PROTECT
     *
     *          Prevent deletion of the referenced object by raising ProtectedError.
     *
     *      -   SET_NULL
     *
     *          Set the ForeignKey null; this is only possible if null is True.
     *
     *      -   SET_DEFAULT
     *
     *          Set the ForeignKey to its default value; a default for the ForeignKey must be set.
     * @var
     */
    protected $on_delete;

    /**
     * @inheritdoc
     * @var bool
     */
    public $is_relation = true;


    /**
     * Used to set the field on the model to use for display e.g for the model user_model.
     * you could set the form_display_field to username, this will result in form select box shown below
     *
     * <pre><code>&lt;select &gt;
     *      &lt; option value=1 &gt; math // <----- the username.
     * &lt;/select &gt;</code></pre>
     * @var
     */
    public $form_display_field;

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = [])
    {
        if (!array_key_exists('model', $field_options)):
            throw new OrmExceptions(sprintf('%1$s requires a related model', $this->get_class_name()));
        endif;

        parent::__construct($field_options);
    }

    public function check()
    {
        $checks = parent::check();
        $checks = $this->add_check($checks, $this->_check_relation_model());
        return $checks;
    }

    /**
     * @ignore
     * @return mixed
     */
    public function _check_relation_model()
    {

        // todo is it astring or an object
//        $rel_model_name = $this->relation->model->meta->model_name;
//        if(!$this->container_model->meta->registry->has_model($rel_model_name)):
//            $message = 'Field { %1$s } defines a relation with model { %2$s }, which does not exist or is abstract.';
//            return [
//                Checks::error([
//                    "message"=>sprintf($message, $this->get_class_name(), ucfirst($rel_model_name)),
//                    "hint"=>NULL,
//                    "context"=>$this,
//                    "id"=>"fields.E300"
//                ])
//            ];
//        endif;

        return [];
    }

    /**
     * @param string $name
     * @param BaseModel $obj
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function contribute_to_class($name, $obj)
    {
        parent::contribute_to_class($name, $obj);
        if (!$obj->meta->abstract):

            /**
             * callback to be invoked when a related model has been resolved.
             * @param BaseModel $related_model
             * @param array $kwargs
             * @since 1.1.0
             * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
             */
            $callback = function ($related_model, $kwargs) {
                if (isset($kwargs['field'])):
                    $field = $kwargs['field'];
                $field->relation->set_model($related_model);
                endif;
            };

        lazy_related_operation($callback, $this->container_model, [$this->relation->get_model()], ['field' => $this]);
        endif;
    }

    public function related_model()
    {
        Orm::get_registry()->is_app_ready();
        return $this->relation->get_model();
    }
}
