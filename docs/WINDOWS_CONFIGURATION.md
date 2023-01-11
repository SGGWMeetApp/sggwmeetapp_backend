# Windows configuration (not recommended)
Note: this configuration is not recommended with primary reason being that it is not that easy to set up, as
it is in Linux. If you are comfortable using WSL2 and Docker Desktop in Windows you may still find it working, but
we cannot guarantee the project is going to run without any errors.

It is also possible to run **most** of this project on Windows. Things that you will need to install at least the following
components to get the project working:
- php (version>=8.1.11)
- php extensions: `curl`, `fileinfo`, `intl`, `mbstring`, `mysqli`, `openssl`, `pdo_mysql`, `pdo_pgsql`, `pgsql`
- composer (latest version is recommended)
- symfony binary (you can download it [here](https://symfony.com/download))

After installing these you will need to install the composer packages. To do that go to project root directory and execute:
```
composer install
```

You might experience errors concerning some of the environment variables. Please refer to `.env` file for clues how to
resolve those errors.

## Production environment (prod) on Windows
This project is using RabbitMQ as an AMQP transport in production environment. If you want to use the production
environment on Windows then you will need to install and configure it by yourself. If you don't need to use production
environment on Windows then you will find the `dev` environment working properly, as it is using a `sync` transport.

## OpenSSL and bin/console scripts issues
There are known issues with OpenSSL and `bin/console` scripts on Windows which might prevent you from generating the
public and private keys for packages that are used in this project. You can generate the keys using a linux distribution
and paste them on Windows as a work-around.