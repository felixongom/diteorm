## DiteORM.

This is PHP ORM for interacting with relational database like Mysql, Sqlite, Posgre, Oracle etc. Currently, it supports only Sqlite, Mysql, Sqlserver and Postgresql databases.
It allows us to keep oursevles within only PHP code instead of switching between sql and PHP code.

**DiteORM** uses functions and classes to generate the right sql which is needed. This means sometimes you have to adjust the setting in the php.ini to fit the database you are using.

<!-- install -->

## Installation.

This can be done using one of this two ways;

**1 -** Download this [github repository](https://github.com/felixongom/DiteORM) and extract it into the root of your project.
```bash
cd DiteORM
composer dump-autoload -o
cd ..
```

**2 -** Using composer by running **composer require  dite-orm** at the root of you project.
```bash
composer require ongom/dite-orm
```

### Setup.

To get started, create a .env file at the root of your project, this is where you will add some configaration settings for **dite-orm**.

#### Setup for sqlite.

```php
DRIVER = sqlite
DATABASE_NAME = schooldb
RUN_SCHEMA = 1
```
- **DRIVER** is the type of database management system you are using. The value is sqlite.
- **DATABASE_NAME** is the name of the database you are using. The value is the name of your database like 'school_database'.

#### Setup for mysql, sqlserver, postgre.

First create the database example 'schooldb' then add the following code to the .env.
```php
DRIVER = mysql
DATABASE_NAME = schooldb
SERVER_NAME = localhost
USER_NAME = root
DATABASE_PASSWORD = 1234
RUN_SCHEMA = 1
```

- **DRIVER** is the type of database management system you are using. List of posible values are;
  - sqlite
  - mysql
  - sqlsever 
  - postgresql or pgsql
- **DATABASE_NAME** is the name of the database you are using. The value is the name of your database like 'school_database'.
- **SERVER_NAME** is the server name or port for example 'localhost' of 3605.
- **USER_NAME** is the user name for example 'root'.

- **DATABASE_PASSWORD** is the password of the database example '23R42'.

**_Other configarations that can be added to .env file._**

```php
LOGGER = 1
FETCH_MODE = std_array
RUN_SCHEMA = 1
```

- **LOGGER** is for debuging purpose.
  - 1 means you want to print queries and messeges on the screen, this should be only used in development.
  - 0 or any other number means no printing queries or messeges on the screen, it is used for production.
- **RUN_SCHEMA.** When you try to create the table using the Schema, the table will not be created, to solve this add RUN_SCHEMA = 1 to the .env file. After creating the tables you can turn the value 0 (RUN_SCHEMA = 0) or remove it completely to avoid rerunning the queries for creating the table again. When you add any new tables, you will have to turn it back to 1 and again run the code.
- **FETCH_MODE** defines how the records are going to be fetched from the database.
  - std_arrays means that the records will be fetched as a php standard array whose values can be access as shown bellow.
```php
$user = ["name"=>"tom", "age"=>36];
$user["name"]; //tom
$user["age"]; //36
```
  - std_obj means records will be fetched as stdClass object which can be access using arrow syntax as below.
```php
$user = stdClass Object ([name] => tom [email] => tom@gmail.com)
$user->name; //tom
$user->age; //36
```
  - If you don't spacify this in the .env file, it will default to **std_obj**.

## Creating tables.
Before any step you first need to autoload the outoload.php from vendor folder.

```php
require_once "path/to/vendor/autoload.php";
```
Creating tables can be done in two ways.

- Using existing database / other softwares (mysql workbench, navycat, DB browser, etc. ) to create. 
- Using Dite Schema.

**1. Using existing database or other softwares to create the database.**

When using other software like myql workbench or PHPmyadmin, all you need to care about is the primary key field.
Primary key field name is got from the name of the table written in lowercase. ie
- **Users** pk feild will be **users_id**
- **Blog_Post** pk feild will be **blog_post_id**
- **Prices** pk feild will be **Prices_id**

Then the model classes(representing each table) are defined like below.


```php
use Dite\Model\Model;

require_once "path/to/vendor/autoload.php";

//users
class Users extends Model{}
//posts
class Posts extends Model{}
// Status
class Status extends Model{}
// Status
class DB extends Model{}

```

**2. Using Dite ***Schema*** to create the database.**

Add the following code below the require statement.

```php
use Dite\Schema\Schema;
use Dite\Model\Model;
use Dite\Table\Table;


require_once "path/to/vendor/autoload.php";
//users
class Users extends Model{

  public function __construct() {
    Schema::create(Users::class, function(Table $table){
      $table->id();
      $table->string('name');
      $table->enum('gender', ['male','female']);
      $table->string('email')->unique()->notnull();
      $table->int('age')->notnull();
      $table->timestamp();
    });
  }
}
```

The above code defines a table called users with the following fields.

- users_id - integer type, primary key, autoincrement and not null.
- name - varchar(255).
- gender - enum that accept only male or female as values.
- email - unique and not null.
- age - int and not null.
- created_at - default current timestamp.
- updated_at - default current timestamp that updateds when the record updates.

You can go ahead and add a post table.
Below is the overall code on how to create three tables (users, posts, status) including foreign keys.

```php
//users
class Users extends Model{

  public function __construct() {
    Schema::create(Users::class, function(Table $table){
      $table->id();
      $table->string('name');
      $table->string('email')->unique()->notnull();
      $table->int('age')->notnull();
      $table->timestamp();
    });
  }
}

//posts
class Posts extends Model{

  public function __construct() {
    Schema::create('Posts', function(Table $table){
      $table->id();
      $table->string('title')->notnull();
      $table->string('body')->notnull();
      $table->foreignKey('users_id')->notnull();
      $table->foreignKey('status_id')->cascade();
      $table->timestamp();
    });
  }
}

// Status
class Status extends Model{

  public function __construct() {
    Schema::create(Status::class, function(Table $table){
      $table->id();
      $table->int('status')->notnull();
    });
  }
}

//Instantiating the three classes
$status = new Status()
$users = new Users()
$posts = new Posts()
```

**_Note:_**

- We have instantiated the classes starting with Status.This is because the status table is being referenced by Posts in the foreign key fieled and the table have to be created first before its is referenced.**Tables that are being reference must be above**.
  If you change the order like below, you will get a foriegn key error mostly in mysql.

```php
//Instantiating the three classes
$users = new Users()
$posts = new Posts()
$status = new Status()
```
Run the code by openinng your file in the browser. This will create the table in the database.

- You can also use Users::class or 'Users' for table name called users and so for other tables.

After the tables has bean created, You can open .env and change RUN_SCHEMA = 0 or else the tables will try to be recreated.

#### 1. Creating an intermidate table.

The intemediate table has a convention of creating it inorder for **Dite** to understand. You have to concatenate the two table names.
For example **teachers** and **courses** tables, the intermediate table will be **teachers_courses** and the primary key feild will be **teachers_courses_id** . The intermediate table must be created like below.
```php
//Teachers table
class Teachers extends Model{

public function __construct() {
    Schema::create(Teachers::class, function(Table $table){
      $table->id();
      $table->string('teacher_name');
      $table->string('email');
    });
  }
}
//Courses table
class Courses extends Model{

  public function __construct() {
    Schema::create(Courses::class, function(Table $table){
      $table->id();
      $table->string('course_name');
    });
  }
}
//Intermediate table
class Teachers_Courses extends Model{

  public function __construct() {
    Schema::create(Teachers_Courses::class, function(Table $table){
      $table->id();
      $table->foriegnKey('courses_id');
      $table->foriegnKey('teachers_id');
    });
    }
}
```
#### Meaning of each of the methods used for building the table.

- **id()** - Defines an autoincrementing primary id feild and and set it not null.
You can not chain any method on to id().
```php
$table->id();
```
- **string()** -Sql varchar feild. It takes in two parameter, one mandatory string parameter(field name like comments), second optional integers parameter which defaults to 255 (max length of the charactors accepted).
  ```php
  $table->string('name');
  //OR
  $table->string('name', 50);
  ```
- **text()** - Sql text field. It takes in two parameter, one mandatory string parameter(field name like comments), second optional integers parameter which defaults to 65535 (max length of the charactors accepted).
  ```php
  $table->text('name');
  //OR
  $table->text('name', 200);
  ```
- **longText()** - Sql text field. It takes in one mandatory string parameter(field name like Posts).
  ```php
  $table->longText('name');
  ```
- **int()** - Sql integer field. It takes in one mandatory string parameter(field name like comments).
  ```php
  $table->int('size');
  ```
- **bigint()** -Sql big interger field. It takes in one mandatory string parameter(field name like comments).
  ```php
  $table->bigInt('size');
  //OR
  $table->bigInt('size', 6);
  ```
- **enum()** - It takes in two mandatory parameter, one string parameter(field name like size), second optional array parameter(only values ccepted).Tis method does not work for postgre sql.
  ```php
  $table->enum('size', ['small','medium', 'large']);
  ```
- **unsigned()** - Sql unsign datatype field. It take in field name
  ```php
  $table->unsign('year');
  ```
- **boolean()** - Sql boolean datatype field(true or false). It take in field name.
  ```php
  $table->boolean('is_active');
  ```
- **float()** - Sql floating point datatype field. It take in field name
  ```php
  $table->float('distance');
  ```
- **double()** -Sql double datatype field. It take field in name.
  ```php
  $table->double('distances');
  ```
- **decimal()** -Sql decimal datatype field. It take in field name. This may not be avilable in other DRIVER
  ```php
  $table->decimal('distance');
  ```
- **year()** -Sql year datatype field. It take in field name.
  ```php
  $table->year('Year_of_birth');
  ```
- **timestamp()** -Sql timestamp. It will create the created_at and updated_at columns.
  ```php
  $table->timestamp();
  ```
- **sql()** -This takes in a string parameter, the query for creating a table. You must not chain anything on to this method
  ```php
  $table->sql("CREATE TABLE IF NOT EXISTS Users ( status_id INT AUTO_INCREMENT PRIMARY KEY NOT NULL , status INT NOT NULL , created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP )");
  ```
- **foreignKey()** -Sql foreign key field. It take name the feild being reference, no need for table name since sparkes can figure it out.
  ```php
  $table->foreignKey('users_id');
  ```

##### Field constrains

- **notnull()** -Sql NOT NULL constrain. You can not do this on id() method..
  ```php
  $table->foreignKey('user_id')->notnull();
  $table->string('user_name')->notnull();
  $table->id('user_name')->notnull(); // dont do this!!
  ```
- **unique()** -Sql UNIQUE constrain.
  ```php
  $table->foreignKey('posts-id')->unique();
  $table->email('user_email')->unique()->notnull();
  ```
- **cascade()** -This is chained only on foriegn ky feilds and it is optional. Its sets ON DELETE and ON UPDATE constrian to CASCADE.
  ```php
  $table->foreignKey('post_id')->cascades();
  $table->foreignKey('user_id')->unique()->notnull()->cascade();
  ```
- **cascade()** -This is chained only on foriegn key feilds and it is optional. It sets ON DELETE and ON UPDATE constrian to CASCADE.
  ```php
  $table->foreignKey('post_id')->cascades();
  $table->foreignKey('user_id')->unique()->notnull()->cascade();
  ```
- **restrict()** -It sets ON DELETE and ON UPDATE constrian to RESTRICT.
  ```php
  $table->foreignKey('post_id')->restrict();
  ```
- **setnull()** -It sets ON DELETE and ON UPDATE constrian to SET NULL.
  ```php
  $table->foreignKey('post_id')->setnull();
  ```
- **noaction()** -It sets ON DELETE and ON UPDATE constrian to NO ACTION.
  ```php
    $table->foreignKey('post_id')->noaction();
  ```
  You can also set this costrain one by one as shown below
- **cascadeDelete()** -It sets ON DELETE CASCADE.
  ```php
  $table->foreignKey('post_id')->cascadeDelete();
  ```
- **cascadeUpdate()** -It sets ON UPDATE CASCADE.
  ```php
  $table->foreignKey('post_id')->cascadeUpdate();
  ```
- **restrictDelete()** -It sets ON DELETE RESTRICT.
  ```php
  $table->foreignKey('post_id')->restrictDelete();
  ```
- **restrictUpdate()** -It sets ON UPDATE RESTRICT.
  ```php
  $table->foreignKey('post_id')->restrictUpdate();
  ```
- **setnullDelete()** -It sets ON DELETE SET NULL.
  ```php
  $table->foreignKey('post_id')->setnullDelete();
  ```
- **setnullUpdate()** -It sets ON UPDATE SET NULL.
  ```php
  $table->foreignKey('post_id')->setnullUpdate();
  ```
- **noactionDelete()** -It sets ON DELETE NOACTION.
  ```php
  $table->foreignKey('post_id')->noactionDelete();
  ```
- **noactionUpdate()** -It sets ON UPDATE NOACTION.
  ```php
    $table->foreignKey('post_id')->noactionUpdate();
  ```
  Below is a very valid chain.

  ```php
   $table->foreignKey('post_id')->noactionUpdate()->setnullDelete();
  ```

## Querying the database.

This section will teach us how to create, read, update and delete record.

### Create new record.

Creating a new user into users table as shown below.

```php
// creating single user
$user = User::create(["user_name"=>"tom", "age"=>32]);

//creates multiple users at once
$user = User::create([
  ["user_name"=>"tom", "age"=>32],
  ["user_name"=>"mike", "age"=>25],
  ["user_name"=>"loy", "age"=>27],
]);
```
Don't pass the primary key feild, created_at and updated_at feild because they get feild up automatically.

The create method returns the new record created only if you are creating a single record.

### Updating record.

The static method update() and updateMany() are used, they takes in two paramters, the record you want to update (id or selector) the and associative array of the new values

```php
//updating the user with id 1
$user = User::update(1, ["user_name"=>"tom", "age"=>32]);

//updating the user where user_name = tom
$user = User::updateMany(["user_name"=>"tom"], ["user_name"=>"john", "age"=>32]);

//updating the user where user_name = tom and age = 32
$user = User::updateMany(["user_name"=>"tom", "age"=>32], ["user_name"=>"andrew", "age"=>15]);
```

The update method returns the new updated record.
The updateMany method returns void.

### Deleting record.

Static method delete will delete a record by it's id.

```php
//deleting the user where user_id = 1
$user = User::delete(1);
```

#### Deleting many records.

Static method deleteMany will delete all the record that match the where clouse passed as paramete.

```php
//deleting the user where user_name = tom
$user = User::deleteMany(["user_name"=>"tom"]);

//deleting the user where user_name = tom and age = 30
$user = User::deleteMany(["user_name"=>"tom", "age" => 30]);
```

#### NB:

**Part of the query that makes the where clause is passed as a parameter to the method**

#### Counting the number of records that matcht the query.

Static method countRecords() is used for Counting the number of records that match the query.

```php
//counting number the users where user_name = tom
$user = User::countRecords();

//updating the user where user_name = tom and age = 30
$user = User::countRecords(["user_name"=>"tom", "age" => 30]);
```

### Reading records from the database.

This can be done using many methods which are all discussed below.
All static methods without chaining functionality that are used for reading records takes in atleast two optional parameters, the where clause array/int and the feilds you want back.
The fields you want back can be passed as comma separated string value of the columns you want back **OR** as an array of all the feilds you want back. As shown below
 ```php
//get all the records from the table user
$user = User::all([], 'name, email');

////get all the records from the  user table where age >  30
$user = User::all(["age"=>[":gt"=>30]], ['name', 'email']);
```

- #### all().

  This is a static method that gets all the records that matches the query.

```php
//get all the records from the table user
$user = User::all();

////get all the records from the  user table where age >  30
$user = User::all(["age"=>[":gt"=>30]]);
```

- #### first().

  This is a static method that gets the first records that matches the query.

```php
//get all the records from the table user
$user = User::first();

//get the first records from the  user table where age >  30
$user = User::first(["age"=>[":gt"=>30]]);
```

- #### last().

  This is a static method that gets the last records that matches the query.

```php
//get the last records from the table user
$user = User::last();

//get the last records from the  user table where age >  30
$user = User::last(["age"=>[":gt"=>30]]);
```

- #### findById().

  This is a static method that gets the records by its primary id.

```php
//get the records from the table user where user_id = 2
$user = User::findById(2);
```

- #### findOne().

  This is a method that gets records that matches the query.

```php
//get the one and first record from the table user where user_id > 10
$user = User::findOne(["user_id"=>[":gt"=>10]]);
```

- #### exist().

  This is a method that checks if the record exists. It returns a true or false

```php
//checks if any user record exist
$user = User::exist();

//checks if user with user_id 2 exists
$user = User::exist(2);

//checks if the users with user_id greater than 10 exist
$user = User::exist(["user_id"=>[":gt"=>10]]);

//checks if email tom@gmail.com exist in user table
$user = User::exist(["email"=>"tom@gmail.com"]);
```

- #### sql().

  This is a static method inherited from Model class. It takes in two parameter, An sql query and the values you want to bind to the query.
  This can be usefull when writing a much complext query.

```php
$user = User::sql("SELECT * FROM users");
$user = User::sql("SELECT * FROM users", []);
$user = User::sql("SELECT * FROM users WHERE user_name = ? And age > ?", ['tom', 30]);
```
## join

There are several ways of joining two tables.
- #### joins() and innerJoins().
  This two methods do the same thing , they will inner join the two the tables.

```php
$user = User::joins(Post::class,);
//OR
$user = User::joins("Post");
//select * from user join post on user.user_id = post.user_id
```

There are methods for other type of joins.

- **leftJoins()** for left joins.
- **rightJoins()** for right joins.

 **So far we have looked at only static methods without chaining, Let us look at other chaining functionality available.**

These methods end with get() and where clause is either passed as parameter in array or integer form or chain where() method but not both

**::findByPk().**

For getting a single record by primary key, it works like the atatic method findById() record fro a table, it works like all()

```php
//select * from user where user_id = 10.
$user = User::findByPk(10)->get()
//select user_id, username from user where user_id = 10.
$user = User::findByPk(10)->select('user_id, username')->get()
```

**find()**.

For getting all the records from a table, it works like all()

```php
$users = User::find()->get();
//select * from user
$users = User::find()->get();
//select * from user where name = tom and age = 10.
$users = User::find()
          ->where(["name"=>"tom", "age"=>10])
          ->get();
//select * from user where age = tom and age = 10.
```

There are few more methods you can chain onto the method find() as described below.

##### orderBy().

Will sort the result in descending or asscending order . The values can be asc or desc for ascending and descending order respectively.

```php
$users = User::find()->orderBy("user_id, name ASC, created_at DESC")->get();
//select * from user order by user_id asc name asc created_at DESC.
$user =  User::find()->orderBy(['user_id'=> 'asc'])->get();
//select * from user order by user_id desc.

$user =  User::find()->orderBy(['user_id'=> 'asc', 'name'=>'desc'])->get();
//select * from user order by user_id desc age asc.

$user =  User::find()->orderBy('age asc')->get();
//select * from user where name = john orderby age asc.
```

##### groupBy()

This will group the result by the suplied feild. groupBy() takes in a string parameter. 

```php

$user = User::find()->groupBy('name')->get()
//select * from user group by name.

$user =  User::find()->groupBy('group_id')->get()
//select * from user group by group_id.

$user =  User::find()->groupBy('username')->get()
//select * from user group by username where name = john doe.
```

##### select().

This will select only the spacified feilds. The parameter is either string or arrays.

```php
$users =  User::find()->select(['name', 'age'])->get()
//select name, age from user.
//OR
$users = User::find()->select('name, age')->get()
//select name, age from user.

$users = User::find()
        ->select(['name as names_of_staffs', 'age',  "COUNT(name) * 2 as total"])
        ->get()
// 
$users = User::find()
        ->groupBy('age')
        ->get()
//SELECT name as names_of_staffs', 'age', "COUNT(name) as total from user group by age where name = john.
$users = User::find()
        ->where(['name'=>'john'])
        ->select('name, age')
        ->groupBy('name')
        ->orderBy(['user_id'=> 'desc', 'age'=>'asc'])
        ->get()
```

##### limit().

This is used to spacify the number of record you want to fetch. It defaults to 12

```php
$users = User::find()->limit(5)->get()
//select * from user limit 5.
```

##### skip() or offset().

Both of these do the same thing. They are used to spacify the number of records that will be skiped. It defaults to 0 

```php
$users = User::find()->skip(10)->get()
//OR
$users = User::find()->offset(10)->get()
```

A combination of skip and limit can be used for paginating your result

```php
$users = Post::find()
            ->skip(5)
            ->limit(10)
            ->select('title, post')
            ->get()
//select title, post from user limit 5,10
```

### pagination.

Pagination helps to query only a slice of records from the database. It has two methods;

- page() - Takes in the page you want
- perpage() - Takes number of records for each page.


```php
$users = User::find()
        ->page(3)
        ->perpage(5)
        ->where(['first_name'=>'tom','age'=>[':gt'=>18]])
        ->get()
```

The above query will return something like below

```php
[
  'num_records' => 15; //total number of records
  'num_pages' => 2; //total number of pages
  'has_next' => false; //if it has next page or not
  'current_page' => 2; //the current page showing
  'has_prev' => true; //if it has previous page or not
  'next_page' => null; //what next page is, null for no next page
  'prev_page' => 1; //what next page is, null for no next page
  'per_page' => 10; //number of records per page
  'position' => 1; //position of the first record of a page in the entire result
  'data' = [...]; // records for each page
]
```

### Using Model class to query data.
Pass the name of the table to the model constrctor if you want to query using the Model class
```php
  class DB extends Model{}
  $Post = DB::table('Post')->get()
  // OR
  $Posts = Model::table('Post')->get()
  
  // 
  $users = DB::table('user')->select('name')->get()
  // find
  $users = Model::table('user')
          ->limit(10)
          ->offset(5)
          ->select('name')
          ->get()

  // paginating
  $users = Model::table('post')
          ->page(10)
          ->perpage(5)
          ->select('title')
          ->get()
// OR
$users = DB::table('post')
          ->page(10)
          ->perpage(5)
          ->select('title')
          ->get()
```
You can chain any valid method like select, join, group, etc.

### Joining tables.
#### join().

Earlier we looked at joins but we were able to join only two tables using the static methods, now lets join more than two tabes.
This are the different types of joins which are avaiable;
**join(), innerJoin, leftJoin(), rightJoin()**.

```php
$users = User::find()
        ->join('post')
        ->join('comments')
        ->get()
```
Three tables will be involved in this join, user, post and comments.
You can join over 20 differnt tables together using any of the above types of join
and apply pagination, select, border by , etc like below

```php
$users = User::find()
        ->join('post')
        ->leftJoin('comments')
        ->page(1)
        ->perpage(10)
        ->orderBy('user.name'=>'desc')
        ->select(['user.name', 'post.title', 'count(*) As total'])
        ->where(["first_name" = "mike"])
        ->get()
```

- **find(), find() and findBypk can not be chained together, you will get an error**
- **find() method limits the result with page() and perpage() methods**
- **find() method limits the result with limit() and skip()/offset() methods**

### joinOn
This will require you to pass the name of the toble you are joining and the condition on which you are joining
``` php
$users = User::find()
        ->joinOn('post', 'user.user_id = post.user_id')
        ->leftJoinOn('comment','post.post_is = comment.coment_id') //optional 
        ->select('user.username, post.post')
        ->where(['user.first_name'=>'tom','user.age'=>[':gt'=>18]]) //optional
        ->get()
```
## Where clause.

The where clause is passed as a parameter in the following methods.

- ::all()
- ::findOne()
- ::findbyId()
- ::first()
- ::last()
- ::delete()
- ::deleteMany()
- ::countRecords()
- ::exist()
- ::findBypk()

you can also chain the where() method on the following methods
- ::find()
- ::table()
- ::find()
- ::findBypk()
- ::hasMany()

**It can be passed in the following ways**

##### 1. Passing an integer.

When you pass an integer to methods like **findById()** or **findBypk()** or **delete()**, the integer is primary id of the of the record you will get back

```php
   $result = User::findById(2)
   //OR
   $post = Post::findByPk(2)->get()
   //OR
   $post = Post::findByPk(2)->select('post, title, created_at AS time')->get()
```

The above code will return a single record whose primary id is 2.

##### 2. Passing an associative array.

When you pass an associative array to methods like **findOne()** or **all()**, the array generate the query as bellow

```php
   $result = User::all(["user_id"=>5])
   //sql = SELECT * FROM user WHERE user_id = 5
   //OR
   $result = User::all(["user_id"=>5, "name"=>"tom"])
   //sql = SELECT * FROM user WHERE user_id = 5 AND name = tom
   //OR
   $result = Posts::find(["user_id"=>5, "name"=>"tom"])->get()
   //sql = SELECT * FROM user WHERE user_id = 5 AND name = tom
```

##### 3. Passing nested associative array.

Sometimes you want to apply operators like <, >, <=, >=, =, like, etc. this is done the following ways

```php
   $result = User::all(["user_id"=>['$lt'=>5]])
   //sql = SELECT * FROM user WHERE user_id < 5
   //OR
   $result = User::all([
       "user_id"=>[':lt'=>5],
       "age"=>[':gt'=>30],
    ])
   //OR
   $result = User::all([
       "email"=>"tom@gmail.com"
       "user_id"=>[':lt'=>5],
       "age"=>[':gt'=>30],
    ])
   //sql = SELECT * FROM users WHERE email = tom@gmail.com AND user_id < 5 AND age > 30
```

Instead of using ':', you can use '$', for example, the output of this code is the same.

```php
   $result = User::all(["user_id"=>['$lt'=>5]])
   $result = User::all(["user_id"=>[':lt'=>5]])
   //Both will output
   //sql = SELECT * FROM user WHERE user_id < 5
```

##### 4. Passing associative array where key is $and or :and.

This will only write queries in which the where clause is separated by AND.

```php
   $result = User::all([
       ':and'=>[
           "user_id"=>['$lt'=>5],
           'age'=>[':gt'=>30],
           'phone'=>3 333 333 333
          ]
        ])
   //sql = SELECT * FROM user WHERE user_id < 5 AND age >30 AND phone = 3 333 333 333
```
##### 5. Passing associative array where key is $or or :or.

This will only write queries in which the where clause is separated by OR.

```php
  $result = User::all([
       ':or'=>[
          "user_id"=>['$lt'=>5],
          'age'=>[':gt'=>30],
          'phone'=>3 333 333 333
        ]
      ])
   //sql = SELECT * FROM user WHERE user_id < 5 OR age > 30 OR phone = 3 333 333 333
   
   $result = User::all([
       ':or'=>[
            ['name'=>'tom', 'email'=>'tom@gmai.com'],
           "user_id"=>['$lt'=>5],
           'age'=>[':gt'=>30],
           'phone'=>3 333 333 333
          ]
        ])
   /*
   sql = SELECT * FROM user 
    WHERE (name = tom AND email = tom@gmai.com) 
        OR user_id < 5 
        OR age > 30 
        OR phone = 3 333 333 333
   */
```

##### 6. Passing associative array where key is $nand or :nand.

This will negate the entire :and.

```php
   $result = User::all([
       ':nand'=>[
           "user_id"=>['$lt'=>5],
           'age'=>[':gt'=>30],
           'phone'=>3 333 333 333
           ]
        ])
    /*
   sql = SELECT * FROM user 
    WHERE NOT (
    user_id < 5 
    AND age > 30 
    AND phone = 3 333 333 333)
   */
  
   $result = User::all([
       ':nand'=>[
          "name" => "tom",
          "user_id"=>['$lt'=>5],
        ]
      ])
    /*
   sql = SELECT * FROM user 
    WHERE NOT (name = tom AND user_id < 5 )
   */
```

##### 7. Passing associative array where key is $nor or :nor.

This will negate the entire :nor.

```php
   $result = User::all([
       ':nor'=>[
           "user_id"=>['$lt'=>5],
           'age'=>[':gt'=>30],
           'phone'=>3 333 333 333
          ]
        ])
    /*
   sql = SELECT * FROM user 
    WHERE NOT (
        OR user_id < 5 
        OR age > 30 
        OR phone = 3 333 333 333)
   */
  
   $result = User::all([
       ':nor'=>[
          "name" => "tom",
          "user_id"=>['$lt'=>5],
        ]
      ])
    /*
   sql = SELECT * FROM user 
    WHERE NOT (name = tom OR user_id < 5 )
   */
  ```
The same way, putting n just after $ or : in the operator will negate that part of the query,
```php
     $result = Users::all(["name" => "tom","user_id"=>['$nlt'=>5]])
    /*
   sql = SELECT * FROM users
    WHERE name = tom AND NOT (user_id < 5 ))
   */
```
##### Passing the same column name more than one times.
If the same key is going to appear more than once, normally associative array will only pick up the key which is written last, to deal with this kind of behavior , you have to append a leading underscore (_) before the column name in the array key, this is demonstrated below.
```php
$result = Users::all(["name" => "joyce","_name"=>"tom"])
// sql = SELECT * FROM users WHERE name = tom AND name = tom ))
$result = Users::all([':or'=>[
                    "name" => "tom",
                    "_name"=>"daniel", 
                    "__name"=>"loy"]])
// sql = SELECT * FROM users WHERE name = tom OR name = deniel OR name = loy))

//Alternatively
$result = Users::find()
                ->where([':or'=>[
                    "name" => "tom",
                    "_name"=>"daniel", 
                    "__name"=>"loy"]])
                    ->get()
// sql = SELECT * FROM users WHERE name = tom OR name = deniel OR name = loy))

```
You will have to append many underscores if the column name is repeating many times in that same associative array.

### List of available operators for the where clause.

| Operators   | Sparcles Symbol | Example 
| --------    | -------| -----------------|
|   =   |   $eq  **or** :=     | ['name'=>[':eq' => 'tom']] OR                     ['name'=>[':=' => 'tom']]
| <    |     $< **or** :lt| ['age'=>[':lt' => 18]]
| >    | $> **or** :gt| ['age'=>[':gt' => 18]]
| >=    | $>= **or** :gte| ['price'=>[':gte' => 1000]]
| <=    | $<= **or** :lte| ['price'=>[':eq' => 50]]
| Like    | $like         | ['name'=>[':like'=>'%micheal']] or ['name'=>[':like'=>'%cheal%']]
| Regexp    | :regexp         | ['name'=>[':regexp'=>'^mich']] or ['name'=>[':regexp'=>'cheal$']]
| In    | :in         | ['name'=>[':in' => ['tom','mike', 'joy']]]
| Between    | :btn or :between         | ['age'=>[':btn' => [20,30]]]
| Null    | null         | ['age'=>'null']
| Not Null    | not null | ['age'=>'not null']
| Not <    |     $n< **or** :nlt| ['age'=>[':nlt' => 18]]
| Not >    | $n> **or** :ngt| ['age'=>[':ngt' => 18]]
| Not >=    | $n>= **or** :ngte| ['price'=>[':ngte' => 1000]]
| Not <=    | $n<= **or** :nlte| ['price'=>[':neq' => 50]]
| Not Like    | :nlike         | ['name'=>[':nlike'=>'%micheal']] or ['name'=>[':nlike'=>'%cheal%']]
| Not In    | :nin         | ['name'=>[':nin' => ['tom','mike', 'joy']]]
| Not Between    | :nbtn or :nbetween         | ['age'=>[':nbtn' => [20,30]]]
| And    | $and        | [':and'=>[ 'age'=>10, 'name' =>tom ]]
| Not And    | $nand        | [':nand'=>[ 'age'=>10, 'name' =>tom ]]
| Or    | $or        | [':or'=>[ 'age'=>10, '_age' =>20 ]]
| Not or    | $nor        | [':nor'=>[ 'age'=>10, '_age' =>20 ]]
| Not Regexp    | $nregexp         | ['name'=>[':nregexp'=>'^mich']] or ['name'=>[':nregexp'=>'cheal$']]

## Relationships.

This will establish connection between some tables, for example if you have a post, you can easily get its comments, or if you have a user you can get all his posts.
There are three types of relationships you can use here,
- One To One relationship.
- One To Many relationship.
- Many To Many relationship.

#### 1. One To One relationship.

One user has one credit card and a credit card belongs to one user.
To establish a **One To One relationship** here , you have to create one function in the Users class and CreditCards calss defination as shonw below.

```php
 $card = User::findByPk(4)
        ->hasOne(CreditCard::class)
        ->get()
// returns one creditcard or false

//and also
 $card = CreditCards::findByPk(4)
        ->belongsToOne(User::class)
        ->get()
// returns one user or false
```

#### 2. One To Many relationship.

The code will be as below.
```php
$card = User::findByPk(4)
        ->hasMany(Post::class)
        ->get()
// returns array of post records

// and also
$Post = Post::findByPk(4)
        ->belongsToOne(User::class)
        ->get()
// returns a user or false
```
#### 3. Many To Many relationship.

This will only work if you had created an intermediate table for the two tables.
The intemediate table has a convention of creating it inorder for **Dite** to understand. You have to concatenate the two table names.
For example **teachers** and **courses** tables, the intermediate table will be **teachers_courses** and the primary key feild will be **teachers_courses_id** .The intermediate tabble must be created like below.
```php
//Teachers table
class Teachers extends Model{

  public function __construct() {
    Schema::create(Teachers::class, function(Table $table){
      $table->id();
      $table->string('teacher_name');
      $table->string('email');
    });
  }
}
//Courses table
class Courses extends Model{

public function __construct() {
    Schema::create(Courses::class, function(Table $table){
      $table->id();
      $table->string('course_name');
    });
  }
}

//Intermediate table
class Teachers_Courses extends Model{

  public function __construct() {
    Schema::create(Teachers_Courses::class, function(Table $table){
      $table->id();
      $table->foriegnKey('courses_id');
      $table->foriegnKey('teachers_id');
    });
  }
}
```
Each time you create a teacher or a course remember to update the intermediate table. 

**Let's** define the relationship.

```php
 $card = Teacher::findByPk(4)
        ->hasManyMany(Courses::class)
        ->get()
// returns one user or false
```
***NB*** On to any relationship, you can chain any valid method chain exept ->findByPk()
```php
$post = Users::findByPk(2)
        ->hasMany(Post::class)
        ->select('post_id,title, post')
        ->orderBy()
        ->where(['title'=>['$like'=>'%computer']])
        ->limit(10)
        ->get();
```

#### Dropping database table

This is done using the static method drop() like below. It returns a boolean , true for successful deleting and false for failure to delete the table.
```php
Users::drop();
```
### Thanks from **Dite**  