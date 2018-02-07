# Useful commands #

Go to the Docker container bash:

```sh
docker exec -it --user root drypack-server bash
```
Run application back-end server
```sh
npm run server
```
Build package
```sh
npm run package
```

Run front-end build
```sh
npm run build
```
Run front-end code check
```sh
npm run check
```

Run webdriver service to be able to run e2e test
```sh
npm run webdriver
```

Run e2e tests
```sh
npm run e2e-test
```

Run back-end php tests
```sh
npm run php-test
```

Refresh actions and resource in database from config file:

*Obs.: internally ActionsSeeder calls the UsersAndRolesSeeder*

```sh
php artisan db:seed --class=ActionsSeeder
```

Redefine the default user actions and roles:
```sh
php artisan db:seed --class=UsersAndRolesSeeder
```



