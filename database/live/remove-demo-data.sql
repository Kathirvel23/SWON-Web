-- =====================================================================
-- SWON Web — clear the demo marketplace so you can launch with real data.
--
-- Run this AFTER importing database/live/schema.sql and (optionally)
-- database/live/seed.sql, and only on a database that has nothing real in
-- it yet. It removes the five demo accounts, their opportunities, their
-- Flight Requests and any pending OTP codes.
--
-- It KEEPS the reference data the app needs: `area` and `job_category`.
-- Deleting those would leave the create-opportunity form with empty
-- dropdowns.
--
-- Safe to run once. Running it twice is harmless (nothing left to match).
-- =====================================================================

-- 1. Flight Requests sent to the demo hosts' opportunities
DELETE a FROM `application` a
JOIN `job` j              ON j.id  = a.job_id
JOIN `employer_profile` ep ON ep.id = j.employer_id
JOIN `account` ea          ON ea.id = ep.account_id
WHERE ea.public_id IN ('acc_kovai_events', 'acc_tiruppur_knit');

-- 2. Flight Requests made by the demo workers
DELETE a FROM `application` a
JOIN `worker_profile` wp ON wp.id = a.worker_id
JOIN `account` wa        ON wa.id = wp.account_id
WHERE wa.public_id IN ('acc_arun', 'acc_priya', 'acc_karthik');

-- 3. opportunities posted by the demo hosts
DELETE j FROM `job` j
JOIN `employer_profile` ep ON ep.id = j.employer_id
JOIN `account` ea          ON ea.id = ep.account_id
WHERE ea.public_id IN ('acc_kovai_events', 'acc_tiruppur_knit');

-- 4. the demo accounts themselves (profiles cascade)
DELETE FROM `account`
WHERE public_id IN ('acc_arun', 'acc_kovai_events', 'acc_priya', 'acc_tiruppur_knit', 'acc_karthik');

-- 5. any leftover OTP challenges
DELETE FROM `otp_challenge`;

-- Expected result: 0 accounts, 0 opportunities, 0 Flight Requests,
-- 25 areas (15 Coimbatore + 10 Tiruppur), 12 categories.
SELECT
  (SELECT COUNT(*) FROM `account`)      AS accounts,
  (SELECT COUNT(*) FROM `job`)          AS jobs,
  (SELECT COUNT(*) FROM `application`)  AS applications,
  (SELECT COUNT(*) FROM `area`)         AS areas,
  (SELECT COUNT(*) FROM `job_category`) AS categories;
