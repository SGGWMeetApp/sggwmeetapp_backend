# Project configuration - Linux

## Using Docker and docker-compose
If you are using a Linux distribution such as Ubuntu, xUbuntu, Debian, etc. the configuration is pretty straightforward.

In order not to install and configure everything by yourself this repository provides a `docker-compose.yml` file which
is located in `.docker` directory which resides in project root directory.

Before starting the containers go to `<<project_root>>/.docker`, open the `.env` file and set the `REPO_DIR` variable to the local project
location, for example:
```
REPO_DIR=/home/username/app_dir
```

To get the project up and running execute the following commands in the terminal (in project root directory):
```
cd .docker
docker-compose up
```
Please note, that you need to have both Docker and docker-compose installed for this to work. You can download the latest
versions from the Internet. This project was tested with Docker version `20.10.21` and docker-compose version `2.14.0`.
Versions prior to these may not work.

## Development
When using docker containers the code you write will automatically be synced with the one residing in containers. If
something seems to not sync or be cached it might be a good idea to exec into the php container and clear the cache using:
```
php bin/console cache:clear
```

## Common pitfalls
### RabbitMQ health check
One of the common pitfalls we encountered is the health check of the RabbitMQ container. On machines that do not possess
adequate amounts of RAM and/or CPU the health check might not complete in time and would fail constantly when trying to
start the containers.

In order to solve this open `.docker/docker-compose.yml` file and try increasing the `interval` of the health check to say
30 seconds. If that is still not enough time then you may also try to increase the `retries` to a higher number. Example
configuration:
```
rabbitmq:
        build:
            context: ./rabbitmq
        volumes:
            - rabbitmq:/var/lib/rabbitmq:cached
        networks:
            - symfony
        healthcheck:
            test: rabbitmq-diagnostics -q ping
            interval: 30s
            timeout: 10s
            retries: 10
```