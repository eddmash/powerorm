
#############################
Model inheritance
#############################

Model inheritance in Powerorm works almost identically to the way normal class inheritance works in PHP,
That means the base class should subclass PModel.

The only decision you have to make is whether you want the parent models to be models in their own right
(with their own database tables), or if the parents are just holders of common information that will only
be visible through the child models.

There are three styles of inheritance that are possible in Powerorm:

- Often, you will just want to use the parent class to hold information that you don’t want to have to type out for
  each child model. This class isn’t going to ever be used in isolation, so Abstract base classes are what you’re after.

- If you’re subclassing an existing model (perhaps something from another application entirely) and want each model
  to have its own database table, Multi-table inheritance is the way to go.

- Finally, if you only want to modify the PHP-level behavior of a model, without changing the models fields in any
  way, you can use Proxy models.

