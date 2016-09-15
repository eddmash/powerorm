
#############################
Abstract base classes
#############################

.. note::
	Because codeigniter does not autoload classes you need to require the file with the abstract
class in your model file.

Abstract base classes are useful when you want to put some common information into a number of other models.
You create an Abstract base class by simply creating a normal php abstract base class.

This model will then not be used to create any database table.

Instead, when it is used as a base class for other models, its fields will be added to those of the child class.

Any fields defined in the Abstract that are again defined in the Child class will be over written by those in the
child class.::

	abstract class CommonInfo extends PModel{
	      public function fields(){
		  name = PModel::CharField(['max_length'=>100]);
		  age = PModel::IntegerField();
	      }
	 }

	 class Student extends CommonInfo{
	      public function fields(){
		  home_group = PModel::CharField(['max_length'=>5]);
	      }
	 }

The Student model will have three fields: name, age and home_group.

The CommonInfo model cannot be used as a normal model, since it is an abstract base class.
It does not generate a database table, and cannot be instantiated or saved directly.

For many uses, this type of model inheritance will be exactly what you want.

It provides a way to factor out common information at the php level, while still only
creating one database table per child model at the database level.

.. note:: Attribute inheritance
	When inheriting, Some attributes will need to be overridden in child classes, since it doesn't make sense to
	set them in the base class.

For example, setting `table_name` would mean that all the child classes (the ones that don’t specify `table_name` explictly)
would use the same database table,which is almost certainly not what you want.


