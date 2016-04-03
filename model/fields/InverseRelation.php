<?php
/**
 * Class responsible for representing the reverse direction of a relationship.
 */
/**
 *
 */
namespace powerorm\model\field;

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
 * @package powerorm\model\field
 */
abstract class InverseRelation extends RelatedField{
    /**
     * @ignore
     * @var bool
     */
    public $inverse = TRUE;
}

/**
 * This creates a reverse connection to a model that define one-to-many/ many-to-many relationships
 * by using ForeignKey or ManyToMAny Fields
 * @package powerorm\model\field
 */
class HasMany extends InverseRelation{}

/**
 * Creates a reverse connection to a model that defines a one-toone relationship using OneToOne field.
 * @package powerorm\model\field
 */
class HasOne extends InverseRelation{}