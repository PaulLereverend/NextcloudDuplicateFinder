<?php
namespace OCA\DuplicateFinder\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version0000Date20210325183200 extends SimpleMigrationStep {

  /**
  * @param IOutput $output
  * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
  * @param array $options
  * @return null|ISchemaWrapper
  */
  public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
    /** @var ISchemaWrapper $schema */
    $schema = $schemaClosure();

    if (!$schema->hasTable('duplicatefinder_finfo')) {
      $table = $schema->createTable('duplicatefinder_finfo');
      $table->addColumn('id', 'integer', [
        'autoincrement' => true,
        'notnull' => true,
      ]);
      $table->addColumn('owner', 'string', [
        'notnull' => true,
        'length' => 200,
      ]);
      $table->addColumn('path', 'text', [
        'notnull' => true,
      ]);
      /**
       * Column to store the hash of file
       * This erases the need to calculate the hash on every time the app is opend
       */
      $table->addColumn('file_hash', 'text', [
        'notnull' => false,
      ]);

      /**
       * Column to store the hash of an image (without exif data)
       */
      $table->addColumn('image_hash', 'text', [
        'notnull' => false
      ]);

      /** Stores the time where the hash is calculated */
     $table->addColumn('updated_at', 'integer', [
      'notnull' => false
     ]);

      $table->setPrimaryKey(['id']);
      $table->addIndex(['path'], 'duplicatefinder_path_idx');
      $table->addIndex(['file_hash'], 'duplicatefinder_hashes_idx');
    }
    $schema = $this->createDuplicatesTable($schema);
    $schema = $this->createDuplicatesRelationTable($schema);
    return $schema;
  }

  private function createDuplicatesTable(ISchemaWrapper $schema) {
    if (!$schema->hasTable('duplicatefinder_dups')) {
      $table = $schema->createTable('duplicatefinder_dups');
      $table->addColumn('id', 'integer', [
        'autoincrement' => true,
        'notnull' => true,
      ]);
      $table->addColumn('type', 'string', [
        'notnull' => true,
        'length' => 200,
      ]);
      $table->addColumn('hash', 'text', [
        'notnull' => true,
      ]);
      $table->addColumn('file_count', 'integer', [
        'notnull' => true,
        'default' => 0
      ]);
      $table->addColumn('count_per_user', 'text', [
        'notnull' => false,
        'default' => '{}'
      ]);
      $table->setPrimaryKey(['id']);
      $table->addIndex(['hash'], 'duplicatefinder_dh_idx');
    }
    return $schema;
  }

  private function createDuplicatesRelationTable(ISchemaWrapper $schema) {
    if (!$schema->hasTable('duplicatefinder_dups_f')) {
      $table = $schema->createTable('duplicatefinder_dups_f');
      $table->addColumn('id', 'integer', [
        'notnull' => true,
      ]);
      $table->addColumn('rid', 'integer', [
        'notnull' => true
      ]);
      $table->addColumn('value', 'string', [
        'notnull' => false,
        'length' => 200
      ]);
      $table->setPrimaryKey(['id', 'rid']);
    }
    return $schema;
  }
}
