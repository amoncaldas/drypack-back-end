## How to use GenericService API ##

GenericService trait is intended to be used to get query data without the need of creating several controllers and implementing query filters manually only to get data. So, it is a fast way to provide services that will be used to query data an populate components. In the other hand, it can be used to apply filters in CRUD controllers get/index methods in an automatic way, avoiding iterating and add each filter manually.

### Using as a way to provide domain data ###

Create some models:

```php

// Category model class
namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends BaseModel
{
    protected $table = 'categories';

    protected $fillable = [
        'slug',
        'name',
        'description',
    ];   
}

// Car model class
namespace App;

use Illuminate\Database\Eloquent\Model;

class CarType extends BaseModel
{
    protected $table = 'car_types';

    protected $fillable = [
        'brand',
        'model',
        'seats',
    ];   
}
```

Create a controller, inject the GenericService and create 2 lines methods:

```php

namespace App\Http\Controllers;

use App\Http\Traits\GenericService;

class DomainDataController extends Controller
{
  use GenericService;        
  
  /**
  * Gets the categories
  *
  * @param request inject the request data
  **/
  public function categories(Request $request)
  {
    $query = \App\Category::query();
    return $this->getResults($request, $query);
  } 

  /**
  * Gets the car types
  *
  * @param request inject the request data
  **/
  public function carType(Request $request)
  {
    $query = \App\CarType::query();
    return $this->getResults($request, $query);
  } 
}
```

Create a generic entry in [routes/api.php](routes/api.php) pointing to the Generic Service method:
```php
Route::get('/domain-data/{methodName}', 'DomainDataController@mapAndGet');
```

The route above will map all the requests to **/domain-data/{method-name}** to **GenericService@mapAndGet** method that will discover the method according the following pattern:

 * '/domain-data/categories' => will fire DomainDataController@categories
 * '/domain-data/car-type' => will fire DomainDataController@CarType

 Having this code, it is possible to make GET requests with query filters, like using three patterns:

```javascript
 ?filters=[{"prop":"slug","op":"=","value":"new"}, {"prop":"description","op":"like","value":"brand new"}];
 ?prop=name&op==&value=New
 ?slug=new
 ```

 Example: **/domain-data/categories?prop=name&op==&value=New**

 The operators supported are:
 - '='
 - '>='
 - '<='
 - 'like'
 - 'ilike'
 - 'startswith'
 - 'endswith'
 - '<>'

### Using as a way to apply query filters ###

 It is also possible to use the GenericService in a controller that extends from CRUDController

 ```php
namespace App\Http\Controllers\Samples;
use Illuminate\Http\Request;
use App\Task;
use App\Http\Requests;
use App\Http\Controllers\CrudController;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\GenericService;

class TasksController extends CrudController
{
  use GenericService;

  public function __construct()
  {
      /**
       * Defines the attributes that GenericService must not automatically add as filter to the query
       *
       * @var array
        */
      $this->gsSkipFilters = ['dateStart', 'dateEnd'];

      /**
       * Defines the default attributes operator that will be used by GenericService when gsApplyFilters is called
       *
       * @var array
        */
      $this->gsAttributesDefaultOperator = ['description'=>'like'];
  }

  protected function getModel()
  {
      return Task::class;
  }

  protected function applyFilters(Request $request, $query) {

      // Uses GenericService to add all filters based in the from request data,
      // skipping $this->gsSkipFilters (dateStart and dateEnd) to be treated manually
      // and automatically adds the $query filters to project_id, description, 'done', 'priority'
      // This avoid having to add each filter to the query manually
      $this->gsApplyFilters($request, $query);

      // Filters that has special wheres, so we have to add them manually
      if ($request->has('dateStart')) {
          $query = $query->where('scheduled_to', '>=', \DryPack::parseDate($request->dateStart));
      }

      if ($request->has('dateEnd')) {
          $query = $query->where('scheduled_to', '<=', \DryPack::parseDate($request->dateEnd));
      }
  }
}
 ```