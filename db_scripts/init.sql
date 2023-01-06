CREATE DATABASE meetappdb;
ALTER DATABASE meetappdb SET timezone TO 'UTC';

CREATE ROLE app_owner WITH LOGIN ENCRYPTED PASSWORD 'password';
GRANT CONNECT ON DATABASE meetappdb TO app_owner;
GRANT CREATE ON DATABASE meetappdb TO app_owner;

CREATE ROLE app_user WITH LOGIN ENCRYPTED PASSWORD 'password';
GRANT CONNECT ON DATABASE meetappdb TO app_user;

-- RUN AS app_owner USER
CREATE SCHEMA IF NOT EXISTS app_owner AUTHORIZATION app_owner;

-- AFTER SCHEMA IS CREATED, RUN AS ROOT USER
ALTER DATABASE meetappdb SET search_path TO app_owner;


CREATE ROLE app_owner_test WITH LOGIN ENCRYPTED PASSWORD 'password';
GRANT CONNECT ON DATABASE meetappdb TO app_owner_test;
GRANT CREATE ON DATABASE meetappdb TO app_owner_test;

CREATE ROLE app_user_test WITH LOGIN ENCRYPTED PASSWORD 'password';
GRANT CONNECT ON DATABASE meetappdb TO app_user_test;

-- RUN AS app_owner_test USER
CREATE SCHEMA IF NOT EXISTS app_owner_test AUTHORIZATION app_owner_test;
SET search_path TO app_owner_test;
