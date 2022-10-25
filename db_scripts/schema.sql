DROP TABLE IF EXISTS event_notifications;

DROP TABLE IF EXISTS events;

DROP TABLE IF EXISTS location_categories;

DROP TABLE IF EXISTS location_ratings;

DROP TABLE IF EXISTS locations;

DROP TABLE IF EXISTS locations_location_categories;

DROP TABLE IF EXISTS user_groups;

DROP TABLE IF EXISTS users;

DROP TABLE IF EXISTS users_user_groups;

CREATE TABLE users (
    user_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    username varchar(255) NOT NULL,
    first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    location_sharing_mode integer NOT NULL DEFAULT 0,
    UNIQUE (username)
);

CREATE TABLE user_groups (
    group_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name varchar(255) NOT NULL,
    owner_id integer NOT NULL,
    FOREIGN KEY (owner_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE users_user_groups (
    user_id integer NOT NULL,
    group_id integer NOT NULL,
    PRIMARY KEY (user_id, group_id),
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (group_id) REFERENCES user_groups (group_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX group_id_inx ON users_user_groups (group_id);

CREATE TABLE location_categories (
    category_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name varchar(255) NOT NULL,
    UNIQUE (name)
);

CREATE TABLE locations (
    location_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    category_id integer NOT NULL,
    name text NOT NULL,
    description text NOT NULL,
    lat numeric(9, 6) NOT NULL,
    long numeric(9, 6) NOT NULL,
    menu text NULL,
    rating_pct numeric(4, 2) NULL
);

CREATE TABLE locations_location_categories (
    location_id integer NOT NULL,
    category_id integer NOT NULL,
    PRIMARY KEY (category_id, location_id),
    FOREIGN KEY (category_id) REFERENCES location_categories (category_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations (location_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE events (
    event_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    group_id integer NOT NULL,
    location_id integer NOT NULL,
    start_date timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    name varchar(255) NOT NULL,
    description text NULL,
    is_public boolean NOT NULL DEFAULT FALSE,
    owner_id integer NOT NULL,
    FOREIGN KEY (location_id) REFERENCES locations (location_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (group_id) REFERENCES user_groups (group_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE event_notifications (
    notification_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    event_id integer NOT NULL,
    message_title varchar(255) NOT NULL,
    message text NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events (event_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE location_ratings (
    location_id integer NOT NULL,
    user_id integer NOT NULL,
    is_positive boolean DEFAULT TRUE,
    comment text NULL,
    up_votes integer NOT NULL DEFAULT 0,
    down_votes integer NOT NULL DEFAULT 0,
    description text NOT NULL,
    publication_date timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations (location_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE OR REPLACE FUNCTION insert_location_rating ()
    RETURNS TRIGGER
    AS $$
DECLARE
    lc_ratings_number integer;
    lc_is_positive integer;
BEGIN
    SELECT
        COUNT(location_id),
        SUM(is_positive::int) INTO lc_ratings_number,
        lc_is_positive
    FROM
        location_ratings
    WHERE
        location_id = NEW.location_id;
    UPDATE
        locations
    SET
        ratings_number = lc_ratings_number,
        rating_pct = (lc_is_positive / lc_ratings_number) * 100
    WHERE
        location_id = NEW.location_id;
    RETURN new;
END;
$$
LANGUAGE plpgsql;

CREATE TRIGGER insert_location_rating_tgr
    AFTER INSERT OR UPDATE OF is_positive ON location_ratings
    FOR EACH ROW
    EXECUTE PROCEDURE insert_location_rating ();

CREATE OR REPLACE FUNCTION delete_location_rating ()
    RETURNS TRIGGER
    AS $$
DECLARE
    lc_ratings_number integer;
    lc_is_positive integer;
BEGIN
    SELECT
        COUNT(location_id),
        SUM(is_positive::int) INTO lc_ratings_number,
        lc_is_positive
    FROM
        location_ratings
    WHERE
        location_id = OLD.location_id;
    UPDATE
        locations
    SET
        ratings_number = lc_ratings_number,
        rating_pct = (lc_is_positive / lc_ratings_number) * 100
    WHERE
        location_id = OLD.location_id;
    RETURN old;
END;
$$
LANGUAGE plpgsql;

CREATE TRIGGER delete_location_rating_tgr
    AFTER DELETE ON location_ratings
    FOR EACH ROW
    EXECUTE PROCEDURE delete_location_rating ();