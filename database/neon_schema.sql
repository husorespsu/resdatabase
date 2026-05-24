-- ============================================================
-- PSU Research Management System
-- PostgreSQL Schema (Neon)
-- ============================================================

-- Trigger function: auto-update updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          SERIAL PRIMARY KEY,
    google_id   VARCHAR(255) DEFAULT NULL,
    username    VARCHAR(50)  DEFAULT NULL,
    password    VARCHAR(255) DEFAULT NULL,
    email       VARCHAR(255) NOT NULL,
    name        VARCHAR(255) NOT NULL,
    avatar      VARCHAR(500) DEFAULT NULL,
    role        VARCHAR(20)  NOT NULL DEFAULT 'executive'
                    CHECK (role IN ('superadmin','admin','executive')),
    department  VARCHAR(255) DEFAULT NULL,
    phone       VARCHAR(20)  DEFAULT NULL,
    is_active   BOOLEAN      NOT NULL DEFAULT TRUE,
    last_login  TIMESTAMP    DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_users_email     UNIQUE (email),
    CONSTRAINT uq_users_google_id UNIQUE (google_id),
    CONSTRAINT uq_users_username  UNIQUE (username)
);
CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================
-- TABLE: funding_sources
-- ============================================================
CREATE TABLE IF NOT EXISTS funding_sources (
    id           SERIAL PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    type         VARCHAR(20)  NOT NULL DEFAULT 'internal'
                     CHECK (type IN ('internal','external')),
    organization VARCHAR(255) DEFAULT NULL,
    description  TEXT         DEFAULT NULL,
    budget_year  SMALLINT     DEFAULT NULL,
    is_active    BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at   TIMESTAMP    NOT NULL DEFAULT NOW()
);
CREATE TRIGGER trg_funding_sources_updated_at
    BEFORE UPDATE ON funding_sources
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================
-- TABLE: fields_of_study
-- ============================================================
CREATE TABLE IF NOT EXISTS fields_of_study (
    id         SERIAL PRIMARY KEY,
    code       VARCHAR(20)  NOT NULL,
    name_th    VARCHAR(255) NOT NULL,
    name_en    VARCHAR(255) NOT NULL,
    faculty    VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_fields_code UNIQUE (code)
);

-- ============================================================
-- TABLE: expert_reviewers
-- ============================================================
CREATE TABLE IF NOT EXISTS expert_reviewers (
    id              SERIAL PRIMARY KEY,
    title           VARCHAR(50)  DEFAULT NULL,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    expertise       VARCHAR(500) DEFAULT NULL,
    institution     VARCHAR(255) DEFAULT NULL,
    position        VARCHAR(255) DEFAULT NULL,
    email           VARCHAR(255) DEFAULT NULL,
    phone           VARCHAR(20)  DEFAULT NULL,
    bank_name       VARCHAR(100) DEFAULT NULL,
    bank_account    VARCHAR(50)  DEFAULT NULL,
    bank_branch     VARCHAR(100) DEFAULT NULL,
    id_card_number  VARCHAR(20)  DEFAULT NULL,
    address         TEXT         DEFAULT NULL,
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP    NOT NULL DEFAULT NOW()
);
CREATE TRIGGER trg_expert_reviewers_updated_at
    BEFORE UPDATE ON expert_reviewers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================
-- TABLE: research_proposals
-- ============================================================
CREATE TABLE IF NOT EXISTS research_proposals (
    id                         SERIAL PRIMARY KEY,
    proposal_code              VARCHAR(50)   NOT NULL,
    title_th                   VARCHAR(1000) NOT NULL,
    title_en                   VARCHAR(1000) DEFAULT NULL,
    principal_investigator_id  INTEGER       DEFAULT NULL
        REFERENCES users(id) ON DELETE SET NULL,
    pi_name                    VARCHAR(500)  DEFAULT NULL,
    co_investigators           JSONB         DEFAULT NULL,
    field_of_study_id          INTEGER       DEFAULT NULL
        REFERENCES fields_of_study(id) ON DELETE SET NULL,
    funding_source_id          INTEGER       DEFAULT NULL
        REFERENCES funding_sources(id) ON DELETE SET NULL,
    budget_requested           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    budget_year                SMALLINT      NOT NULL,
    abstract                   TEXT          DEFAULT NULL,
    objectives                 TEXT          DEFAULT NULL,
    methodology                TEXT          DEFAULT NULL,
    start_date                 DATE          DEFAULT NULL,
    end_date                   DATE          DEFAULT NULL,
    status                     VARCHAR(20)   NOT NULL DEFAULT 'draft'
        CHECK (status IN ('draft','reviewing','approved','rejected')),
    attachment_path            VARCHAR(500)  DEFAULT NULL,
    submitted_at               TIMESTAMP     DEFAULT NULL,
    approved_by                INTEGER       DEFAULT NULL
        REFERENCES users(id) ON DELETE SET NULL,
    approved_at                TIMESTAMP     DEFAULT NULL,
    notes                      TEXT          DEFAULT NULL,
    created_by                 INTEGER       NOT NULL
        REFERENCES users(id),
    created_at                 TIMESTAMP     NOT NULL DEFAULT NOW(),
    updated_at                 TIMESTAMP     NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_proposals_code UNIQUE (proposal_code)
);
CREATE TRIGGER trg_research_proposals_updated_at
    BEFORE UPDATE ON research_proposals
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================
-- TABLE: research_projects
-- ============================================================
CREATE TABLE IF NOT EXISTS research_projects (
    id                         SERIAL PRIMARY KEY,
    proposal_id                INTEGER       NOT NULL
        REFERENCES research_proposals(id),
    project_code               VARCHAR(50)   NOT NULL,
    status                     VARCHAR(20)   NOT NULL DEFAULT 'approved'
        CHECK (status IN ('approved','in_progress','completed','closed','cancelled')),
    approved_date              DATE          DEFAULT NULL,
    approved_budget            DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    approved_by                INTEGER       DEFAULT NULL
        REFERENCES users(id) ON DELETE SET NULL,
    contract_number            VARCHAR(100)  DEFAULT NULL,
    contract_date              DATE          DEFAULT NULL,
    actual_start_date          DATE          DEFAULT NULL,
    actual_end_date            DATE          DEFAULT NULL,
    progress_percentage        SMALLINT      NOT NULL DEFAULT 0,
    final_report_submitted_at  TIMESTAMP     DEFAULT NULL,
    notes                      TEXT          DEFAULT NULL,
    created_at                 TIMESTAMP     NOT NULL DEFAULT NOW(),
    updated_at                 TIMESTAMP     NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_projects_code UNIQUE (project_code)
);
CREATE TRIGGER trg_research_projects_updated_at
    BEFORE UPDATE ON research_projects
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================
-- TABLE: proposal_reviews
-- ============================================================
CREATE TABLE IF NOT EXISTS proposal_reviews (
    id                       SERIAL PRIMARY KEY,
    proposal_id              INTEGER       NOT NULL
        REFERENCES research_proposals(id),
    reviewer_id              INTEGER       NOT NULL
        REFERENCES expert_reviewers(id),
    assigned_date            DATE          DEFAULT NULL,
    due_date                 DATE          DEFAULT NULL,
    received_date            DATE          DEFAULT NULL,
    invitation_letter_number VARCHAR(100)  DEFAULT NULL,
    invitation_sent_date     DATE          DEFAULT NULL,
    invitation_file_path     VARCHAR(500)  DEFAULT NULL,
    review_result            VARCHAR(30)   NOT NULL DEFAULT 'pending'
        CHECK (review_result IN ('pending','approved','approved_with_condition','rejected')),
    review_score             DECIMAL(5,2)  DEFAULT NULL,
    review_comments          TEXT          DEFAULT NULL,
    payment_amount           DECIMAL(15,2) DEFAULT NULL,
    payment_date             DATE          DEFAULT NULL,
    payment_status           VARCHAR(10)   NOT NULL DEFAULT 'pending'
        CHECK (payment_status IN ('pending','paid')),
    payment_reference        VARCHAR(100)  DEFAULT NULL,
    reminder_sent_count      SMALLINT      NOT NULL DEFAULT 0,
    last_reminder_sent_at    TIMESTAMP     DEFAULT NULL,
    created_by               INTEGER       NOT NULL
        REFERENCES users(id),
    created_at               TIMESTAMP     NOT NULL DEFAULT NOW(),
    updated_at               TIMESTAMP     NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_review_proposal_reviewer UNIQUE (proposal_id, reviewer_id)
);
CREATE TRIGGER trg_proposal_reviews_updated_at
    BEFORE UPDATE ON proposal_reviews
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id            SERIAL PRIMARY KEY,
    user_id       INTEGER      NOT NULL
        REFERENCES users(id) ON DELETE CASCADE,
    type          VARCHAR(50)  NOT NULL,
    title         VARCHAR(255) NOT NULL,
    message       TEXT         NOT NULL,
    related_table VARCHAR(100) DEFAULT NULL,
    related_id    INTEGER      DEFAULT NULL,
    is_read       BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMP    NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_read ON notifications(is_read);

-- ============================================================
-- TABLE: activity_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id          BIGSERIAL    PRIMARY KEY,
    user_id     INTEGER      DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,
    table_name  VARCHAR(100) DEFAULT NULL,
    record_id   INTEGER      DEFAULT NULL,
    old_value   JSONB        DEFAULT NULL,
    new_value   JSONB        DEFAULT NULL,
    ip_address  VARCHAR(45)  DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_activity_user   ON activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_table  ON activity_logs(table_name, record_id);
CREATE INDEX IF NOT EXISTS idx_activity_action ON activity_logs(action);

-- ============================================================
-- TABLE: huso_personnel
-- ============================================================
CREATE TABLE IF NOT EXISTS huso_personnel (
    id         SERIAL PRIMARY KEY,
    full_name  VARCHAR(500) NOT NULL,
    department VARCHAR(300) DEFAULT NULL,
    position   VARCHAR(300) DEFAULT NULL,
    email      VARCHAR(200) DEFAULT NULL,
    dept_id    INTEGER      DEFAULT NULL,
    dept_type  VARCHAR(20)  DEFAULT NULL
        CHECK (dept_type IN ('undergraduate','graduate','doctoral')),
    created_at TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP    NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_huso_full_name ON huso_personnel(full_name);
CREATE TRIGGER trg_huso_personnel_updated_at
    BEFORE UPDATE ON huso_personnel
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
