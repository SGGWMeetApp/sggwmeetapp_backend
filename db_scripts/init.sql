CREATE SCHEMA IF NOT EXISTS app_owner AUTHORIZATION cypmguynebukil;

ALTER DATABASE d5gaejcsil51el SET search_path TO app_owner;

ALTER DATABASE d5gaejcsil51el SET timezone TO 'UTC';

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
    name VARCHAR(255) NOT NULL,
    UNIQUE(name)
);

CREATE TABLE locations (
    location_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    category_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    lat numeric(9,6) NOT NULL,
    long numeric(9,6) NOT NULL,
    menu TEXT NULL,
    rating_pct NUMERIC(4, 2) NULL,
    FOREIGN KEY (category_id) REFERENCES location_categories (category_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE events (
    event_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    group_id integer NOT NULL,
    location_id INTEGER NOT NULL,
    start_date DATETIME NOT NULL DATETIME DEFAULT CURRENT_DATETIME,
    name varchar(255) NOT NULL,
    description text NULL,
    is_public boolean NOT NULL DEFAULT FALSE,
    owner_id integer NOT NULL,
    FOREIGN KEY (location_id) REFERENCES locations (location_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (group_id) REFERENCES user_groups (group_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
);

CREATE TABLE event_notifications (
    notification_id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    event_id integer NOT NULL,
    message_title varchar(255) NOT NULL,
    message text NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events (event_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE location_ratings (
    location_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    is_positive BOOLEAN DEFAULT TRUE,
    comment TEXT NULL,
    up_votes INTEGER NOT NULL DEFAULT 0,
    down_votes INTEGER NOT NULL DEFAULT 0,
    description TEXT NOT NULL,
    publication_date DATETIME NOT NULL DEFAULT CURRENT_DATETIME,
    FOREIGN KEY (location_id) REFERENCES locations (location_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE
);


