-- Optional MySQL indexes for a faster public LiteBans table.
-- Run once on the database used by LiteBans. Skip an index if it already exists.

ALTER TABLE litebans_bans
  ADD INDEX idx_mineacle_active_time (`active`, `time`),
  ADD INDEX idx_mineacle_active_until (`active`, `until`),
  ADD INDEX idx_mineacle_uuid (`uuid`);

ALTER TABLE litebans_history
  ADD INDEX idx_mineacle_history_uuid_date (`uuid`, `date`),
  ADD INDEX idx_mineacle_history_name (`name`);
