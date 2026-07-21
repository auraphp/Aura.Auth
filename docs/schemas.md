# Database Schemas

The SQL below creates every table Aura.Auth can use. It is not a single
required schema: take the tables for the features you actually use.

- `accounts` is an **example**. `PdoAdapter` reads whatever table and columns
  you configure it with, so an existing user table needs no changes -- see
  [Adapters](adapters.md). It is included here because the PDO integration
  tests need something to authenticate against.
- The `aura_auth_*` tables are fixed by their storage classes, though each
  takes its table name as a constructor argument. Use them only if you use the
  corresponding feature: [Remember Me](remember-me.md), [API
  Tokens](api-tokens.md), [Login Throttling](throttling.md).

These statements are not written out by hand for the documentation. They are
the files the `pdo-integration` CI job executes before running the test suite
against real MySQL and PostgreSQL servers, so a schema that does not work is a
failed build rather than a bug report.

## SQLite

Source: `tests/integration/pdo/sqlite.sql`

```sql
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
    id           INTEGER      NOT NULL PRIMARY KEY AUTOINCREMENT,
    throttle_key VARCHAR(255) NOT NULL,
    attempted_at INTEGER      NOT NULL
);
CREATE INDEX aura_auth_throttle_key ON aura_auth_throttle (throttle_key, attempted_at);
```

## MySQL / MariaDB

Source: `tests/integration/pdo/mysql.sql`

```sql
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
```

## PostgreSQL

Source: `tests/integration/pdo/pgsql.sql`

```sql
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
```

## Keeping The Throttle And Token Tables Tidy

The throttle, remember-me, and token tables accumulate rows that are no longer
useful. Each storage class has a `deleteExpired()` for a cron task to call; see
the housekeeping notes in [Login Throttling](throttling.md) and [API
Tokens](api-tokens.md).
