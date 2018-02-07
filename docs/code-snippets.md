# Useful code snippets #

## Sometimes you need to delete al the tables ##

```php

// Delete all tables from a postgres db
function emptyDatabase() {
  $tables = \DB::select("select * from information_schema.tables WHERE table_schema='public'");

  foreach($tables as $table) {
    $droplist[] = $table->table_name;
  }
  $droplist = implode(',', $droplist);

  \DB::beginTransaction();
  \DB::statement("DROP TABLE $droplist");
  \DB::commit();
}
```

## Transform a nested hierarchy string representation into a nested navigable object ##

```php

// Transform an string like "school.student.name" into $school->student->name
function nestedStringIntoObject($nested_string) {
  if(strpos($nested_string, '.') !== false) {
    $parts = explode(".", $nested_string);
    $json_str = "";
    $amount = count($parts);
    $closes = "";

    for ($i=0; $i < $amount; $i++) {
      $part = $parts[$i];
      if($i == ($amount-1)) {
        $json_str .= '"'.$part.'"';
      } else {
        $json_str .= "{".'"'.$part.'":';
        $closes .= "}";
      }
    }
    $json_str .= $closes;
    $object = json_decode($json_str, true);
  }

  return $object;
}
```
Get SQL query log:

```php

\DB::enableQueryLog();

// EXECUTE YOUR CALL TO DB HERE

$logs = \DB::getQueryLog();

```
## simulate the install request on a server ##

```php
// on routes/web.php, route to simulate the install request on a server
Route::get('/package/install.php', function () {
    ob_start();
    $path = base_path("package/install.php");
    require($path);
    return ob_get_clean();
});
```

