-- #!sqlite
-- #{ azskyblock_island
-- #    { init
CREATE TABLE IF NOT EXISTS az_skyblock (
    username TEXT,
    data TEXT,
    date_created TEXT
);
-- #      }
-- # { select
-- #     :username string
SELECT * FROM az_skyblock WHERE username = :username;
-- # }
-- # { selects
SELECT * FROM az_skyblock;
-- # }
-- # { update
-- #      :username string
-- #      :data string
-- #      :date_created string
UPDATE az_skyblock SET data=:data, date_created=:date_created WHERE username = :username;
-- # }
-- # { insert
-- #      :username string
-- #      :data string
-- #      :date_created string
INSERT INTO az_skyblock(username, data, date_created) VALUES (:username, :data, :date_created);
-- # }
-- # { delete
-- #       :username string
DELETE FROM az_skyblock WHERE username = :username;
-- # }
-- # { count
SELECT COUNT(*) FROM az_skyblock;
-- # }
-- # }