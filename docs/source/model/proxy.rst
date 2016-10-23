
#############################
Proxy models
#############################

When using multi-table inheritance, a new database table is created for each subclass of a model.

This is usually the desired behavior, since the subclass needs a place to store any additional data fields
that are not present on the base class.

Sometimes, however, you only want to change the php behavior of a model – perhaps to add a new method.

This is what proxy model inheritance is for:

- creating a proxy for the original model. You can create, delete and update instances of the proxy model
  and all the data will be saved as if you were using the original (non-proxied) model.

- The difference is that you can change things like the default model ordering or the default manager in
  the proxy, without having to alter the original.

Proxy models are declared like normal models. You tell Powerorm that it’s a proxy model by setting
the `proxy` attribute of the class to True.

.. code-block:: php

	 class Employee extends PModel{
	      public function fields(){
	         name = PModel::CharField(['max_length'=>100]);
	         age = PModel::InteferField();
	      }
	 }

	 class Auditor extends Employee{

	      public $proxy = TRUE;

	      // get how many times a specific accountant has audited a specific employee.
	      public function get_times_has_audited($employee){}
	 }

.. note::
	Because codeigniter does not autoload classes you need to load the base class first before
	the child. when using e.g. load the models defined above as show.

.. code-block:: php

	 // load model
	 $this->load->model('employee');
	 $this->load->model('auditor');

The Auditor class operates on the same database table as its parent Person class.
In particular, any new instances of Employee will also be accessible through Auditor, and vice-versa:

.. code-block:: php

	 $p = $this->employee->create(['name='foobar']);
	 $this->auditor->get(['name'='foobar']);

.. note:: QuerySets still return the model that was requested

There is no way to have Powerorm to return, say, a Auditor object whenever you query for Employee objects.

A queryset for Employee objects will return those types of objects.

The whole point of proxy objects is that code relying on the original Employee will use those and your
own code can use the extensions you included (that no other code is relying on anyway).
It is not a way to replace the Employee (or any other) model everywhere with something of your own creation.



