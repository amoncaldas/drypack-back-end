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

## Get SQL query log ##

```php

\DB::enableQueryLog();

// EXECUTE YOUR CALL TO DB HERE

$logs = \DB::getQueryLog();

```
## Process directly a php file ##

```php
// on routes/web.php, define it
Route::get('/folder/file.php', function () {
    ob_start();
    $path = base_path("folder/file.php");
    require($path);
    return ob_get_clean();
});
```

## Add custom validation message ##

```php
'custom' => [
    'field_name' => [
        'required' => 'This message will be shown if this input key is empty',
    ]
],
```

## Check/remove HTML in js ##

```js
var isHTML = RegExp.prototype.test.bind(/(<([^>]+)>)/i);
if(isHTML('<p>a</p>')) {/*...*/}
var withHtml = '<p>a</p>';
var withoutHtml = withHtml ? String(withHtml).replace(/<[^>]+>/gm, '') : '';
```

## Get JS File from base64 ##

```js
function getJSFileInstance(file) {
  var byteString = file.thumb;

  // write the bytes of the string to a typed array
  var ia = new Uint8Array(byteString.length);

  for (var i = 0; i < byteString.length; i++) {
    ia[i] = byteString.charCodeAt(i);
  }

  var theFile = new File(
    [ia],
    file.file_name,
    {
      type: file.mimetype
    }
  );

  return theFile;
}
```

# Route api closure #
```php
Route::get('my-content/endpoint/{id}', function(Request $request, $id) {
  $klass = "\App\Http\Controllers\Content\"DesiredController";
  $controller = new $klass();
  return $controller->showContent($request, $id);
});
```


          





