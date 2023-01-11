# Project configuration - Linux

## Using Docker and docker-compose
If you are using a Linux distribution such as Ubuntu, xUbuntu, Debian, etc. the configuration is pretty straightforward.

In order not to install and configure everything by yourself this repository provides a `docker-compose.yml` file which
is located in `.docker` directory which resides in project root directory. To get the project up and running execute the
following commands in the terminal (in project root directory):
```
cd .docker
docker-compose up
```
Please note, that you need to have both Docker and docker-compose installed for this to work. You can download the latest
versions from the Internet. This project was tested with Docker version `20.10.21` and docker-compose version `2.14.0`.
Versions prior to these may not work.

## Configuring for development
When using Linux in development it is highly recommended to use the `docker-sync` extension. It will synchronize the files
between the linux file system and the container's file system as you write code. You can find the relevant documentation
on how to install `docker-sync` [here](https://docker-sync.readthedocs.io/en/latest/getting-started/installation.html).

Note: you might need to install ruby and its gems if they are not already installed.

After the installation execute these commands starting in the project root directory:
```
cd .docker
docker-sync-stack start
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