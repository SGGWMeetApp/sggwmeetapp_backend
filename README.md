# sggwmeetapp_backend
This repository contains backend logic, REST API and database design for SGGWMeetApp - an app designed by the students of
the IT master's programme (2022-2023) at Warsaw University of Life Sciences (WULS). This is an open-source project that we
hope will be further developed by future generations of students. 

## Environment configuration
The environment variables for this project can be found in the `.env` file.

**DO NOT** enter any *production secrets* inside this file as it is committed to GitHub. Only include dummy values when
adding new environment variables.

To create a local environment please create a `.env.local` file which will
override the configuration given in `.env`. After that copy the contents of `.env` to `.env.local` and change the values
of all variables per your requirements. Note that this file won't be committed to GitHub repository.

The `.env` file contains comments on the variables it defines. Use those to figure out what each and every variable
does. Some variables also have a comment indicating the composer package/other service they are related to. 

## Project configuration - Linux
Please refer to [Linux configuration docs](docs/LINUX_CONFIGURATION.md) to configure the project in Linux.

## Project configuration - Windows (not recommended)
Please refer to [Windows configuration docs](docs/WINDOWS_CONFIGURATION.md) to configure the project in Windows.

## Amazon S3
Please refer to [S3 configuration docs](docs/S3_CONFIGURATION.md) to configure Amazon S3 service for this project.

## Database
To create database run files
1) `db_scripts/init.sql` as root user
2) `db_scripts/schema.sql` as schema owner user
3) `db_scripts/grants.sql` as schema owner user

As a result database should have 3 users:
- root
- app_owner (schema owner)
- app_user (user for application and devs, have CRUD access to all tables in schema)