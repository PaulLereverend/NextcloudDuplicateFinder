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
             * Column to store other hashes than the file hash
             * For some mimetypes you may would like to use special algorithms do find dupplicats.
             * e.g. for images the binary stream is relavant but not the exif data.
             * The column contains a json to store different algorithms
             */
            $table->addColumn('other_hashes', 'text', [
                'notnull' => false,
                'default' => '{}'
            ]);

            /** Stores the time where the hash is calculated */
            $table->addColumn('updated_at', 'integer', [
                 'notnull' => false,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['path'], 'duplicatefinder_path_idx');
            $table->addIndex(['file_hash'], 'duplicatefinder_hashes_idx');
        }
        return $schema;
    }
}
