-- ---------------------------------------------------------------------------
-- contract_party_data: MyISAM/latin1 with TEXT join columns
--                   -> InnoDB/utf8mb4 with varchar join columns, then indexed
--
-- NOT a Laravel migration, by the dev's call 2026-08-20: the collation name
-- depends on the client's database type and version, so this is run by hand at
-- deployment time by someone who can see that database. See
-- .scratch/contracts-dashboard-perf/issues/20-migration-portability.md
--
-- PREFER THE ARTISAN COMMAND. This file is the by-hand fallback, for a database
-- you can only reach with a SQL client. Everywhere PHP runs, use:
--
--     php artisan contract:convert-party-data                     # checks only
--     php artisan contract:convert-party-data --apply
--     php artisan contract:convert-party-data --apply --collation=utf8mb4_general_ci
--
-- It does every check listed below for you and refuses to run if one fails.
-- Default collation utf8mb4_unicode_ci, default width 32.
--
-- ALREADY RUN on the dev database apollo_contracts_expense, 2026-08-21, via the
-- command: utf8mb4_unicode_ci, varchar(32), 6,940 rows in and 6,940 rows out.
--
-- ONE SIDE EFFECT worth knowing before you run it anywhere: step 2 promotes
-- party_address from TEXT to MEDIUMTEXT. That is CONVERT TO doing its job - it
-- keeps the column's capacity in *characters* the same when the character set
-- goes from 1 byte to 4. Nothing is lost, the column gets bigger. It is not a
-- mistake and it does not need undoing.
--
-- BEFORE YOU RUN IT
--
-- 1. Check the collation below exists on this server, and matches the
--    `contracts` table on THIS database:
--
--      SELECT table_name, engine, table_collation
--        FROM information_schema.tables
--       WHERE table_schema = DATABASE()
--         AND table_name IN ('contracts', 'contract_party_data');
--
--    If `contracts` is not utf8mb4_unicode_ci, change every utf8mb4_unicode_ci
--    below to whatever `contracts` actually uses. The whole point is that the
--    two tables match, so joins do not cross a collation boundary. Of the 8
--    client databases on the dev machine, 7 use utf8mb4_general_ci and only
--    apollo_contracts_expense uses utf8mb4_unicode_ci - so expect to change it.
--
--    Whatever you pick, it must be case-INSENSITIVE (_ci). Two queries compare
--    contract_party_type against lowercase 'internal'
--    (ContractController.php:725 and :4358) and only work because case is
--    ignored. A _bin or _cs collation breaks them silently.
--
-- 2. Check nothing is longer than the widths below. Expected 10 and 3:
--
--      SELECT MAX(CHAR_LENGTH(contract_party_type))        AS party_type,
--             MAX(CHAR_LENGTH(contract_party_location_id)) AS location_id
--        FROM contract_party_data;
--
--    Anything above 32 and STOP - do not run step 3, it would cut data.
--
-- 3. Take a backup. This is a full table rebuild and it LOCKS THE TABLE.
--    It needs a maintenance window.
--
-- WHAT CHANGES, AND WHAT DOES NOT
--
-- Only two columns change type: contract_party_type and
-- contract_party_location_id, the two columns every party lookup joins on.
--
-- The CONVERT TO in step 2 is table-wide - it changes the character set of all
-- six text columns (contract_party_type, party_sub_type,
-- contract_party_location_id, vendor_code, party_address, contact_details).
-- That is what CONVERT TO does and it is wanted: a half-converted table still
-- has a collation boundary in it. The four other columns keep their type;
-- party_address in particular stays TEXT, because it holds a free-form address
-- and 32 characters would be nowhere near enough.
--
-- custom_field_group_id is already int(11) and is not touched.
-- ---------------------------------------------------------------------------

-- Step 1. Engine. Do this first: CONVERT TO on a large MyISAM table is slower
-- and the indexes at the end need InnoDB anyway.
ALTER TABLE `contract_party_data` ENGINE = InnoDB;

-- Step 2. Character set, whole table.
ALTER TABLE `contract_party_data`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Step 3. The two join columns, TEXT -> varchar(32).
--
-- 32, not 20: the dev's call 2026-08-20. contract_party_type is a three-value
-- set fixed in PHP - Internal, External, Intergroup, longest 10 - so 32 leaves
-- room for a fourth without another maintenance window.
-- contract_party_location_id holds a branch id as text, longest 3 seen across
-- all 8 client databases; 32 holds a 32-digit id.
--
-- TEXT cannot be indexed without a prefix length, which is the reason this
-- step exists at all.
ALTER TABLE `contract_party_data`
  MODIFY `contract_party_type` varchar(32)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  MODIFY `contract_party_location_id` varchar(32)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;

-- Step 4. The indexes. custom_field_group_id is the column every party lookup
-- joins on and the table has PRIMARY only today.
ALTER TABLE `contract_party_data`
  ADD INDEX `idx_cpd_custom_field_group_id` (`custom_field_group_id`),
  ADD INDEX `idx_cpd_type_location` (`contract_party_type`, `contract_party_location_id`);

-- Step 5. Check it landed.
--
--   SELECT engine, table_collation FROM information_schema.tables
--    WHERE table_schema = DATABASE() AND table_name = 'contract_party_data';
--
--   SHOW INDEX FROM contract_party_data;
--
--   SELECT COUNT(*) FROM contract_party_data;   -- must equal the count before

-- ---------------------------------------------------------------------------
-- GOING BACK
--
-- Reversing is NOT risk-free. latin1 cannot hold every character utf8mb4 can,
-- so the round trip only survives if nothing wrote non-latin1 text in between.
-- Restore the backup instead if you can.
--
--   ALTER TABLE `contract_party_data`
--     DROP INDEX `idx_cpd_type_location`,
--     DROP INDEX `idx_cpd_custom_field_group_id`;
--
--   ALTER TABLE `contract_party_data`
--     MODIFY `contract_party_type` text NULL,
--     MODIFY `contract_party_location_id` text NULL;
--
--   ALTER TABLE `contract_party_data`
--     CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci;
--
--   ALTER TABLE `contract_party_data` ENGINE = MyISAM;
-- ---------------------------------------------------------------------------
