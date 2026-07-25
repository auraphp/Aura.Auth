-- PostgreSQL schema for Aura.Auth.
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
    active   VARCHAR(1)   NOT NULL DEFAULT 'y'
);
CREATE INDEX accounts_username ON accounts (username);

CREATE TABLE aura_auth_remember (
    selector         VARCHAR(32)  NOT NULL PRIMARY KEY,
    hashed_validator VARCHAR(64)  NOT NULL,
    username         VARCHAR(255) NOT NULL,
    userdata         TEXT         NULL,
    expires          INTEGER      NOT NULL
);
CREATE INDEX aura_auth_remember_username ON aura_auth_remember (username);

CREATE TABLE aura_auth_token (
    selector         VARCHAR(32)  NOT NULL PRIMARY KEY,
    hashed_validator VARCHAR(64)  NOT NULL,
    username         VARCHAR(255) NOT NULL,
    userdata         TEXT         NULL,
    label            VARCHAR(255) NULL,
    expires          INTEGER      NOT NULL,
    created_at       INTEGER      NOT NULL,
    last_used_at     INTEGER      NULL
);
CREATE INDEX aura_auth_token_username ON aura_auth_token (username);

CREATE TABLE aura_auth_throttle (
    id           SERIAL       PRIMARY KEY,
    throttle_key VARCHAR(255) NOT NULL,
    attempted_at INTEGER      NOT NULL
);
CREATE INDEX aura_auth_throttle_key ON aura_auth_throttle (throttle_key, attempted_at);
