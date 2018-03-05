#!/bin/bash

# Clearing the test database and running the seed
APP_ENV=testing php artisan migrate:reset --quiet
APP_ENV=testing php artisan migrate --seed --quiet

# Starting the server in test mode
( APP_ENV=testing php artisan serve --quiet -n --port=5000 --host=0.0.0.0 --no-ansi & ) > /dev/null 2>&1

# Running the tests in the front-end
protractor public/admin/tests/conf.js $1

# Kill the server process
lsof -t -i tcp:5000 | xargs kill -9

# Remove temp files
rm -rf .org.chromium.Chromium.*
