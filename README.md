# powerorm 
A light weight easy to use CodeIgniter ORM.

# Introduction
I created this project because i required a lightweight easy to use orm that i could use in my Codeigniter projects 
with the least amount of configuration . sort of `plug and play` if you will. 
while at the same time reducing repetition and providing a consistent way to deal with databases.

That is, i wanted to avoid the repetitive actions of creating migration files, creating query method to query the 
database and also wanted to be able to see all my database table fields on my models without me going to the database 
tables themselves and use this fields to interact with the database.

This ORM is heavily inspired by Django ORM. Because i personally love how there orm works.

# Install

- Via Composer

`composer require eddmash/powerorm`

- Download or Clone package from github.

# Load the Library

Load the library like any other Codeigniter library.

`$autoload['libraries'] = array('session', 'powerorm/orm', 'powerauth/auth')`

# Note

The ORM does not replace codeigniters Active records, you can still them as if the orm was not in existence. 
if you are using active records do not assume the database is loaded since the Orm loads the database only when need be. 

# Dependecies
This orm heavily relies on the core libraries provided with CodeIgniter without making any alterations on them.
This means that any configuration made for the following libraries will affect how the ORM operates

- The CodeIgniter Migration library

- The CodeIgniter Database classes

- The QueryBuilder Class.

- The Form helpers.

- The Form validation class.

# Configuration
 - Enable migrations 
    Locate `application/config/migration.php` and enable migration. ```$config['migration_enabled'] = TRUE;```
 - Enable query builder class 
    Locate `application/config/database.php` and enable migration. ```$query_builder = TRUE;```
 - Load the **powerorm** library. preferable on autoload. ```$autoload['libraries'] = array('powerorm/orm',);```
 

# Features
 - Allows to fully think of the database and its table in an object oriented manner i.e. 
    table are represented by model and columns are represented by fields.
 - Create automatic migrations.
 - Create forms automatically based on models.
 - All fields visible on the model, no need to look at the database table when you want to interact with the database.
 - Provides database interaction methods
 
# How It works

The orm takes each model created to represent a database table, it also takes any fields defined in the **fields()**
 method to represent a column in that table. 
 
# Usage 

## 1. The models
To Start using the orm, create models as you normally would in CodeIgniter and extend the **PModel** instead of 
**CI_Model** as shown below .
    
    
    class User extends PModel{
        
        public function fields(){
            $this->username = ORM::CharField(['max_length'=>25]);
            $this->f_name = ORM::CharField(['max_length'=>25]);
            $this->l_name = ORM::CharField(['max_length'=>25, 'form_widget'=>'textarea']);
            $this->password = ORM::CharField(['max_length'=>65]);
            $this->age = ORM::CharField(['max_length'=>65, 'form_widget'=>'currency', 'empty_label'=>'%%%%']);
            $this->email = ORM::EmailField();
            $this->roles = ORM::ManyToMany(['model'=>'role']); // Many To Many Relationship roles model
        }
    }
    
    class Role extends PModel{
    
        public function fields()
        {
            $this->name = ORM::CharField(['max_length'=>30]);
            $this->users = ORM::HasMany(['model'=>'user']); // creates a reverse connection to user model
            $this->slug = ORM::CharField(['max_length'=>30]);
        }
    }
    
    class Profile extends PModel{
    
        public function fields(){
            $this->user = ORM::OneToOne(['model'=>'user', 'primary_key'=>TRUE]); // One To One Relationship to user model
            $this->town = ORM::CharField(['max_length'=>30, 'db_index'=>TRUE]);
            $this->country = ORM::CharField(['max_length'=>30, 'unique'=>TRUE,'null'=>FALSE, 'default'=>'kenya']);
            $this->box = ORM::CharField(['max_length'=>30]);
            $this->code = ORM::CharField(['max_length'=>30]); 
            $this->ceo = ORM::ForeignKey(['model'=>'user']);  // One To Many Relationship to user model
        }
    
    } 
     
    
The above 3 methods extend **PModel** , Extending this model requires that you implement the **fields()** method.
The Main purpose of this method is to create fields that the model will use to map to database table columns.

The orm provides different field type that represent the different types of database columns 
e.g. **CharField** represent **varchar** type,  .

Learn more about fields here http://eddmash.github.io/powerorm/docs/namespaces/powerorm.model.field.html



## 2. Migration
Create a migrations controller and add the following methods:

    class Migrations extends CI_Controller{
            
            /**
            * Generate migration files.
            */
            public function makemigrations(){
                ORM::makemigrations();
            }
        
            /**
             * Runs the latest migrations
             */
            public function migrate()
            {
                Orm::migrate();
            }
        
            /**
            * roll back to a previouse migration
            */
            public function rollback($version)
            {
                Orm::rollback($version);
            }
    }        
        
Once you have the models created, on the command line/ terminal, run the following command

`php index.php migrations/makemigrations`

This command detects any changes made to you models and creates the necessary migrations file.

Once the files have been generated you can run the following command to actually executes the migrations fields to make 
the database match the model state

`php index.php migrations/migrate`
 
Looking at the roles model, it will generate a migration file that looks as shown below:

    // Migration for the model role
    
    <?php
    
    use powerorm\migrations\RunSql;
    
    class Migration_Create_Role_1458811308_1121 extends CI_Migration{
    
    	public $model= 'role';
    	public $depends= [];
    
    	public function up(){
    		RunSql::add_field("name VARCHAR(30) NOT NULL");
    		RunSql::add_field("slug VARCHAR(30) NOT NULL");
    		RunSql::add_field("id INT NOT NULL AUTO_INCREMENT");
    		RunSql::add_field_constraint("PRIMARY KEY (id)");
    		RunSql::create_table("role", TRUE, ['ENGINE'=>'InnoDB']);
    	}
    
    	public function down(){
    		RunSql::drop_table("role", TRUE);
    	}
    
    	public function state(){
    		return	[
    			'model_name'=>'role',
    			'operation'=>'add_model',
    			'table_name'=>'role',
    			'fields'=>	[
    				'name'=>	[
    					'field_options'=>	[
    						'name'=>'name',
    						'type'=>'VARCHAR(30)',
    						'null'=> FALSE,
    						'unique'=> FALSE,
    						'max_length'=>30,
    						'primary_key'=> FALSE,
    						'auto'=> FALSE,
    						'default'=> NULL,
    						'signed'=> FALSE,
    						'constraint_name'=>'',
    						'db_column'=>'name',
    						'db_index'=> NULL,
    						'container_model'=>'role',
    					],
    					'class'=>'powerorm\model\field\CharField',
    				],
    				'slug'=>	[
    					'field_options'=>	[
    						'name'=>'slug',
    						'type'=>'VARCHAR(30)',
    						'null'=> FALSE,
    						'unique'=> FALSE,
    						'max_length'=>30,
    						'primary_key'=> FALSE,
    						'auto'=> FALSE,
    						'default'=> NULL,
    						'signed'=> FALSE,
    						'constraint_name'=>'',
    						'db_column'=>'slug',
    						'db_index'=> NULL,
    						'container_model'=>'role',
    					],
    					'class'=>'powerorm\model\field\CharField',
    				],
    				'id'=>	[
    					'field_options'=>	[
    						'name'=>'id',
    						'type'=>'INT',
    						'null'=> FALSE,
    						'unique'=> FALSE,
    						'max_length'=> NULL,
    						'primary_key'=> TRUE,
    						'auto'=> TRUE,
    						'default'=> NULL,
    						'signed'=> FALSE,
    						'constraint_name'=>'',
    						'db_column'=>'id',
    						'db_index'=> NULL,
    						'container_model'=>'role',
    					],
    					'class'=>'powerorm\model\field\AutoField',
    				],
    			],
    		];
    	}
    
    }


## 3. Querying
The PModel class also provides several methods that can be used to interact with the database tables represented by 
each model.

e.g. inside any controller method

     public function index(){
        $this->load->model('user');
        
        $this->user->all(); // fetch all users in the database table represented by model user
        
        $this->user->get(1); // get user with the primary key 1
        
        $this->user->get(['username'=>'sia']); // get user with the username sia
        
        $this->user->filter(['l_name'=>'sia']); // get all users with the l_name sia
        
        // To save a user
        $user1 = $this->user;
        $user1->username = 'matt';
        $user1->password = '$qwer#$';
        $user1->save();
     }

See more methods and examples here http://eddmash.github.io/powerorm/docs/classes/powerorm.queries.Queryset.html
and 
http://eddmash.github.io/powerorm/docs/classes/PModel.html

## 4. Form
The PModel also provides a form_builder that helps you generate and customize form for a specific model.

e.g. inside any controller method

    public function index(){
        $this->load->model('user');
        
        // this will generate a general form for the model
         $form_builder = $this->user->form_builder(); // get the form builder
        
        // this will generate a form loaded with the values of this model e.g. when updating
        $form_builder = $this->user->get(1)->form_builder(); // get the form builder
        
        // build the form using only password and username
        $form_builder->only(['password','username']);  
        
        // get the form
        $form = $form_builder->form(); 
        
        $data['form'] = $form;
        
        // load it on view
        $this->load->view('user_view', $data);
    }

To use the form on the view

    echo $form->open();
        foreach($form->fields as $field):
            echo $field->errors();
            echo $field->label();
            echo $field->widget(array("class"=>"form-control"));
        endforeach;
        
        echo "<input type='submit' value='save'>"; // you have to add this manually it is not generated
    echo $form->close(); 

Or accessing each field individually from the form itself; as shown below:

        echo $form->open();
            echo $form->label('username');
            echo $form->widget('username', ["class"=>"form-control"]);
            
            echo $form->label('password');
            echo $form->widget('password', ["class"=>"form-control"]);
            
            echo $form->label('age');
            echo $form->widget('age', ["class"=>"form-control"]);
            
            echo "<input type='submit' value='save'>"; // you have to add this manually it is not generated
            
        echo $form->close(); 
        
 
 
see more form builder methods and examples here http://eddmash.github.io/powerorm/docs/classes/powerorm.form.ModelForm.html
 

# Find detailed information here

http://eddmash.github.io/powerorm/docs/classes/Orm.html

# Requirements on 
 - CodeIgniter 3.0+
 - php 5.4+
 - on MYsql 5.5+
 - on Postgresql 9+

# Related CODEIGNITER Libraries.

 - powerdispatch
 
    An Event Dispatching mechanism for Codeigniter https://github.com/eddmash/powerdispatch
 
 