# Base-Model
CodeIgniter Base Model
An extension of CodeIgniter's base Model class for easy CRUD operation.

# Example
This model can be used to define relationship between models.

```php
class User_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
        $this->table = 'i_users';   // Table name
        $this->primary_key = 'user_id';  // primary key
        $this->auto_fill();  // This function get all table columns and prepare for CRUD operation.
    }
    
    /*
    | -------------------------------------------------------------------------
    | RELATIONSHIP
    | -------------------------------------------------------------------------
    */    
    
    function blogs($paginate = NULL)    
    {
        if($paginate !== NULL) {
            $blogs = $this->blog_model->where('user_id', $this->get_id())->paginate((int)$paginate);        
        } else {
            $blogs = $this->blog_model->where('user_id', $this->get_id())->fetch();    
        }
        return $blogs;
    }
    
}
```

```php
class Blog_model extends MY_Model
{
    function __construct()
    {
        parent::__construct(); // Table name
        $this->table = 'i_blogs';  // primary key
        $this->auto_fill(); // This function get all table columns and prepare for CRUD operation.
    }

    /*
    | -------------------------------------------------------------------------
    | RELATIONSHIP
    | -------------------------------------------------------------------------
    */
    
    function user()
    {
        return $this->user_model->find($this->get('user_id'));
    }
     
}
```

```php
$users = $this->user_model->where('published', '1')->fetch();
// This returns user collection with where filter.

foreach($users as $user) {
  echo $user->get('id');  // returns value of 'id' column
  echo $user->blogs();  // returns blog collection with respect to specific user.
  echo $user->blogs(5);  // returns blog collection with respect to specific user with pagination.
}

```

```php
$blogs = $this->blog_model->paginate(10);
// This returns blog collection with pagination.

foreach($blogs as $blog) {
  echo $blog->get('name');  // returns value of 'name' column
  echo $blog->user()->get('id');  // returns 'id' of user assigned to blog.
}

```


More examples are coming Soon..
