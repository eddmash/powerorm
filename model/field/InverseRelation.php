<?php
/**
 * Class responsible for representing the reverse direction of a relationship.
 */
/**
 *
 */
namespace powerorm\model\field;
use powerorm\exceptions\OrmExceptions;

/**
 * Creates a reverse connection for a relationship.
 *
 * The primary purpose of this connection is to perform lookup queries.
 *
 * From inverse side to the owning side where the owning side is the model that defines the relationship i.e.
 * it has either of the following fields to a model:
 * - ManyToMany
 * - ForeignKey
 * - OneToOne
 *
 * <h4>Example</h4>
 *
 * <pre><code>
 * // owning side of the relationship i.e. since it defines the relationship it owns the relationship.
 * class User extends PModel{
 *      public function fields(){
 *          $this->username = ORM::CharField(['max_length'=>25]);
 *          $this->f_name = ORM::CharField(['max_length'=>25]);
 *          ...// more fields
 *          $this->roles = ORM::ManyToMany(['model'=>'role']); // Many To Many Relationship roles model
 *      }
 * }
 *
 * // the inverse side.
 * class Role extends PModel{
 *      public function fields(){
 *          $this->name = ORM::CharField(['max_length'=>30]);
 *          $this->users = ORM::HasMany(['model'=>'user']); // creates a reverse connection to user model
 *          $this->slug = ORM::CharField(['max_length'=>30]);
 *      }
 * }</code></pre>
 *
 *
 * Should not be instantiated.use its subclasse HasMany and HasOne.
 *
 * @package powerorm\model\field
 */
trait InverseRelation{

    public function is_inverse()
    {
        return TRUE;
    }
}


/**
 * Creates a reverse connection to a model that defines a one-toone relationship using OneToOne field.
 *
 * @package powerorm\model\field
 */
class HasOneField extends ManyToOneField{
    use InverseRelation;

    public function __construct($field_options=[])
    {
        $this->M2O = FALSE;
        parent::__construct($field_options);



        if(!array_key_exists('mapped_by', $field_options)):
            throw new OrmExceptions(sprintf('%s fields need `mapped_by`', $this->get_class_name()));
        endif;


        $this->relation = new OneToManyObject([
            'model'=>$field_options['model'],
            'field'=>$this,
            'mapped_by'=> $field_options['mapped_by']
        ]);
    }

    public function db_column_name()
    { 
        return sprintf('%s_id', $this->lower_case($this->relation->get_mapped_by()->name));
    }


    public function contribute_to_class($name, $obj){
        Field::contribute_to_class($name,$obj);
        $this->container_model->{$name} = ReverseManyToOneAccessor::instance($this->container_model, $this);
    }
}