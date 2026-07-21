-- MySQL / MariaDB schema for Aura.Auth.
--
-- This file is executed by the pdo-integration CI job and published verbatim
-- in docs/schemas.md, so the documented schema is the one that is tested.
--
-- The `accounts` table is an example: PdoAdapter reads whatever table and
-- columns you configure it with. The `aura_auth_*` tables are fixed by their
-- storage classes, though each takes the table name as a constructor argument.

CREATE TABLE accounts (
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    active   VARCHAR(1)   NOT NULL DEFAULT 'y',
    INDEX accounts_username (username)
);

CREATE TABLE aura_auth_remember (
    selector         VARCHAR(32)  NOT NULL PRIMARY KEY,
    hashed_validator VARCHAR(64)  NOT NULL,
    username         VARCHAR(255) NOT NULL,
    userdata         TEXT         NULL,
    expires          INT          NOT NULL,
    INDEX aura_auth_remember_username (username)
);

CREATE TABLE aura_auth_token (
    selector         VARCHAR(32)  NOT NULL PRIMARY KEY,
    hashed_validator VARCHAR(64)  NOT NULL,
    username         VARCHAR(255) NOT NULL,
    userdata         TEXT         NULL,
    label            VARCHAR(255) NULL,
    expires          INT          NOT NULL,
    created_at       INT          NOT NULL,
    last_used_at     INT          NULL,
    INDEX aura_auth_token_username (username)
);

CREATE TABLE aura_auth_throttle (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    throttle_key VARCHAR(255) NOT NULL,
    attempted_at INT          NOT NULL,
    INDEX aura_auth_throttle_key (throttle_key, attempted_at)
);
