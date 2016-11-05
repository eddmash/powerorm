
#############################
Multi-table inheritance
#############################

The second type of model inheritance supported by Powerorm is when each model in the hierarchy is a table all by itself.
Each model corresponds to its own database table and can be queried and created individually.

The inheritance relationship introduces links between the child model and each of its parents (via an automatically-created OneToOne).

For example:

.. code-block:: php

    class Place extends PModel
    {
        public function unboundFields()
        {
            return [
                'name' => PModel::CharField(['maxLength' => 100]),
                'address' => PModel::CharField(['maxLength' => 80])
            ];
        }
    }

    class Restaurant extends Place
    {
        public function unboundFields()
        {
            return [
                'serves_hot_dogs' => PModel::BooleanField(['default' => false]),
                'serves_pizza' => PModel::BooleanField(['default' => false])
            ];
        }
    }

.. note::
	Because codeigniter does not autoload classes you need to load the base class first before
	the child. when using e.g. load the models defined above as show :

Loading models :

.. code-block:: php

 	$this->load->model('place');
 	$this->load->model('restraurant');

All of the fields of Place will also be available in Restaurant, although the data will reside in a
different database table. So these are both possible:

.. code-block:: php

	$this->place->filter([name="Bob's Cafe"]);
 	$this->restaurant->filter([name="Bob's Cafe"]);


If you have a Place that is also a Restaurant, you can get from the Place object to the Restaurant
object by using the lower-case version of the model name:

.. code-block:: php

	$p = $this->place->get(['id'=12]);
 	// If p is a Restaurant object, this will give the child class:
 	$p.restaurant

However, if p in the above example was not a Restaurant (it had been created directly as a Place
object or was the parent of some other class), referring to p.restaurant would raise a exception.

In reality the orm creates the base model table as expected in the database i.e with all the field the model
specifies but for the child model it creates the a table with fields that have been specfied in the child model
and create a one-to-one connection to the base models' table.


