PowerForm User Guide
####################

The Form component is a tool to help you solve the problem of allowing end-users to interact with the data and modify
the data in your application. And though traditionally this has been through HTML forms, the component focuses on
processing data to and from your client and application, whether that data be from a normal form post or from an API.

Installation
------------

Via composer **(recommended)**::

	composer require eddmash/powerform:"@dev"

Or add this to the composer.json file::

	composer require eddmash/powerform:@dev

You could also Download or Clone package from github.


Then, require the vendor/autoload.php file to enable the autoloading mechanism provided by Composer. Otherwise, your
application won't be able to find the classes of Powerform.

.. toctree::
   :maxdepth: 2

   form
   form_api
   fields
   widgets
   modelform
   validations
   validators
   example