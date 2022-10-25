CREATE SCHEMA IF NOT EXISTS app_owner AUTHORIZATION cypmguynebukil;
ALTER DATABASE d5gaejcsil51el SET search_path TO app_owner;
ALTER DATABASE d5gaejcsil51el SET timezone TO 'UTC';

CREATE TABLE users
(
    user_id           INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    username          VARCHAR(255) NOT NULL,
    first_name        VARCHAR(255) NOT NULL, 
    last_name         VARCHAR(255) NOT NULL,        
    location_sharing_mode INTEGER NOT NULL DEFAULT 0,
    UNIQUE (username)
);

CREATE TABLE user_groups
(
    group_id           INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name          VARCHAR(255) NOT NULL,
    admin INTEGER NOT NULL,
    FOREIGN KEY (admin) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
);

CREATE TABLE users_user_groups
(
    user_id INTEGER NOT NULL,
    group_id           INTEGER NOT NULL,
    PRIMARY KEY (user_id, group_id),
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (group_id) REFERENCES user_groups (group_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX group_id_inx ON users_user_groups (group_id);

CREATE TABLE events
(
    event_id           INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    group_id INTEGER NOT NULL,
    start_date TIMEZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    name          VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_public BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (group_id) REFERENCES user_groups (group_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE event_notifications
(
    notification_id INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    event_id           INTEGER NOT NULL,
    message_title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events (event_id) ON DELETE CASCADE ON UPDATE CASCADE
);


