/*
  Production schema changes for the Laravel 13 / Passport 13 upgrade (SQL Server).

  Equivalent to running these migrations, which must NOT be run with `php artisan migrate`
  on production:
    2024_06_01_000001_create_oauth_device_codes_table
    2026_10_22_000001_add_two_factor_confirmed_at_to_users_table
    2026_10_22_000002_upgrade_oauth_clients_table_for_passport_13

  The script is idempotent and runs in one transaction. Take a database backup first.
  Run it in SSMS or with sqlcmd against the application database, then run
  `php artisan passport:hash` from the application folder (hashing needs PHP).
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;
BEGIN TRANSACTION;

------------------------------------------------------------------------
-- 1. users.two_factor_confirmed_at
------------------------------------------------------------------------
IF COL_LENGTH('dbo.users', 'two_factor_confirmed_at') IS NULL
BEGIN
    ALTER TABLE dbo.users ADD two_factor_confirmed_at DATETIME NULL;
    PRINT 'users.two_factor_confirmed_at added';
END
ELSE
    PRINT 'users.two_factor_confirmed_at already present';

------------------------------------------------------------------------
-- 2. oauth_device_codes (new in Passport 13; integer client ids are kept)
------------------------------------------------------------------------
IF OBJECT_ID('dbo.oauth_device_codes', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.oauth_device_codes (
        id               NCHAR(80)      NOT NULL,
        user_id          BIGINT         NULL,
        client_id        BIGINT         NOT NULL,
        user_code        NCHAR(8)       NOT NULL,
        scopes           NVARCHAR(MAX)  NOT NULL,
        revoked          BIT            NOT NULL,
        user_approved_at DATETIME       NULL,
        last_polled_at   DATETIME       NULL,
        expires_at       DATETIME       NULL,
        CONSTRAINT oauth_device_codes_id_primary PRIMARY KEY (id),
        CONSTRAINT oauth_device_codes_user_code_unique UNIQUE (user_code)
    );
    CREATE INDEX oauth_device_codes_user_id_index   ON dbo.oauth_device_codes (user_id);
    CREATE INDEX oauth_device_codes_client_id_index ON dbo.oauth_device_codes (client_id);
    PRINT 'oauth_device_codes created';
END
ELSE
    PRINT 'oauth_device_codes already present';

------------------------------------------------------------------------
-- 3. oauth_clients: Passport 10 layout -> Passport 13 layout
--    user_id                                -> owner_type / owner_id
--    redirect (comma separated)             -> redirect_uris (JSON array)
--    personal_access_client/password_client -> grant_types (JSON array)
------------------------------------------------------------------------
IF COL_LENGTH('dbo.oauth_clients', 'user_id') IS NOT NULL
BEGIN
    ALTER TABLE dbo.oauth_clients ADD
        owner_type    NVARCHAR(255) NULL,
        owner_id      BIGINT        NULL,
        redirect_uris NVARCHAR(MAX) NULL,
        grant_types   NVARCHAR(MAX) NULL;

    -- Dynamic SQL so the UPDATE compiles after the columns exist in this batch.
    -- Mirrors the legacy accessors in Laravel\Passport\Client (redirectUris / grantTypes).
    EXEC(N'
        UPDATE dbo.oauth_clients SET
            owner_id   = user_id,
            owner_type = CASE WHEN user_id IS NOT NULL THEN N''App\Models\User'' END,
            redirect_uris = CASE
                WHEN redirect IS NULL OR LTRIM(RTRIM(redirect)) = '''' THEN N''[]''
                ELSE N''["'' + REPLACE(redirect, '','', ''","'') + N''"]''
            END,
            grant_types = N''['' + STUFF(
                  CASE WHEN redirect IS NOT NULL AND LTRIM(RTRIM(redirect)) <> ''''              THEN N'',"authorization_code"'' ELSE N'''' END
                + CASE WHEN secret IS NOT NULL AND secret <> '''' AND user_id IS NULL             THEN N'',"client_credentials"''  ELSE N'''' END
                + CASE WHEN redirect IS NOT NULL AND LTRIM(RTRIM(redirect)) <> ''''              THEN N'',"implicit"''            ELSE N'''' END
                + CASE WHEN password_client = 1                                                   THEN N'',"password"''            ELSE N'''' END
                + CASE WHEN personal_access_client = 1 AND secret IS NOT NULL AND secret <> ''''  THEN N'',"personal_access"''     ELSE N'''' END
                + N'',"refresh_token"''
                + N'',"urn:ietf:params:oauth:grant-type:device_code"''
              , 1, 1, N'''') + N'']''
    ');

    -- Drop indexes that reference the legacy columns before dropping them.
    DECLARE @sql NVARCHAR(MAX) = N'';
    SELECT @sql = @sql + N'DROP INDEX ' + QUOTENAME(i.name) + N' ON dbo.oauth_clients;'
    FROM sys.indexes i
    JOIN sys.index_columns ic ON ic.object_id = i.object_id AND ic.index_id = i.index_id
    JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
    WHERE i.object_id = OBJECT_ID('dbo.oauth_clients')
      AND i.is_primary_key = 0
      AND c.name IN ('user_id', 'redirect', 'personal_access_client', 'password_client');
    IF @sql <> N'' EXEC sp_executesql @sql;

    -- Drop default constraints on the legacy columns, if any.
    SET @sql = N'';
    SELECT @sql = @sql + N'ALTER TABLE dbo.oauth_clients DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';'
    FROM sys.default_constraints dc
    JOIN sys.columns c ON c.object_id = dc.parent_object_id AND c.column_id = dc.parent_column_id
    WHERE dc.parent_object_id = OBJECT_ID('dbo.oauth_clients')
      AND c.name IN ('user_id', 'redirect', 'personal_access_client', 'password_client');
    IF @sql <> N'' EXEC sp_executesql @sql;

    ALTER TABLE dbo.oauth_clients DROP COLUMN user_id, redirect, personal_access_client, password_client;
    ALTER TABLE dbo.oauth_clients ALTER COLUMN redirect_uris NVARCHAR(MAX) NOT NULL;
    ALTER TABLE dbo.oauth_clients ALTER COLUMN grant_types   NVARCHAR(MAX) NOT NULL;
    CREATE INDEX oauth_clients_owner_type_owner_id_index ON dbo.oauth_clients (owner_type, owner_id);
    PRINT 'oauth_clients converted to the Passport 13 layout';
END
ELSE
    PRINT 'oauth_clients already in the Passport 13 layout';

------------------------------------------------------------------------
-- 4. Record the migrations so a future `php artisan migrate` skips them
------------------------------------------------------------------------
DECLARE @batch INT = (SELECT ISNULL(MAX(batch), 0) + 1 FROM dbo.migrations);

INSERT INTO dbo.migrations (migration, batch)
SELECT m.name, @batch
FROM (VALUES
    ('2016_06_01_000001_create_oauth_auth_codes_table'),
    ('2016_06_01_000002_create_oauth_access_tokens_table'),
    ('2016_06_01_000003_create_oauth_refresh_tokens_table'),
    ('2016_06_01_000004_create_oauth_clients_table'),
    ('2024_06_01_000001_create_oauth_device_codes_table'),
    ('2026_10_22_000001_add_two_factor_confirmed_at_to_users_table'),
    ('2026_10_22_000002_upgrade_oauth_clients_table_for_passport_13')
) AS m(name)
WHERE NOT EXISTS (SELECT 1 FROM dbo.migrations x WHERE x.migration = m.name);
PRINT CONCAT(@@ROWCOUNT, ' migration record(s) added');

COMMIT TRANSACTION;
GO

-- Sanity check (separate batch: the columns above do not exist when the first batch is compiled)
SELECT id, name, owner_type, owner_id, grant_types, redirect_uris, revoked FROM dbo.oauth_clients;
