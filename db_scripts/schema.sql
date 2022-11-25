-- RUN AS app_owner USER
DROP TABLE IF EXISTS rating_reviews CASCADE;

DROP TABLE IF EXISTS event_notifications CASCADE;

DROP TABLE IF EXISTS events CASCADE;

DROP TABLE IF EXISTS locations_location_categories CASCADE;

DROP TABLE IF EXISTS location_categories CASCADE;

DROP TABLE IF EXISTS locations CASCADE;

DROP TABLE IF EXISTS location_ratings CASCADE;

DROP TABLE IF EXISTS users_user_groups CASCADE;

DROP TABLE IF EXISTS user_groups CASCADE;

DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
    user_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    username varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    password text NOT NULL,
    first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    phone_number_prefix char(4) NOT NULL,
    phone_number varchar(15) NOT NULL,
    location_sharing_mode integer NOT NULL DEFAULT 0,
    description text NULL,
    UNIQUE (phone_number_prefix, phone_number)
);

CREATE UNIQUE INDEX username_inx ON users (lower(username));

CREATE UNIQUE INDEX email_inx ON users (lower(username));

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
    name varchar(255) NOT NULL
);

CREATE UNIQUE INDEX location_category_name_inx ON location_categories (lower(name));

CREATE TABLE locations (
    location_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name text NOT NULL,
    description text NOT NULL,
    lat numeric(9, 6) NOT NULL,
    long numeric(9, 6) NOT NULL,
    menu text NULL,
    ratings_number integer NOT NULL DEFAULT 0,
    rating_pct numeric(5, 2) NULL,
    text_location text NOT NULL
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
    group_id integer NULL,
    location_id integer NOT NULL,
    start_date timestamp NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    is_public boolean NOT NULL DEFAULT FALSE,
    owner_id integer NOT NULL,
    can_edit boolean NOT NULL DEFAULT FALSE,
    creation_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notification_enabled boolean NOT NULL DEFAULT FALSE,
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
    rating_id integer NOT NULL PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
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

CREATE UNIQUE INDEX rating_unq_inx ON location_ratings (user_id, location_id);

CREATE INDEX location_inx ON location_ratings (location_id);

CREATE TABLE rating_reviews (
    rating_id integer NOT NULL,
    user_id integer NOT NULL,
    is_up_vote boolean NOT NULL DEFAULT TRUE,
    creation_date timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (rating_id, user_id),
    FOREIGN KEY (rating_id) REFERENCES location_ratings (rating_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE OR REPLACE FUNCTION insert_location_rating ()
    RETURNS TRIGGER
    AS $$
BEGIN
    WITH ratings AS (
        SELECT
            COALESCE(SUM(is_positive::int), 0)::numeric(5, 2) AS positives,
            COUNT(rating_id)::numeric(5, 2) AS ratings_num
        FROM
            location_ratings
        WHERE
            location_id = NEW.location_id)
    UPDATE
        locations
    SET
        ratings_number = ratings.positives,
        rating_pct = CASE WHEN (ratings.ratings_num = 0) THEN
            NULL
        ELSE
            ROUND((ratings.positives / ratings.ratings_num) * 100, 2)
        END
    FROM
        ratings
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
BEGIN
    WITH ratings AS (
        SELECT
            COALESCE(SUM(is_positive::int), 0)::numeric(5, 2) AS positives,
            COUNT(rating_id)::numeric(5, 2) AS ratings_num
        FROM
            location_ratings
        WHERE
            location_id = OLD.location_id)
    UPDATE
        locations
    SET
        ratings_number = ratings.positives,
        rating_pct = CASE WHEN (ratings.ratings_num = 0) THEN
            NULL
        ELSE
            ROUND((ratings.positives / ratings.ratings_num) * 100, 2)
        END
    FROM
        ratings
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

CREATE OR REPLACE FUNCTION insert_rating_review ()
    RETURNS TRIGGER
    AS $$
BEGIN
    WITH ratings_ratio AS (
        SELECT
            COALESCE(SUM(is_up_vote::int), 0) AS up_votes,
            COUNT(is_up_vote) AS votes
        FROM
            rating_reviews
        WHERE
            rating_id = NEW.rating_id)
    UPDATE
        location_ratings
    SET
        up_votes = ratings_ratio.up_votes,
        down_votes = ratings_ratio.votes - ratings_ratio.up_votes
    FROM
        ratings_ratio
    WHERE
        rating_id = NEW.rating_id;
    RETURN new;
END;
$$
LANGUAGE plpgsql;

CREATE TRIGGER insert_rating_review_tgr
    AFTER INSERT OR UPDATE OF is_up_vote ON rating_reviews
    FOR EACH ROW
    EXECUTE PROCEDURE insert_rating_review ();

CREATE OR REPLACE FUNCTION delete_rating_review ()
    RETURNS TRIGGER
    AS $$
BEGIN
    WITH ratings_ratio AS (
        SELECT
            COALESCE(SUM(is_up_vote::int), 0) AS up_votes,
            COUNT(is_up_vote) AS votes
        FROM
            rating_reviews
        WHERE
            rating_id = OLD.rating_id)
    UPDATE
        location_ratings
    SET
        up_votes = ratings_ratio.up_votes,
        down_votes = ratings_ratio.votes - ratings_ratio.up_votes
    FROM
        ratings_ratio
    WHERE
        rating_id = OLD.rating_id;
    RETURN old;
END;
$$
LANGUAGE plpgsql;

CREATE TRIGGER delete_rating_review_tgr
    AFTER DELETE ON rating_reviews
    FOR EACH ROW
    EXECUTE PROCEDURE delete_rating_review ();

