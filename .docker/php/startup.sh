#!/bin/bash

composer i -o
php bin/console lexik:jwt:generate-keypair --overwrite
chmod 644 config/jwt/private.pem
php bin/console messenger:consume async -vv >> /var/log/messenger.log 2>&1 &
php-fpm