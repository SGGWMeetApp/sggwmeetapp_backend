# sggwmeetapp_backend
Backend logic, API and database design for SGGWMeetApp

## Database
To create database run files
1) `db_scripts/init.sql` as root user
2) `db_scripts/schema.sql` as schema owner user
3) `db_scripts/grants.sql` as schema owner user

As a result database should have 3 users:
- root
- app_owner (schema owner)
- app_user (user for application and devs, have CRUD access to all tables in schema)