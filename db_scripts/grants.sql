-- RUN AS app_owner USER
GRANT USAGE ON SCHEMA app_owner TO app_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA app_owner TO app_user;
--GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA app_owner TO app_user;


-- RUN AS app_owner_test USER
GRANT USAGE ON SCHEMA app_owner_test TO app_user_test;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA app_owner_test TO app_user_test;
--GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA app_owner TO app_user;
