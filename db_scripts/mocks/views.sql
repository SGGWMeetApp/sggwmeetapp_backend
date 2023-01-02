CREATE OR REPLACE VIEW all_events AS
SELECT
    p.event_id,
    p.location_id,
    l.name AS locName,
    l.description AS locDes,
    l.lat,
    l.long,
    l.text_location AS text_location,
    l.rating_pct,
    p.name AS eventName,
    p.description AS evntDes,
    p.start_date,
    p.can_edit,
    p.is_public,
    p.notification_enabled,
    b.user_id,
    b.first_name,
    b.last_name,
    b.email,
    b.phone_number_prefix,
    b.phone_number,
    b.avatar_path,
    b.description AS userDes,
    b.creation_date AS "userRegistrationDate",
    ARRAY_TO_JSON(ARRAY (
            SELECT
                lc.name
            FROM app_owner.location_categories lc
            INNER JOIN app_owner.locations_location_categories llc ON llc.category_id = lc.category_id
            WHERE
                llc.location_id = p.location_id)) AS category_names,
    ARRAY_TO_JSON(ARRAY (
            SELECT
                lcp.photo_path
            FROM app_owner.location_photos lcp
            WHERE
                lcp.location_id = p.location_id)) AS photo_paths,
    ug.group_id,
    ug.name AS group_name,
    ug.owner_id AS group_owner_id,
    (
        SELECT
            COUNT(ea.user_id)
        FROM
            app_owner.event_attenders ea
        WHERE
            ea.event_id = p.event_id) AS "attendersCount"
FROM
    app_owner.events p
    INNER JOIN app_owner.users b ON p.owner_id = b.user_id
    INNER JOIN app_owner.locations l ON p.location_id = l.location_id
    LEFT OUTER JOIN app_owner.user_groups ug ON (p.group_id = ug.group_id);

CREATE OR REPLACE VIEW all_places AS
SELECT
    p.location_id,
    p.name,
    p.description,
    p.lat,
    p.long,
    p.rating_pct,
    ARRAY_TO_JSON(ARRAY (
            SELECT
                lc.name
            FROM app_owner.location_categories lc
            INNER JOIN app_owner.locations_location_categories llc ON llc.category_id = lc.category_id
            WHERE
                llc.location_id = p.location_id)) AS category_names,
    ARRAY_TO_JSON(ARRAY (
            SELECT
                lcp.photo_path
            FROM app_owner.location_photos lcp
            WHERE
                lcp.location_id = p.location_id)) AS photo_paths,
    p.text_location,
    p.menu AS menu_path,
    p.ratings_number AS reviews_count
FROM
    app_owner.locations p;

CREATE OR REPLACE VIEW all_places AS
SELECT
    p.location_id,
    p.name,
    p.description,
    p.lat,
    p.long,
    p.rating_pct,
    ARRAY_TO_JSON(ARRAY (
            SELECT
                lc.name
            FROM app_owner.location_categories lc
            INNER JOIN app_owner.locations_location_categories llc ON llc.category_id = lc.category_id
            WHERE
                llc.location_id = p.location_id)) AS category_names,
    ARRAY_TO_JSON(ARRAY (
            SELECT
                lcp.photo_path
            FROM app_owner.location_photos lcp
            WHERE
                lcp.location_id = p.location_id)) AS photo_paths,
    p.text_location,
    p.menu AS menu_path,
    p.ratings_number AS reviews_count
FROM
    app_owner.locations p;

