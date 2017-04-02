############
Proxy models
############

When using multi-table inheritance, a new database table is created for each subclass of a model.

This is usually the desired behavior, since the subclass needs a place to store any additional data fields
that are not present on the base class.

Sometimes, however, you only want to change the php behavior of a model – perhaps to add a new method.

This is what **proxy model** inheritance is for:

- creating a proxy for the original model. You can create, delete and update instances of the proxy model
  and all the data will be saved as if you were using the original (non-proxied) model.

- The difference is that you can change things like the default model ordering in
  the proxy, without having to alter the original.

Proxy models are declared like normal models. You tell PowerOrm that it's a proxy model by setting
the `proxy` meta setting of the class to True.

.. code-block:: php

    use Eddmash\PowerOrm\Model\Model;

    class Employee extends Model
    {
        private function unboundFields()
        {
            return [
                'name' => Model::CharField(['maxLength' => 100]),
                'age' => Model::IntegerField()
            ];
        }
    }

    class Auditor extends Employee
    {

        public function pricePerAuditJob($employee)
        {
            // logic
        }

        public function getMetaSettings()
        {
            return [
                'proxy' => true
            ];
        }
    }

The Auditor class operates on the same database table as its parent Employee class.

In particular, any new instances of Employee will also be accessible through Auditor, and vice-versa:

.. code-block:: php

    $employee = new Employee();
    $employee->name = 'foobar';
    $employee->save();

    Auditor::objects()->get(['name'='foobar']);

Learn more on :doc:`Querying Models <../queries/index>`

.. note:: QuerySets still return the model that was requested

    There is no way to have Powerorm return, say, a Auditor object whenever you query for Employee objects.
    A queryset for Employee objects will return those employee objects.
    The whole point of proxy objects is that code relying on the original Employee will use employee objects and your
    own code can use the extensions you included (that no other code is relying on anyway).
    It is not a way to replace the Employee (or any other) model everywhere with something of your own creation.



