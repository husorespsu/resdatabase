-- ============================================================
-- PSU Research Project Management System
-- MySQL 8.x Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS research_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE research_management;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `google_id`   VARCHAR(255)  DEFAULT NULL,
    `email`       VARCHAR(255)  NOT NULL,
    `name`        VARCHAR(255)  NOT NULL,
    `avatar`      VARCHAR(500)  DEFAULT NULL,
    `role`        ENUM('superadmin','admin','executive') NOT NULL DEFAULT 'executive',
    `department`  VARCHAR(255)  DEFAULT NULL,
    `phone`       VARCHAR(20)   DEFAULT NULL,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `last_login`  DATETIME      DEFAULT NULL,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email`    (`email`),
    UNIQUE KEY `uq_users_google_id`(`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: funding_sources
-- ============================================================
CREATE TABLE IF NOT EXISTS `funding_sources` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(255) NOT NULL,
    `type`         ENUM('internal','external') NOT NULL DEFAULT 'internal',
    `organization` VARCHAR(255) DEFAULT NULL,
    `description`  TEXT         DEFAULT NULL,
    `budget_year`  YEAR         DEFAULT NULL,
    `is_active`    TINYINT(1)  NOT NULL DEFAULT 1,
    `created_at`   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: fields_of_study
-- ============================================================
CREATE TABLE IF NOT EXISTS `fields_of_study` (
    `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`     VARCHAR(20)  NOT NULL,
    `name_th`  VARCHAR(255) NOT NULL,
    `name_en`  VARCHAR(255) NOT NULL,
    `faculty`  VARCHAR(255) NOT NULL,
    `created_at` DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_fields_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: expert_reviewers
-- ============================================================
CREATE TABLE IF NOT EXISTS `expert_reviewers` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`            VARCHAR(50)  DEFAULT NULL,
    `first_name`       VARCHAR(100) NOT NULL,
    `last_name`        VARCHAR(100) NOT NULL,
    `expertise`        VARCHAR(500) DEFAULT NULL,
    `institution`      VARCHAR(255) DEFAULT NULL,
    `position`         VARCHAR(255) DEFAULT NULL,
    `email`            VARCHAR(255) DEFAULT NULL,
    `phone`            VARCHAR(20)  DEFAULT NULL,
    `bank_name`        VARCHAR(100) DEFAULT NULL,
    `bank_account`     VARCHAR(50)  DEFAULT NULL,
    `bank_branch`      VARCHAR(100) DEFAULT NULL,
    `id_card_number`   VARCHAR(20)  DEFAULT NULL,
    `address`          TEXT         DEFAULT NULL,
    `is_active`        TINYINT(1)  NOT NULL DEFAULT 1,
    `created_at`       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: research_proposals
-- ============================================================
CREATE TABLE IF NOT EXISTS `research_proposals` (
    `id`                       INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `proposal_code`            VARCHAR(50)     NOT NULL,
    `title_th`                 VARCHAR(1000)   NOT NULL,
    `title_en`                 VARCHAR(1000)   DEFAULT NULL,
    `principal_investigator_id` INT UNSIGNED   NOT NULL,
    `co_investigators`         JSON            DEFAULT NULL,
    `field_of_study_id`        INT UNSIGNED   DEFAULT NULL,
    `funding_source_id`        INT UNSIGNED   DEFAULT NULL,
    `budget_requested`         DECIMAL(15,2)  NOT NULL DEFAULT 0.00,
    `budget_year`              YEAR           NOT NULL,
    `abstract`                 TEXT           DEFAULT NULL,
    `objectives`               TEXT           DEFAULT NULL,
    `methodology`              TEXT           DEFAULT NULL,
    `start_date`               DATE           DEFAULT NULL,
    `end_date`                 DATE           DEFAULT NULL,
    `status`                   ENUM('draft','reviewing','approved','rejected') NOT NULL DEFAULT 'draft',
    `attachment_path`          VARCHAR(500)   DEFAULT NULL,
    `submitted_at`             DATETIME       DEFAULT NULL,
    `approved_by`              INT UNSIGNED   DEFAULT NULL,
    `approved_at`              DATETIME       DEFAULT NULL,
    `notes`                    TEXT           DEFAULT NULL,
    `created_by`               INT UNSIGNED   NOT NULL,
    `created_at`               DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`               DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_proposals_code` (`proposal_code`),
    CONSTRAINT `fk_proposals_pi`       FOREIGN KEY (`principal_investigator_id`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_proposals_field`    FOREIGN KEY (`field_of_study_id`)         REFERENCES `fields_of_study`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_proposals_funding`  FOREIGN KEY (`funding_source_id`)          REFERENCES `funding_sources`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_proposals_creator`  FOREIGN KEY (`created_by`)                REFERENCES `users`(`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_proposals_approver` FOREIGN KEY (`approved_by`)               REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: research_projects
-- ============================================================
CREATE TABLE IF NOT EXISTS `research_projects` (
    `id`                       INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `proposal_id`              INT UNSIGNED   NOT NULL,
    `project_code`             VARCHAR(50)    NOT NULL,
    `status`                   ENUM('approved','in_progress','completed','closed','cancelled') NOT NULL DEFAULT 'approved',
    `approved_date`            DATE           DEFAULT NULL,
    `approved_budget`          DECIMAL(15,2)  NOT NULL DEFAULT 0.00,
    `approved_by`              INT UNSIGNED   DEFAULT NULL,
    `contract_number`          VARCHAR(100)   DEFAULT NULL,
    `contract_date`            DATE           DEFAULT NULL,
    `actual_start_date`        DATE           DEFAULT NULL,
    `actual_end_date`          DATE           DEFAULT NULL,
    `progress_percentage`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `final_report_submitted_at` DATETIME      DEFAULT NULL,
    `notes`                    TEXT           DEFAULT NULL,
    `created_at`               DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`               DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_projects_code` (`project_code`),
    CONSTRAINT `fk_projects_proposal`  FOREIGN KEY (`proposal_id`)  REFERENCES `research_proposals`(`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_projects_approver`  FOREIGN KEY (`approved_by`)  REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: proposal_reviews
-- ============================================================
CREATE TABLE IF NOT EXISTS `proposal_reviews` (
    `id`                       INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `proposal_id`              INT UNSIGNED   NOT NULL,
    `reviewer_id`              INT UNSIGNED   NOT NULL,
    `assigned_date`            DATE           DEFAULT NULL,
    `due_date`                 DATE           DEFAULT NULL,
    `received_date`            DATE           DEFAULT NULL,
    `invitation_letter_number` VARCHAR(100)   DEFAULT NULL,
    `invitation_sent_date`     DATE           DEFAULT NULL,
    `invitation_file_path`     VARCHAR(500)   DEFAULT NULL,
    `review_result`            ENUM('pending','approved','approved_with_condition','rejected') NOT NULL DEFAULT 'pending',
    `review_score`             DECIMAL(5,2)   DEFAULT NULL,
    `review_comments`          TEXT           DEFAULT NULL,
    `payment_amount`           DECIMAL(15,2)  DEFAULT NULL,
    `payment_date`             DATE           DEFAULT NULL,
    `payment_status`           ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    `payment_reference`        VARCHAR(100)   DEFAULT NULL,
    `reminder_sent_count`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `last_reminder_sent_at`    DATETIME       DEFAULT NULL,
    `created_by`               INT UNSIGNED   NOT NULL,
    `created_at`               DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`               DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_review_proposal_reviewer` (`proposal_id`, `reviewer_id`),
    CONSTRAINT `fk_reviews_proposal`  FOREIGN KEY (`proposal_id`)  REFERENCES `research_proposals`(`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_reviewer`  FOREIGN KEY (`reviewer_id`)  REFERENCES `expert_reviewers`(`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_creator`   FOREIGN KEY (`created_by`)   REFERENCES `users`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED NOT NULL,
    `type`          VARCHAR(50)  NOT NULL,
    `title`         VARCHAR(255) NOT NULL,
    `message`       TEXT         NOT NULL,
    `related_table` VARCHAR(100) DEFAULT NULL,
    `related_id`    INT UNSIGNED DEFAULT NULL,
    `is_read`       TINYINT(1)  NOT NULL DEFAULT 0,
    `created_at`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user` (`user_id`),
    KEY `idx_notifications_read` (`is_read`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: activity_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED    DEFAULT NULL,
    `action`      VARCHAR(100)    NOT NULL,
    `table_name`  VARCHAR(100)    DEFAULT NULL,
    `record_id`   INT UNSIGNED    DEFAULT NULL,
    `old_value`   JSON            DEFAULT NULL,
    `new_value`   JSON            DEFAULT NULL,
    `ip_address`  VARCHAR(45)     DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_user`   (`user_id`),
    KEY `idx_activity_table`  (`table_name`, `record_id`),
    KEY `idx_activity_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Users: 2 admin + 2 executive
INSERT INTO `users` (`google_id`, `email`, `name`, `avatar`, `role`, `department`, `phone`, `is_active`, `last_login`, `created_at`) VALUES
('116423571092345678901', 'superadmin@psu.ac.th',  'ดร.สมชาย รักวิจัย',    'https://lh3.googleusercontent.com/a/default-user-1', 'superadmin', 'สำนักวิจัยและพัฒนา',         '074-289-000', 1, '2026-05-15 08:30:00', '2025-01-01 00:00:00'),
('104985621384720938472', 'admin.research@psu.ac.th','นางสาวมาลี จัดการดี',  'https://lh3.googleusercontent.com/a/default-user-2', 'admin',      'กองบริหารงานวิจัย',          '074-289-001', 1, '2026-05-15 09:00:00', '2025-01-05 00:00:00'),
('112738491826374918263', 'exec1@psu.ac.th',        'รศ.ดร.วิชัย มองการณ์ไกล','https://lh3.googleusercontent.com/a/default-user-3', 'executive',  'คณะวิศวกรรมศาสตร์',          '074-289-100', 1, '2026-05-14 14:00:00', '2025-01-10 00:00:00'),
('109283746192837461928', 'exec2@psu.ac.th',        'ผศ.ดร.สุดา วิเคราะห์เก่ง','https://lh3.googleusercontent.com/a/default-user-4','executive', 'คณะวิทยาศาสตร์และเทคโนโลยี','074-289-200', 1, '2026-05-13 10:00:00', '2025-01-12 00:00:00');

-- Funding Sources: 3 internal, 2 external
INSERT INTO `funding_sources` (`name`, `type`, `organization`, `description`, `budget_year`, `is_active`) VALUES
('ทุนวิจัยงบประมาณแผ่นดิน ม.อ.',             'internal', 'มหาวิทยาลัยสงขลานครินทร์',          'ทุนสนับสนุนการวิจัยจากงบประมาณแผ่นดิน ประจำปี 2568',                  2025, 1),
('ทุนพัฒนานักวิจัยรุ่นใหม่ ม.อ.',            'internal', 'มหาวิทยาลัยสงขลานครินทร์',          'ทุนสำหรับอาจารย์และนักวิจัยรุ่นใหม่ที่ต้องการพัฒนาความสามารถด้านวิจัย',2025, 1),
('ทุนวิจัยเพื่อพัฒนาท้องถิ่น',               'internal', 'มหาวิทยาลัยสงขลานครินทร์',          'ทุนสนับสนุนการวิจัยเพื่อตอบสนองความต้องการของชุมชนในภาคใต้',           2025, 1),
('ทุนวิจัยจาก วช.',                           'external', 'สำนักงานการวิจัยแห่งชาติ (วช.)',    'ทุนวิจัยจากแหล่งทุนภายนอก สำนักงานการวิจัยแห่งชาติ ประจำปีงบประมาณ 2568',2025, 1),
('ทุนวิจัยจากกองทุน PMU-B',                  'external', 'หน่วยบริหารและจัดการทุนด้านการพัฒนากำลังคนและทุนด้านการพัฒนาสถาบันอุดมศึกษา', 'ทุนวิจัยเพื่อพัฒนากำลังคนและสถาบัน', 2025, 1);

-- Fields of Study
INSERT INTO `fields_of_study` (`code`, `name_th`, `name_en`, `faculty`) VALUES
('ENG-CE',  'วิศวกรรมโยธา',                    'Civil Engineering',                      'คณะวิศวกรรมศาสตร์'),
('ENG-EE',  'วิศวกรรมไฟฟ้า',                   'Electrical Engineering',                 'คณะวิศวกรรมศาสตร์'),
('SCI-CS',  'วิทยาการคอมพิวเตอร์',             'Computer Science',                       'คณะวิทยาศาสตร์และเทคโนโลยี'),
('SCI-BIO', 'ชีววิทยา',                         'Biology',                                'คณะวิทยาศาสตร์และเทคโนโลยี'),
('MED-PH',  'สาธารณสุขศาสตร์',                 'Public Health',                          'คณะแพทยศาสตร์'),
('AGR-AG',  'เกษตรศาสตร์',                     'Agriculture',                            'คณะทรัพยากรธรรมชาติ'),
('SOC-EC',  'เศรษฐศาสตร์',                     'Economics',                              'คณะเศรษฐศาสตร์'),
('ENV-ES',  'วิทยาศาสตร์สิ่งแวดล้อม',          'Environmental Science',                  'คณะสิ่งแวดล้อมและทรัพยากรศาสตร์');

-- Expert Reviewers
INSERT INTO `expert_reviewers` (`title`, `first_name`, `last_name`, `expertise`, `institution`, `position`, `email`, `phone`, `bank_name`, `bank_account`, `bank_branch`, `id_card_number`, `address`, `is_active`) VALUES
('ศาสตราจารย์ ดร.', 'ประสิทธิ์',   'มั่นคง',     'วิศวกรรมโยธา, การวิเคราะห์โครงสร้าง, วัสดุก่อสร้าง',    'จุฬาลงกรณ์มหาวิทยาลัย',           'ศาสตราจารย์ประจำภาควิชาวิศวกรรมโยธา',         'prasit.m@chula.ac.th',     '081-234-5678', 'ธนาคารกรุงไทย',    '1234567890',  'สาขาจุฬา',        '1100100123456', '123 ถ.พระราม 4 แขวงวังใหม่ เขตปทุมวัน กรุงเทพฯ 10330', 1),
('รองศาสตราจารย์ ดร.', 'สมหมาย',  'ใจดี',       'วิทยาการคอมพิวเตอร์, ปัญญาประดิษฐ์, Machine Learning',   'มหาวิทยาลัยเชียงใหม่',             'รองศาสตราจารย์ภาควิชาวิทยาการคอมพิวเตอร์',    'sommai.j@cmu.ac.th',       '082-345-6789', 'ธนาคารกสิกรไทย',   '2345678901',  'สาขาเชียงใหม่',   '5301200234567', '456 ถ.ห้วยแก้ว ต.สุเทพ อ.เมือง จ.เชียงใหม่ 50200', 1),
('ผู้ช่วยศาสตราจารย์ ดร.', 'นิภา', 'สดใส',      'ชีววิทยา, นิเวศวิทยา, ความหลากหลายทางชีวภาพ',            'มหาวิทยาลัยขอนแก่น',               'ผู้ช่วยศาสตราจารย์ภาควิชาชีววิทยา',           'nipa.s@kku.ac.th',         '083-456-7890', 'ธนาคารไทยพาณิชย์', '3456789012',  'สาขาขอนแก่น',     '4000300345678', '789 ถ.มิตรภาพ ต.ในเมือง อ.เมือง จ.ขอนแก่น 40000', 1),
('ศาสตราจารย์', 'วีระพล',          'เก่งกาจ',   'สาธารณสุขศาสตร์, ระบาดวิทยา, สุขภาพชุมชน',               'มหาวิทยาลัยมหิดล',                 'ศาสตราจารย์ภาควิชาระบาดวิทยา',               'weerapon.k@mahidol.ac.th', '084-567-8901', 'ธนาคารกรุงเทพ',    '4567890123',  'สาขาศิริราช',     '1000400456789', '101 ถ.พุทธมณฑลสาย 4 ต.ศาลายา อ.พุทธมณฑล จ.นครปฐม 73170', 1),
('ดร.', 'อารีย์',                  'รักษ์ธรรมชาติ','วิทยาศาสตร์สิ่งแวดล้อม, การจัดการทรัพยากรน้ำ, GIS',    'มหาวิทยาลัยเกษตรศาสตร์',           'อาจารย์ภาควิชาวิทยาศาสตร์สิ่งแวดล้อม',        'aree.r@ku.ac.th',          '085-678-9012', 'ธนาคารออมสิน',    '5678901234',  'สาขาบางเขน',      '1009900567890', '50 ถ.งามวงศ์วาน แขวงลาดยาว เขตจตุจักร กรุงเทพฯ 10900', 1);

-- Research Proposals (3 proposals, different statuses)
INSERT INTO `research_proposals`
    (`proposal_code`, `title_th`, `title_en`, `principal_investigator_id`, `co_investigators`,
     `field_of_study_id`, `funding_source_id`, `budget_requested`, `budget_year`,
     `abstract`, `objectives`, `methodology`, `start_date`, `end_date`,
     `status`, `submitted_at`, `created_by`)
VALUES
(
    'PSU-2568-001',
    'การพัฒนาระบบบริหารจัดการน้ำอัจฉริยะสำหรับชุมชนในภาคใต้ของประเทศไทยโดยใช้เทคโนโลยี IoT',
    'Development of Smart Water Management System for Southern Thailand Communities Using IoT Technology',
    3, -- exec1 เป็น PI
    JSON_ARRAY(
        JSON_OBJECT('id', 4, 'name', 'ผศ.ดร.สุดา วิเคราะห์เก่ง', 'role', 'ผู้ร่วมวิจัย')
    ),
    1, -- ENG-CE
    4, -- วช.
    1500000.00, 2025,
    'งานวิจัยนี้มุ่งพัฒนาระบบบริหารจัดการน้ำอัจฉริยะโดยใช้เทคโนโลยี Internet of Things (IoT) เพื่อแก้ปัญหาการขาดแคลนน้ำและน้ำท่วมที่เกิดขึ้นบ่อยครั้งในชุมชนภาคใต้ของประเทศไทย โดยระบบจะทำการตรวจวัดระดับน้ำ ปริมาณฝน และคุณภาพน้ำแบบเรียลไทม์',
    'เพื่อพัฒนาระบบเซ็นเซอร์ IoT สำหรับตรวจวัดปริมาณน้ำและคุณภาพน้ำแบบเรียลไทม์\nเพื่อสร้างแบบจำลองการพยากรณ์น้ำท่วมและภัยแล้งสำหรับพื้นที่ภาคใต้\nเพื่อพัฒนาแอปพลิเคชันสำหรับแจ้งเตือนภัยพิบัติทางน้ำแก่ชุมชน',
    'การวิจัยนี้ใช้ระเบียบวิธีวิจัยเชิงประยุกต์ โดยแบ่งออกเป็น 3 ระยะ ได้แก่ (1) การศึกษาและออกแบบระบบ (2) การพัฒนาและทดสอบระบบในห้องปฏิบัติการ และ (3) การติดตั้งและทดสอบในพื้นที่จริง 3 ชุมชนนำร่องในจังหวัดสงขลา',
    '2025-06-01', '2027-05-31',
    'reviewing', '2026-03-01 10:00:00', 1
),
(
    'PSU-2568-002',
    'ผลของสารสกัดจากพืชสมุนไพรภาคใต้ต่อการยับยั้งเชื้อแบคทีเรียดื้อยาในโรงพยาบาล',
    'Effects of Southern Thai Medicinal Plant Extracts on Inhibition of Hospital-Acquired Drug-Resistant Bacteria',
    4, -- exec2 เป็น PI
    JSON_ARRAY(
        JSON_OBJECT('id', 3, 'name', 'รศ.ดร.วิชัย มองการณ์ไกล', 'role', 'ผู้ร่วมวิจัย')
    ),
    4, -- SCI-BIO
    1, -- ทุนงบประมาณแผ่นดิน
    800000.00, 2025,
    'การวิจัยนี้มุ่งศึกษาฤทธิ์ต้านเชื้อแบคทีเรียของสารสกัดจากพืชสมุนไพรที่พบในภาคใต้ของประเทศไทย จำนวน 10 ชนิด ต่อเชื้อแบคทีเรียดื้อยา (Antimicrobial Resistance: AMR) ที่พบในโรงพยาบาล เพื่อค้นหาสารสำคัญที่มีศักยภาพในการพัฒนาเป็นยาหรือผลิตภัณฑ์ทางการแพทย์',
    'เพื่อคัดเลือกพืชสมุนไพรที่มีฤทธิ์ต้านเชื้อแบคทีเรียดื้อยา\nเพื่อระบุสารสำคัญที่มีฤทธิ์ต้านเชื้อแบคทีเรียด้วยวิธี Chromatography\nเพื่อทดสอบความเป็นพิษต่อเซลล์มนุษย์ของสารสกัดที่คัดเลือก',
    'ใช้วิธี Disc diffusion และ Minimum Inhibitory Concentration (MIC) ในการทดสอบฤทธิ์ต้านเชื้อแบคทีเรีย จากนั้นทำการแยกสารและระบุโครงสร้างด้วย NMR และ Mass Spectrometry',
    '2025-07-01', '2027-06-30',
    'approved', '2026-02-15 14:00:00', 1
),
(
    'PSU-2568-003',
    'การประเมินศักยภาพการท่องเที่ยวเชิงนิเวศในพื้นที่ชายฝั่งทะเลอ่าวไทยตอนล่างเพื่อการพัฒนาที่ยั่งยืน',
    'Assessment of Ecotourism Potential in the Lower Gulf of Thailand Coastal Areas for Sustainable Development',
    3, -- exec1 เป็น PI
    NULL,
    8, -- ENV-ES
    3, -- ทุนวิจัยเพื่อพัฒนาท้องถิ่น
    450000.00, 2025,
    'การวิจัยนี้มุ่งประเมินศักยภาพและความพร้อมของพื้นที่ชายฝั่งทะเลอ่าวไทยตอนล่างในการพัฒนาการท่องเที่ยวเชิงนิเวศ โดยศึกษาทั้งมิติทรัพยากรธรรมชาติ สังคม เศรษฐกิจ และการมีส่วนร่วมของชุมชนท้องถิ่น',
    'เพื่อสำรวจและประเมินทรัพยากรธรรมชาติที่มีศักยภาพทางการท่องเที่ยวเชิงนิเวศในพื้นที่ชายฝั่ง\nเพื่อศึกษาความต้องการและความพึงพอใจของนักท่องเที่ยวและชุมชนท้องถิ่น\nเพื่อจัดทำแผนพัฒนาการท่องเที่ยวเชิงนิเวศอย่างยั่งยืน',
    'ใช้วิธีวิจัยแบบผสมผสาน (Mixed Methods Research) ประกอบด้วยการสำรวจภาคสนาม การสัมภาษณ์เชิงลึก การสนทนากลุ่ม และการวิเคราะห์ข้อมูลเชิงพื้นที่ด้วย GIS',
    '2025-08-01', '2026-07-31',
    'draft', NULL, 2
);

-- Research Projects (2 projects from approved proposals)
INSERT INTO `research_projects`
    (`proposal_id`, `project_code`, `status`, `approved_date`, `approved_budget`,
     `approved_by`, `contract_number`, `contract_date`, `actual_start_date`,
     `progress_percentage`, `notes`)
VALUES
(
    2, -- proposal PSU-2568-002
    'PROJ-2568-001',
    'in_progress',
    '2026-03-01',
    780000.00,
    1, -- superadmin อนุมัติ
    'PSU-CONTRACT-2568-001',
    '2026-03-15',
    '2026-04-01',
    35,
    'โครงการดำเนินงานตามแผนที่วางไว้ หัวหน้าโครงการรายงานความก้าวหน้าสม่ำเสมอ'
),
(
    1, -- proposal PSU-2568-001 (reviewing -> approved for demo)
    'PROJ-2568-002',
    'approved',
    '2026-04-01',
    1480000.00,
    1,
    'PSU-CONTRACT-2568-002',
    '2026-04-15',
    NULL,
    0,
    'รอการลงนามในสัญญากับชุมชนนำร่อง'
);

-- Proposal Reviews
INSERT INTO `proposal_reviews`
    (`proposal_id`, `reviewer_id`, `assigned_date`, `due_date`, `received_date`,
     `invitation_letter_number`, `invitation_sent_date`,
     `review_result`, `review_score`, `review_comments`,
     `payment_amount`, `payment_date`, `payment_status`, `payment_reference`,
     `reminder_sent_count`, `created_by`)
VALUES
(
    1, 1, -- proposal 1, reviewer Prasit
    '2026-03-05', '2026-03-25', '2026-03-22',
    'PSU-INV-2568-001', '2026-03-05',
    'approved_with_condition', 78.50,
    'ข้อเสนอโครงการมีความน่าสนใจและมีความเป็นไปได้สูง อย่างไรก็ตามควรเพิ่มเติมรายละเอียดด้านงบประมาณและควรชี้แจงวิธีการคัดเลือกพื้นที่นำร่องให้ชัดเจนยิ่งขึ้น',
    3000.00, '2026-04-01', 'paid', 'PAY-2568-001',
    1, 2
),
(
    1, 3, -- proposal 1, reviewer Nipa
    '2026-03-05', '2026-03-25', NULL,
    'PSU-INV-2568-002', '2026-03-05',
    'pending', NULL, NULL,
    3000.00, NULL, 'pending', NULL,
    2, 2
),
(
    2, 3, -- proposal 2, reviewer Nipa
    '2026-02-20', '2026-03-10', '2026-03-08',
    'PSU-INV-2568-003', '2026-02-20',
    'approved', 88.00,
    'ข้อเสนอโครงการมีคุณภาพดีมาก มีความชัดเจนในด้านวัตถุประสงค์ วิธีการวิจัย และงบประมาณ สมควรได้รับการอนุมัติ',
    3000.00, '2026-03-20', 'paid', 'PAY-2568-002',
    0, 2
),
(
    2, 4, -- proposal 2, reviewer Weerapon
    '2026-02-20', '2026-03-10', '2026-03-09',
    'PSU-INV-2568-004', '2026-02-20',
    'approved', 85.50,
    'งานวิจัยมีความสำคัญและทันสมัย ระเบียบวิธีวิจัยมีความเหมาะสม แนะนำให้ดำเนินการต่อ',
    3000.00, '2026-03-20', 'paid', 'PAY-2568-003',
    0, 2
);

-- Sample Notifications
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `related_table`, `related_id`, `is_read`) VALUES
(2, 'proposal_submitted',   'ข้อเสนอโครงการใหม่รอการตรวจสอบ',      'ข้อเสนอโครงการ PSU-2568-001 โดย รศ.ดร.วิชัย มองการณ์ไกล ส่งเข้ามาเพื่อรอการพิจารณา', 'research_proposals', 1, 1),
(2, 'proposal_submitted',   'ข้อเสนอโครงการใหม่รอการตรวจสอบ',      'ข้อเสนอโครงการ PSU-2568-002 โดย ผศ.ดร.สุดา วิเคราะห์เก่ง ส่งเข้ามาเพื่อรอการพิจารณา', 'research_proposals', 2, 1),
(3, 'proposal_approved',    'ข้อเสนอโครงการได้รับการอนุมัติ',       'ยินดีด้วย! ข้อเสนอโครงการ PSU-2568-002 ของท่านได้รับการอนุมัติแล้ว', 'research_proposals', 2, 0),
(3, 'review_reminder',      'เตือนความจำ: ผู้ทรงคุณวุฒิยังไม่ส่งผล','ผู้ทรงคุณวุฒิ ผศ.ดร.นิภา สดใส ยังไม่ได้ส่งผลการพิจารณาโครงการ PSU-2568-001', 'proposal_reviews', 2, 0),
(1, 'project_progress',     'อัพเดทความก้าวหน้าโครงการ',            'โครงการ PROJ-2568-001 มีความก้าวหน้า 35% แล้ว', 'research_projects', 1, 0);

-- Sample Activity Logs
INSERT INTO `activity_logs` (`user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`) VALUES
(1, 'CREATE', 'users',              2, NULL,                                          JSON_OBJECT('email','admin.research@psu.ac.th','role','admin'),              '127.0.0.1'),
(2, 'CREATE', 'research_proposals', 1, NULL,                                          JSON_OBJECT('proposal_code','PSU-2568-001','status','draft'),                '127.0.0.1'),
(2, 'UPDATE', 'research_proposals', 1, JSON_OBJECT('status','draft'),                 JSON_OBJECT('status','reviewing'),                                          '127.0.0.1'),
(2, 'CREATE', 'research_proposals', 2, NULL,                                          JSON_OBJECT('proposal_code','PSU-2568-002','status','draft'),                '127.0.0.1'),
(1, 'UPDATE', 'research_proposals', 2, JSON_OBJECT('status','reviewing'),             JSON_OBJECT('status','approved'),                                           '127.0.0.1'),
(1, 'CREATE', 'research_projects',  1, NULL,                                          JSON_OBJECT('project_code','PROJ-2568-001','proposal_id',2),                 '127.0.0.1'),
(2, 'CREATE', 'proposal_reviews',   1, NULL,                                          JSON_OBJECT('proposal_id',1,'reviewer_id',1,'assigned_date','2026-03-05'),   '127.0.0.1'),
(2, 'UPDATE', 'proposal_reviews',   1, JSON_OBJECT('review_result','pending'),        JSON_OBJECT('review_result','approved_with_condition','review_score',78.50), '127.0.0.1');
