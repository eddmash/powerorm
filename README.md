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

- The CodeIgniter Database classes

- The QueryBuilder Class.

- The Form helpers.

- The Form validation class.

# Configuration 
 - Copy the **pmanger.php** file located in powerorm/bin/ to the same folder as `index.php`
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
            $this->first_name = PModel::CharField(['max_length'=>50, 'unique'=>TRUE]);
            $this->age = PModel::CharField(['max_length'=>50, 'null'=>TRUE, 'default'=>10]);
            $this->last_name = PModel::CharField(['max_length'=>50]);
            $this->password = PModel::CharField(['max_length'=>50]);
            $this->roles = PModel::ForeignKey(['model'=>'role']); 
        }
    }
    
    class Role extends PModel{
    
        public function fields()
        {
            $this->name = PModel::CharField(['max_length'=>40]);
            $this->code = PModel::CharField(['max_length'=>40]); 
            $this->users = PModel::HasMany(['model'=>'user']); // creates a reverse connection to user model 
        }
    } 
     
    
The above 3 methods extend **PModel** , Extending this model requires that you implement the **fields()** method.
The Main purpose of this method is to create fields that the model will use to map to database table columns.

The orm provides different field type that represent the different types of database columns 
e.g. **CharField** represent **varchar** type,  .

Learn more about fields here http://eddmash.github.io/powerorm/docs/namespaces/powerorm.model.field.html



## 2. Migration
To interact with the migration module, you do it through the command line.

The orm comes with commandline tool, to use it, you need to Copy the **pmanger.php** file located in powerorm/bin/ 
to the same folder as `index.php` i.e. in the same directory as the application folder.
 
Having created the models like we have above, ON the command line/ terminal, run the following command, 
get in the codeigniter installation folder, i.e. 
the parent folder that house application folder, system folder, index.php, and now pmanager.php

`php pmanager.php makemigrations`

This command detects any changes made to you models and creates the necessary migrations file.

Once the files have been generated you can run the following command to actually executes the migrations fields to make 
the database match the model state

`php pmanager.php migrate`
 
Based on the models we created earlier, it will generate a migration file that looks as shown below:

    // Migration for the model role
    
    <?php  
            
        namespace app\migrations;
        
        use powerorm\migrations\Migration;
        use powerorm\migrations\operations\CreateModel;
        use powerorm\model\field\CharField;
        use powerorm\model\field\AutoField;
        use powerorm\migrations\operations\AddField;
        use powerorm\model\field\ForeignKey;
        
        
        class Migration_0001_Initial extends Migration{
        
            public function get_dependency(){
                return 	[
                ] ;
            }
        
            public function operations(){
                return [
                    new CreateModel(
                        [
                            'model'=> 'user',
                            'fields'=>[ 
                                'first_name'=> new CharField(['max_length'=> 50, 'unique'=> TRUE]),
                                'age'=> new CharField(['max_length'=> 50, 'null'=> TRUE, 'default'=> 10]),
                                'last_name'=> new CharField(['max_length'=> 50]),
                                'password'=> new CharField(['max_length'=> 50]),
                                'id'=> new AutoField(['primary_key'=> TRUE, 'unique'=> TRUE]),
                            ]
                        ]
                    ),
        
                    new CreateModel(
                        [
                            'model'=> 'role',
                            'fields'=>[ 
                                'name'=> new CharField(['max_length'=> 40]),
                                'code'=> new CharField(['max_length'=> 40]),
                                'id'=> new AutoField(['primary_key'=> TRUE, 'unique'=> TRUE]),
                            ]
                        ]
                    ), 
        
                    new AddField(
                        [
                            'model'=> 'user',
                            'name'=> 'roles',
                            'field'=> new ForeignKey(['model'=> 'role']),
                        ]
                    ),
        
                ] ;
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
 
 