#!/bin/bash

composer i -o
composer update
php bin/console lexik:jwt:generate-keypair --overwrite
chmod 644 config/jwt/private.pem
php bin/console messenger:stop-workers
service supervisor start
supervisorctl update
supervisorctl start messenger-consume:*
php-fpm