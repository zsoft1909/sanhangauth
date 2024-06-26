#!/bin/sh
set -eu
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p storage/app/{media,resized,uploads}
mkdir -p storage/cms/{cache,combiner,twig}
mkdir -p storage/temp/public
chmod 777 /var/www/html/storage -R
# aws-env --format=dotenv > .env
# php artisan october:up
