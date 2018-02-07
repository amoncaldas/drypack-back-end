#!/bin/bash

# Running the Laravel tests
# Clearing the database and running the seed
./vendor/bin/phpunit --verbose --colors=always

echo
cat testresults/log.txt
