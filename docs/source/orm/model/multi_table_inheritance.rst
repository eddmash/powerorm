#############################
Multi-table inheritance
#############################

The second type of model inheritance supported by Powerorm is when each model in
the hierarchy is a table all by itself. i.e. each model corresponds to its own
database table and can be queried and created individually.

The inheritance relationship introduces links between the child model and
its parents (via an automatically-created OneToOne).

For example:

.. code-block:: php

    use Eddmash\PowerOrm\Model\Model;

    class Place extends Model
    {
        public function unboundFields()
        {
            return [
                'name' => Model::CharField(['maxLength' => 100]),
                'address' => Model::CharField(['maxLength' => 80])
            ];
        }
    }

    class Restaurant extends Place
    {
        public function unboundFields()
        {
            return [
                'serves_hot_dogs' => Model::BooleanField(['default' => false]),
                'serves_pizza' => Model::BooleanField(['default' => false])
            ];
        }
    }


All of the fields of Place will also be available in Restaurant, although the
data will reside in a different database table. So these are both possible:

.. code-block:: php

	Restaurant::objects()->filter([name=>"Bob's Cafe"]);
 	Place::objects()->filter([name=>"Bob's Cafe"]);


If you have a Place that is also a Restaurant, you can get from the Place object
 to the Restaurant object by using the lower-case version of the model name:

.. code-block:: php

	$p = Place::objects()->get(['id'=>12]);
 	// If p is a Restaurant object, this will give the child class:
 	$p->restaurant

However, if `$p` in the above example was not a Restaurant
(it had been created directly as a Place
object or was the parent of some other class), referring to p.restaurant would
throw an exception.

In reality the orm creates the base model table as expected in the database i.e
with all the field the model specifies but for the child model it creates the a
table with fields that have been specfied in the child model
and create a one-to-one connection to the base models' table.


