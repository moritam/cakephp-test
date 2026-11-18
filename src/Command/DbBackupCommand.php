<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;

/**
 * SQLite データベースをバックアップする
 *
 *     bin/cake db backup                         # TMP/backups/ に保存し、新しい 14 個を残す
 *     bin/cake db backup --dir /data/backups --keep 30
 *
 * アプリを止めずに安全にコピーするため、SQLite の VACUUM INTO を使う。
 */
class DbBackupCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'db backup';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser.
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription('SQLite データベースのバックアップを作成します。')
            ->addOption('dir', [
                'help' => '保存先のディレクトリ (既定: BACKUP_DIR 環境変数、なければ tmp/backups)',
                'default' => null,
            ])
            ->addOption('keep', [
                'help' => '残すバックアップの数 (古いものから削除。0 なら削除しない)',
                'default' => '14',
            ])
            ->addOption('connection', [
                'help' => '対象の接続名',
                'default' => 'default',
            ]);
    }

    /**
     * @param \Cake\Console\Arguments $args The arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $connection = ConnectionManager::get((string)$args->getOption('connection'));
        if (!$connection instanceof Connection || !$connection->getDriver() instanceof Sqlite) {
            $io->error('SQLite の接続だけに対応しています。');

            return static::CODE_ERROR;
        }

        $dir = (string)($args->getOption('dir') ?: (env('BACKUP_DIR') ?: TMP . 'backups'));
        $dir = rtrim($dir, DS);
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            $io->error("保存先を作成できません: {$dir}");

            return static::CODE_ERROR;
        }

        $file = $dir . DS . 'todo_app-' . DateTime::now()->format('Ymd-His') . '.sqlite';
        if (file_exists($file)) {
            $io->error("同名のファイルがあります: {$file}");

            return static::CODE_ERROR;
        }

        $connection->execute('VACUUM INTO ?', [$file]);
        chmod($file, 0600);
        $io->success(sprintf('バックアップを作成しました: %s (%d bytes)', $file, filesize($file)));

        $keep = (int)$args->getOption('keep');
        if ($keep > 0) {
            $files = glob($dir . DS . 'todo_app-*.sqlite') ?: [];
            rsort($files);
            foreach (array_slice($files, $keep) as $old) {
                unlink($old);
                $io->out("古いバックアップを削除しました: {$old}");
            }
        }

        return static::CODE_SUCCESS;
    }
}
