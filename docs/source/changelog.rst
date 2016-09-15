##########
Change Log
##########

Version 1.1.0-Pre-Alpha
======================

**Rewrite of the PowerOrm**

Release Date: Not Released

Additions
-----------------------
- Registry 
        
        This a registry of all the models the orm detected.
        
    - Manager
        
        This is in charge of managing all the command line related tasks for the orm, 
        no need for users to create a migration controller to be able to work with 
        the orm anymore
        
    - db schemas
    
        Adds db schemas to extend the functionality of the db forge in an easy and 
        consistent manner

    - Support

        - Migrations now supports postgres 9+
        
    - A different Migration Module and Migrations commands

        - check
        - showmigration
        - migrate
        - makemigrations 
        - robot 
        - help
        - version
        
    - Migrations is able to support :
        
         - field name
         
    - Forms class
        
        - You can deal with forms in two ways :

            - Use the default form provided by the ORM

                - this is achieved by calling the $this->orm->get_form()
                which helps you build your form.

            - Define a form class in the forms folder.

                - create a forms folder at the same level as the libraries folder, 
                if it does not exist.
                - inside it,  Define a class the extends PForm class
                - just like model, override the fields() method and define you 
                forms fields
                - and just like models, you the form fields from PForm e.g. 
                PFORM::EmailField() .
                
        - the benefits of defining a form class is that it makes it very easy to 
        do custom validations i.e. that those not handled by CI_form_validation class
    
    - From the orm orm object `$this->orm` you can be able to access the following :
        
        - registry = this->orm->get_registry()
        - orm version = this->orm->get_version()
        - Orm Form = this->orm->get_form() 
                this builds a form based on the 
                 - orms core form class by default
                 - use a custom form you have defined if you pass the form as an argument or 
                 - model to build a form based on a model that already exists.
        
    - Contributor, DeConstructable interface to provide a consistent way of 
    deconstructing  objects and contributing objects to other objects


Improvements and Fixes
-----------------------
    
    - Provides a consistent api for the models meta data, for easier access.
    - A consistent approach to how checks are carried out.
    - Migrations operations
    - Queryset to use the mode consistent model meta
    - Check system by redefining the check message levels

Rewrites
-----------------------
    
    - The whole migration module.
        
        - This module saw the addition of some important class:

               - AutoDetector - rewrite, keeps track of all changes within the models
                and produces the migration files..
               - Executor - responsible for running the migrations 
               applying/unapplying.
               - Graph - Keeps track of how the migrations are related to each 
               other i.e. which migration needs to run before which.
               - Migration - this was a rewrite, this is the base class for 
               all migrations
               - Loader - this was a rewrite, loads migrations found in the 
               migrations folder
               - Questioner - this was a rewrite
               - Recorder - this helps in keeping track of which migrations 
               have been applied/unapplied by storing them in the database
               - State - this was rewrite to allow to use the new registry created
                            
        - This drops using the CI_MIGRATION module and implements a differrent 
        approach of doing migration this was prompted by need to reduce the number 
        of migration files the previous version was producing

    - The whole console module
        
         - This removes the need for user to create a migration controller to be able 
         to use the orm just copy the `pmanger.php` file located at 
	eddmash\powerorm\bin\pmanger.php to the same directory as `index.php`
         
         - This also provides a consistent api for adding more commands within the orm
         
    - The whole Form Module
        
         - This was done to enable defining forms as classes on a separate php file.
         
         - This rewrite resulted in the following classes:

                - Form  - this is the overall class, it keeps track of a forms fields,
                form errors etc
                - Field - this keeps track of information relating to a form field 
                like which errors it has,  which label to use, the value of the 
                field etc
                            
                - Widget - this is responsible for rendering/ creating the 
                expected html widget eg. input, textarea, password.
                            
                - ValidationError - this thrown if a validation fails.
            
         - Whilst the new Form Module has its own validation technique, it heavily 
         relies of the  Ci_form_validation class. the new validation technique is meant 
         to be used when doing validation that is not handle by Ci_form_validation class.
 	 
	 You will mostly use it in the following form methods , i.e. if you have defined a form class :

                    - the forms clean() method
                    - the forms clean_{field_name}() method

