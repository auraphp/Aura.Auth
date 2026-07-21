<?php
namespace Aura\Auth;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * docs/schemas.md publishes the same DDL the pdo-integration CI job runs, so
 * that the documented schema is a tested one. That only holds while the two
 * agree: editing a .sql file without updating the page would leave the docs
 * describing a schema nothing verifies.
 */
class SchemaDocsTest extends \PHPUnit\Framework\TestCase
{
    public static function schemaFileProvider()
    {
        return array(
            'sqlite' => array('sqlite'),
            'mysql' => array('mysql'),
            'pgsql' => array('pgsql'),
        );
    }

    #[DataProvider('schemaFileProvider')]
    public function testDocumentedSchemaMatchesTheOneCiRuns($driver)
    {
        $root = dirname(__DIR__);

        $sql = file_get_contents("{$root}/tests/integration/pdo/{$driver}.sql");
        // the page carries its own prose, so the leading comment block is
        // stripped when it is generated
        $sql = trim(preg_replace('/\A(--[^\n]*\n)+\n?/', '', $sql));

        $docs = file_get_contents("{$root}/docs/schemas.md");

        $this->assertStringContainsString(
            $sql,
            $docs,
            "docs/schemas.md does not contain the current tests/integration/pdo/{$driver}.sql."
                . ' Regenerate the page after changing the schema.'
        );
    }

    public function testEveryStorageTableIsDocumented()
    {
        $docs = file_get_contents(dirname(__DIR__) . '/docs/schemas.md');

        $tables = array(
            'accounts',
            'aura_auth_remember',
            'aura_auth_token',
            'aura_auth_throttle',
        );

        foreach ($tables as $table) {
            $this->assertStringContainsString(
                "CREATE TABLE {$table}",
                $docs,
                "docs/schemas.md has no CREATE TABLE for '{$table}'."
            );
        }
    }
}
