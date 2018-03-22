# Resources about using html5Mode API #

These Resources are intended to help the understanding the usage and support of html5Mode.

[The html5Mode during the time](http://diveintohtml5.info/history.html)
[The html5Mode browser's support](https://caniuse.com/#search=history)

# How to use in AngularJS #

Inside the angular config function, using ui.router and injecting $locationProvider, do:

```js
$locationProvider.html5Mode({
  enabled: false, // set to true if you want history/html5Mode for the url, removing the '#'
  requireBase: false
}).hashPrefix('!');

// if you set enable: true and requireBase: true, it is necessary to put <base href="/"> on the index file, after head
```

To work properly, the html5Mode requires that all the request return the index.html.
In the [routes/web.php](routes/web.php) file, add, as last route.
So, if the user type /user/details or /project/10, the back-end will return the index.html file
and the app routing will take place on the front-end.

Example:

```php
// all the requests to the client will fall down to /client/index.html
Route::get('{all}', function () {
    return File::get(public_path().'/client/index.html');
})->where('all', '^(?!api).*$');
```

