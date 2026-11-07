-- =====================================================================
-- SWON Web — demo reference data + sample marketplace activity.
-- Idempotent: safe to re-run (INSERT ... ON DUPLICATE KEY UPDATE).
-- Demo OTP login: any valid Indian mobile number, e.g. 9000000001.
--
-- CONTEXT SOURCE OF TRUTH: the SWON mobile app
-- (C:\Users\kathi\WorkBridge). Launch footprint is Coimbatore + Tiruppur,
-- Tamil Nadu — not Chennai/Bengaluru. Areas below are the same 25 service
-- areas seeded into every SWON database
-- (Backend/prisma/migrations/20260925081400_seed_reference).
-- =====================================================================

USE `swon_web`;

-- ---------------------------------------------------------------- areas
-- 15 Coimbatore + 10 Tiruppur service areas (V1 launch footprint).
INSERT INTO `area` (`id`, `public_id`, `name`, `city`, `active`) VALUES
  (1,  'area_cbe_singanallur',        'Singanallur',        'Coimbatore', 1),
  (2,  'area_cbe_ganapathy',          'Ganapathy',          'Coimbatore', 1),
  (3,  'area_cbe_peelamedu',          'Peelamedu',          'Coimbatore', 1),
  (4,  'area_cbe_rs_puram',           'R.S. Puram',         'Coimbatore', 1),
  (5,  'area_cbe_saibaba_colony',     'Saibaba Colony',     'Coimbatore', 1),
  (6,  'area_cbe_kuniamuthur',        'Kuniamuthur',        'Coimbatore', 1),
  (7,  'area_cbe_pollachi_road',      'Pollachi Road',      'Coimbatore', 1),
  (8,  'area_cbe_sidco_kurichi',      'SIDCO Kurichi',      'Coimbatore', 1),
  (9,  'area_cbe_vadavalli',          'Vadavalli',          'Coimbatore', 1),
  (10, 'area_cbe_race_course',        'Race Course',        'Coimbatore', 1),
  (11, 'area_cbe_gandhipuram',        'Gandhipuram',        'Coimbatore', 1),
  (12, 'area_cbe_ukkadam',            'Ukkadam',            'Coimbatore', 1),
  (13, 'area_cbe_kuniamuthur_idc',    'Kuniamuthur IDC',    'Coimbatore', 1),
  (14, 'area_cbe_perur',              'Perur',              'Coimbatore', 1),
  (15, 'area_cbe_sulur',              'Sulur',              'Coimbatore', 1),
  (16, 'area_tup_avinashi',           'Avinashi',           'Tiruppur',   1),
  (17, 'area_tup_kangayam_road',      'Kangayam Road',      'Tiruppur',   1),
  (18, 'area_tup_nehru_nagar',        'Nehru Nagar',        'Tiruppur',   1),
  (19, 'area_tup_thirumuruganpoondi', 'Thirumuruganpoondi', 'Tiruppur',   1),
  (20, 'area_tup_palladam_road',      'Palladam Road',      'Tiruppur',   1),
  (21, 'area_tup_porcupillai',        'Porcupillai',        'Tiruppur',   1),
  (22, 'area_tup_mangalam',           'Mangalam',           'Tiruppur',   1),
  (23, 'area_tup_udumalpet_road',     'Udumalpet Road',     'Tiruppur',   1),
  (24, 'area_tup_kangeyam',           'Kangeyam',           'Tiruppur',   1),
  (25, 'area_tup_dharapuram_road',    'Dharapuram Road',    'Tiruppur',   1)
ON DUPLICATE KEY UPDATE `public_id` = VALUES(`public_id`), `name` = VALUES(`name`),
  `city` = VALUES(`city`), `active` = VALUES(`active`);

-- ------------------------------------------------------------ categories
-- The mobile app's 12 parent work domains (UI names; slugs stay stable so
-- the photo/tile mapping in app/helpers.php keeps working).
INSERT INTO `job_category` (`id`, `public_id`, `name`, `slug`, `icon`, `active`) VALUES
  (1,  'cat_event',        'Events & Weddings',        'event-crew',    'sparkles',  1),
  (2,  'cat_hospitality',  'Hotels & Catering',        'hospitality',   'utensils',  1),
  (3,  'cat_logistics',    'Delivery & Logistics',     'logistics',     'truck',     1),
  (4,  'cat_retail',       'Retail & Showroom',        'retail',        'tag',       1),
  (5,  'cat_construction', 'Construction & Labour',    'construction',  'helmet',    1),
  (6,  'cat_housekeeping', 'Housekeeping & Cleaning',  'housekeeping',  'broom',     1),
  (7,  'cat_promotions',   'Promotions & Field Sales', 'promotions',    'megaphone', 1),
  (8,  'cat_warehouse',    'Warehouse & Loading',      'warehouse',     'box',       1),
  (9,  'cat_manufacturing','Manufacturing & Units',    'manufacturing', 'factory',   1),
  (10, 'cat_healthcare',   'Healthcare Support',       'healthcare',    'heart',     1),
  (11, 'cat_security',     'Security & Surveillance',  'security',      'shield',    1),
  (12, 'cat_transport',    'Drivers & Transport',      'transport',     'truck',     1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `slug` = VALUES(`slug`),
  `icon` = VALUES(`icon`), `active` = VALUES(`active`);

-- --------------------------------------------------------------- people
INSERT INTO `account` (`id`, `public_id`, `phone`, `role`, `status`, `onboarded_at`, `created_at`) VALUES
  (1, 'acc_arun',          '9000000001', 'worker',   'ACTIVE', '2026-10-01 10:00:00', '2026-10-01 09:55:00'),
  (2, 'acc_kovai_events',  '9000000002', 'employer', 'ACTIVE', '2026-10-01 10:10:00', '2026-10-01 10:05:00'),
  (3, 'acc_priya',         '9000000003', 'worker',   'ACTIVE', '2026-10-02 11:00:00', '2026-10-02 10:50:00'),
  (4, 'acc_tiruppur_knit', '9000000004', 'employer', 'ACTIVE', '2026-10-02 12:00:00', '2026-10-02 11:50:00'),
  (5, 'acc_karthik',       '9000000005', 'worker',   'ACTIVE', '2026-10-03 09:00:00', '2026-10-03 08:55:00')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`), `status` = VALUES(`status`), `onboarded_at` = VALUES(`onboarded_at`);

INSERT INTO `worker_profile` (`id`, `account_id`, `full_name`, `area_id`, `skills`, `availability`, `about`) VALUES
  (1, 1, 'Arun Kumar', 4, 'Event setup, Crowd management, Sound assistance', 'FULL_TIME',
      'Five years of event and banquet crew work across Coimbatore.'),
  (2, 3, 'Priya Sharma', 3, 'Catering, Guest handling, POS billing', 'WEEKENDS',
      'Hospitality graduate, comfortable with long event shifts in Peelamedu and Race Course.'),
  (3, 5, 'Karthik Raja', 8, 'Loading, Inventory, Forklift helper', 'FLEXIBLE',
      'Warehouse and logistics experience in SIDCO Kurichi and Sulur.')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`), `area_id` = VALUES(`area_id`),
  `skills` = VALUES(`skills`), `availability` = VALUES(`availability`), `about` = VALUES(`about`);

INSERT INTO `employer_profile` (`id`, `account_id`, `org_name`, `contact_name`, `area_id`, `about`) VALUES
  (1, 2, 'Kovai Event Works', 'Senthil Kumar', 11,
      'Weddings, corporate events and exhibitions across Coimbatore since 2016.'),
  (2, 4, 'Tiruppur Knit Staffing', 'Deepa Iyer', 17,
      'Workforce partner for knitwear units, showrooms and warehouses across Tiruppur.')
ON DUPLICATE KEY UPDATE `org_name` = VALUES(`org_name`), `contact_name` = VALUES(`contact_name`),
  `area_id` = VALUES(`area_id`), `about` = VALUES(`about`);

-- ----------------------------------------------------------------- jobs
-- Opportunities (UI term) across the Coimbatore + Tiruppur launch footprint.
INSERT INTO `job`
  (`id`, `public_id`, `employer_id`, `category_id`, `area_id`, `title`, `description`, `address`,
   `pay_amount_paise`, `pay_type`, `headcount`, `starts_on`, `ends_on`, `start_time`, `end_time`, `status`, `created_at`)
VALUES
  (1, 'job_wedding_crew', 2, 1, 3, 'Wedding Reception Crew',
      'Help set up the mandap and dining area, manage guest movement and assist the sound team. Meals and uniform provided.',
      'Sri Lakshmi Mahal, Peelamedu', 90000, 'DAY', 10, '2026-10-27', '2026-10-28', '15:00:00', '23:00:00', 'OPEN', '2026-10-20 09:00:00'),
  (2, 'job_catering_staff', 2, 2, 1, 'Catering Service Staff',
      'Serving staff for a 400-guest reception. Prior banquet experience preferred. Dinner provided.',
      'Singanallur Convention Hall', 75000, 'DAY', 6, '2026-10-29', '2026-10-29', '17:00:00', '23:30:00', 'OPEN', '2026-10-20 09:10:00'),
  (3, 'job_warehouse_loaders', 4, 8, 8, 'Warehouse Loaders',
      'Load and unload cartons and do basic inventory counting at a knitwear warehouse. Safety shoes recommended.',
      'SIDCO Kurichi Industrial Estate, Block C', 85000, 'DAY', 8, '2026-10-26', '2026-10-30', '08:00:00', '17:00:00', 'OPEN', '2026-10-20 09:20:00'),
  (4, 'job_retail_assistant', 2, 4, 4, 'Retail Sales Assistant',
      'Weekend support for a garment store — billing, folding and customer assistance.',
      'DB Road, R.S. Puram', 65000, 'DAY', 4, '2026-10-31', '2026-11-02', '10:00:00', '20:00:00', 'OPEN', '2026-10-21 10:00:00'),
  (5, 'job_housekeeping', 4, 6, 18, 'Hotel Housekeeping Attendants',
      'Room turnover and corridor upkeep for a 60-room property. Training provided on day one.',
      'Nehru Nagar, near the bus stand', 60000, 'DAY', 5, '2026-10-26', '2026-11-05', '07:00:00', '15:00:00', 'OPEN', '2026-10-21 11:00:00'),
  (6, 'job_brand_promoter', 4, 7, 22, 'Weekend Brand Promoter',
      'Promote a knitwear brand at a showroom launch. Good communication skills required.',
      'Mangalam Main Road showroom', 110000, 'DAY', 3, '2026-11-01', '2026-11-02', '11:00:00', '21:00:00', 'OPEN', '2026-10-22 09:30:00'),
  (7, 'job_construction_helpers', 2, 5, 9, 'Construction Helpers',
      'Site cleaning, material shifting and basic masonry assistance. Helmet provided.',
      'Vadavalli Link Road site', 80000, 'DAY', 12, '2026-10-25', '2026-11-08', '08:00:00', '17:00:00', 'OPEN', '2026-10-22 10:00:00'),
  (8, 'job_sound_light_crew', 2, 1, 2, 'Sound & Light Crew',
      'Draft requirement for a corporate annual day. Confirm dates before publishing.',
      'Ganapathy community hall', 100000, 'DAY', 4, '2026-11-10', '2026-11-11', '12:00:00', '22:00:00', 'DRAFT', '2026-10-23 09:00:00'),
  (9, 'job_banquet_stewards', 2, 2, 10, 'Banquet Stewards',
      'Banquet service team for a hotel event. Grooming standards apply, uniform provided.',
      'Race Course Road banquet hall', 70000, 'DAY', 6, '2026-10-28', '2026-10-30', '16:00:00', '23:00:00', 'OPEN', '2026-10-23 10:00:00'),
  (10, 'job_knitwear_packing', 4, 9, 17, 'Knitwear Packing Operators',
      'Packing and labelling finished knitwear for dispatch. Standing work, training provided.',
      'Kangayam Road knitwear unit', 70000, 'DAY', 20, '2026-10-27', '2026-11-06', '09:00:00', '18:00:00', 'OPEN', '2026-10-23 11:00:00')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `status` = VALUES(`status`),
  `pay_amount_paise` = VALUES(`pay_amount_paise`), `headcount` = VALUES(`headcount`);

-- --------------------------------------------------------- applications
-- Flight Requests (UI term).
INSERT INTO `application` (`id`, `public_id`, `job_id`, `worker_id`, `status`, `note`, `created_at`) VALUES
  (1, 'app_arun_wedding',   1, 1, 'HIRED',       'Available for both days.', '2026-10-21 09:00:00'),
  (2, 'app_arun_warehouse', 3, 1, 'APPLIED',     '', '2026-10-22 08:30:00'),
  (3, 'app_arun_housekeep', 5, 1, 'SHORTLISTED', '', '2026-10-22 12:00:00'),
  (4, 'app_priya_wedding',  1, 3, 'APPLIED',     'Have banquet experience.', '2026-10-21 11:00:00'),
  (5, 'app_priya_catering', 2, 3, 'APPLIED',     '', '2026-10-22 10:15:00'),
  (6, 'app_karthik_wedding',1, 5, 'APPLIED',     '', '2026-10-22 16:00:00'),
  (7, 'app_karthik_retail', 4, 5, 'WAITLISTED',  'Only weekends available.', '2026-10-23 09:45:00')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `note` = VALUES(`note`);
