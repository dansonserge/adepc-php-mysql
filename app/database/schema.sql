-- ADEPC website schema. MySQL 5.7+ / MariaDB 10.3+, utf8mb4.
-- Every word and every image the public site shows comes from these tables
-- (or from files they point to). Templates hold structure only.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS locales (
  code         VARCHAR(8)   NOT NULL PRIMARY KEY,
  name         VARCHAR(64)  NOT NULL,
  short_label  VARCHAR(8)   NOT NULL,
  hreflang     VARCHAR(16)  NOT NULL,
  og_locale    VARCHAR(16)  NOT NULL,
  is_default   TINYINT(1)   NOT NULL DEFAULT 0,
  sort         INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  skey        VARCHAR(100) NOT NULL PRIMARY KEY,
  value       MEDIUMTEXT   NULL,
  is_default  TINYINT(1)   NOT NULL DEFAULT 0,
  updated_at  TIMESTAMP    NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS translations (
  locale      VARCHAR(8)   NOT NULL,
  tkey        VARCHAR(190) NOT NULL,
  value       MEDIUMTEXT   NOT NULL,
  updated_at  TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (locale, tkey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
  pkey        VARCHAR(32)  NOT NULL PRIMARY KEY,
  slug_fr     VARCHAR(100) NOT NULL DEFAULT '',
  slug_en     VARCHAR(100) NOT NULL DEFAULT '',
  in_sitemap  TINYINT(1)   NOT NULL DEFAULT 1,
  changefreq  VARCHAR(16)  NOT NULL DEFAULT 'monthly',
  priority    DECIMAL(2,1) NOT NULL DEFAULT 0.7,
  sort        INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS redirects (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  from_path   VARCHAR(255) NOT NULL,
  to_path     VARCHAR(255) NOT NULL,
  status      SMALLINT     NOT NULL DEFAULT 308,
  is_auto     TINYINT(1)   NOT NULL DEFAULT 0,
  UNIQUE KEY uq_redirect_from (from_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ref                VARCHAR(64)  NULL,
  kind               VARCHAR(16)  NOT NULL,              -- image | video
  path               VARCHAR(255) NOT NULL,              -- relative to public/media
  mime               VARCHAR(64)  NOT NULL,
  width              INT          NULL,
  height             INT          NULL,
  variants           VARCHAR(255) NULL,                  -- generated WebP widths, comma separated
  blur               TEXT         NULL,                  -- tiny JPEG data URL for the blur placeholder
  alt_fr             TEXT         NULL,
  alt_en             TEXT         NULL,
  focus              VARCHAR(32)  NULL,                  -- CSS object-position
  church_id          INT UNSIGNED NULL,
  tags               VARCHAR(255) NULL,
  in_gallery         TINYINT(1)   NOT NULL DEFAULT 0,
  in_church_moments  TINYINT(1)   NOT NULL DEFAULT 0,
  label              VARCHAR(255) NULL,                  -- admin-only name (original file name)
  sort               INT          NOT NULL DEFAULT 0,
  created_at         TIMESTAMP    NULL DEFAULT NULL,
  updated_at         TIMESTAMP    NULL DEFAULT NULL,
  UNIQUE KEY uq_media_ref (ref),
  KEY idx_media_church (church_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_slots (
  skey      VARCHAR(64)  NOT NULL PRIMARY KEY,
  media_id  INT UNSIGNED NULL,
  focus     VARCHAR(32)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menu_items (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  menu       VARCHAR(16)  NOT NULL,                      -- header | overlay | cta | footer | tabbar
  page_key   VARCHAR(32)  NOT NULL,
  label_fr   VARCHAR(120) NOT NULL,
  label_en   VARCHAR(120) NOT NULL,
  icon       VARCHAR(32)  NULL,                          -- icon file key (media/icons/<key>.svg)
  highlight  TINYINT(1)   NOT NULL DEFAULT 0,
  sort       INT          NOT NULL DEFAULT 0,
  visible    TINYINT(1)   NOT NULL DEFAULT 1,
  KEY idx_menu (menu, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS list_items (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  list_key      VARCHAR(48)  NOT NULL,
  title_fr      VARCHAR(255) NULL,
  title_en      VARCHAR(255) NULL,
  short_fr      VARCHAR(255) NULL,
  short_en      VARCHAR(255) NULL,
  body_fr       TEXT         NULL,
  body_en       TEXT         NULL,
  value         VARCHAR(64)  NULL,
  value_source  VARCHAR(16)  NULL,                       -- glance figures: churches | cities | provinces | manual
  url           VARCHAR(500) NULL,
  media_id      INT UNSIGNED NULL,
  sort          INT          NOT NULL DEFAULT 0,
  visible       TINYINT(1)   NOT NULL DEFAULT 1,
  KEY idx_list (list_key, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS churches (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug               VARCHAR(64)  NOT NULL,
  name               VARCHAR(120) NOT NULL,
  city_fr            VARCHAR(120) NOT NULL,
  city_en            VARCHAR(120) NOT NULL,
  region             VARCHAR(8)   NOT NULL,
  street             VARCHAR(190) NOT NULL,
  locality           VARCHAR(120) NOT NULL,
  postal_code        VARCHAR(16)  NOT NULL,
  lat                DECIMAL(9,6) NULL,
  lng                DECIMAL(9,6) NULL,
  phone              VARCHAR(40)  NULL,
  email              VARCHAR(190) NULL,
  pastor_fr          VARCHAR(190) NULL,
  pastor_en          VARCHAR(190) NULL,
  pastor_is_default  TINYINT(1)   NOT NULL DEFAULT 0,
  summary_fr         TEXT         NULL,
  summary_en         TEXT         NULL,
  intro_fr           TEXT         NULL,
  intro_en           TEXT         NULL,
  about_title_fr     VARCHAR(255) NULL,
  about_title_en     VARCHAR(255) NULL,
  hero_media_id      INT UNSIGNED NULL,
  band_focus         VARCHAR(32)  NULL,
  sort               INT          NOT NULL DEFAULT 0,
  published          TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at         TIMESTAMP    NULL DEFAULT NULL,
  UNIQUE KEY uq_church_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS church_services (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  church_id  INT UNSIGNED NOT NULL,
  day        VARCHAR(10)  NOT NULL,                      -- sunday | friday
  time       CHAR(5)      NOT NULL,                      -- 24 h HH:mm
  label_fr   VARCHAR(190) NOT NULL,
  label_en   VARCHAR(190) NOT NULL,
  sort       INT          NOT NULL DEFAULT 0,
  KEY idx_service_church (church_id, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kind               VARCHAR(16)  NOT NULL DEFAULT 'recurring',   -- featured | recurring
  title_fr           VARCHAR(255) NOT NULL,
  title_en           VARCHAR(255) NOT NULL,
  description_fr     TEXT         NOT NULL,
  description_en     TEXT         NOT NULL,
  date_mode          VARCHAR(8)   NOT NULL DEFAULT 'none',        -- none | date | text
  event_date         DATE         NULL,
  date_text_fr       VARCHAR(120) NULL,
  date_text_en       VARCHAR(120) NULL,
  date_is_default    TINYINT(1)   NOT NULL DEFAULT 0,
  recurrence_fr      VARCHAR(120) NULL,
  recurrence_en      VARCHAR(120) NULL,
  time_mode          VARCHAR(8)   NOT NULL DEFAULT 'none',        -- none | time | text
  event_time         CHAR(5)      NULL,
  time_text_fr       VARCHAR(120) NULL,
  time_text_en       VARCHAR(120) NULL,
  time_is_default    TINYINT(1)   NOT NULL DEFAULT 0,
  venue_fr           VARCHAR(255) NULL,                           -- NULL = not shown
  venue_en           VARCHAR(255) NULL,
  venue_is_default   TINYINT(1)   NOT NULL DEFAULT 0,
  registration_url   VARCHAR(500) NULL,                           -- NULL = no button
  people             TEXT         NULL,                           -- one name per line
  all_churches       TINYINT(1)   NOT NULL DEFAULT 0,
  media_id           INT UNSIGNED NULL,
  show_sunday_times  TINYINT(1)   NOT NULL DEFAULT 0,
  sort               INT          NOT NULL DEFAULT 0,
  published          TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at         TIMESTAMP    NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_churches (
  event_id   INT UNSIGNED NOT NULL,
  church_id  INT UNSIGNED NOT NULL,
  sort       INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (event_id, church_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS video_categories (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug      VARCHAR(32)  NOT NULL,                        -- page anchor (#message)
  role      VARCHAR(16)  NULL,                            -- messages | live | NULL
  name_fr   VARCHAR(120) NOT NULL,
  name_en   VARCHAR(120) NOT NULL,
  empty_fr  TEXT         NULL,
  empty_en  TEXT         NULL,
  sort      INT          NOT NULL DEFAULT 0,
  visible   TINYINT(1)   NOT NULL DEFAULT 1,
  UNIQUE KEY uq_category_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS videos (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title_fr          VARCHAR(255) NOT NULL,
  title_en          VARCHAR(255) NOT NULL,
  category_id       INT UNSIGNED NOT NULL,
  church_id         INT UNSIGNED NULL,
  date_label        VARCHAR(32)  NULL,
  duration_seconds  INT          NULL,
  poster_media_id   INT UNSIGNED NULL,
  source_kind       VARCHAR(16)  NOT NULL DEFAULT 'file',  -- file | youtube
  mp4_media_id      INT UNSIGNED NULL,
  webm_media_id     INT UNSIGNED NULL,
  youtube_id        VARCHAR(32)  NULL,
  sort              INT          NOT NULL DEFAULT 0,
  published         TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at        TIMESTAMP    NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stories (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  quote_fr      TEXT         NOT NULL,
  quote_en      TEXT         NOT NULL,
  name          VARCHAR(190) NOT NULL,
  detail_fr     VARCHAR(255) NULL,
  detail_en     VARCHAR(255) NULL,
  church_id     INT UNSIGNED NULL,
  media_id      INT UNSIGNED NULL,
  consent       TINYINT(1)   NOT NULL DEFAULT 0,
  confirm_note  TEXT         NULL,
  sort          INT          NOT NULL DEFAULT 0,
  published     TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at    TIMESTAMP    NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_purposes (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pkey      VARCHAR(32)  NOT NULL,
  label_fr  VARCHAR(120) NOT NULL,
  label_en  VARCHAR(120) NOT NULL,
  is_prayer TINYINT(1)   NOT NULL DEFAULT 0,
  sort      INT          NOT NULL DEFAULT 0,
  visible   TINYINT(1)   NOT NULL DEFAULT 1,
  UNIQUE KEY uq_purpose_key (pkey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_notes (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kind     VARCHAR(16)  NOT NULL,                         -- removed | banner
  what_fr  TEXT         NOT NULL,
  what_en  TEXT         NOT NULL,
  why_fr   TEXT         NULL,
  why_en   TEXT         NULL,
  sort     INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email             VARCHAR(190) NOT NULL,
  name              VARCHAR(120) NOT NULL,
  password_hash     VARCHAR(255) NOT NULL,
  role              VARCHAR(16)  NOT NULL DEFAULT 'editor',   -- admin | editor
  ui_locale         VARCHAR(8)   NOT NULL DEFAULT 'fr',
  failed_attempts   INT          NOT NULL DEFAULT 0,
  locked_until      DATETIME     NULL,
  reset_token_hash  CHAR(64)     NULL,
  reset_expires     DATETIME     NULL,
  last_login_at     DATETIME     NULL,
  created_at        TIMESTAMP    NULL DEFAULT NULL,
  updated_at        TIMESTAMP    NULL DEFAULT NULL,
  UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ip            VARCHAR(45)  NOT NULL,
  email         VARCHAR(190) NOT NULL,
  attempted_at  DATETIME     NOT NULL,
  KEY idx_attempt_ip (ip, attempted_at),
  KEY idx_attempt_email (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
