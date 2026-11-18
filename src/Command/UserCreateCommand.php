<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * ユーザーを作成する
 *
 * 新規登録を止めた本番環境 (REGISTRATION_ENABLED=false) で、自分のアカウントを作るために使う。
 *
 *     bin/cake user create you@example.com
 *
 * パスワードは対話的に 2 回入力する (端末では入力内容を表示しない)。
 */
class UserCreateCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'user create';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser.
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription('ユーザーを作成します (新規登録を止めている環境向け)。')
            ->addArgument('email', [
                'help' => 'ログインに使うメールアドレス',
                'required' => true,
            ]);
    }

    /**
     * @param \Cake\Console\Arguments $args The arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $email = (string)$args->getArgument('email');
        $password = $this->askSecret($io, 'パスワード (8 文字以上)');
        $confirm = $this->askSecret($io, 'パスワード (確認)');
        if ($password !== $confirm) {
            $io->error('パスワードが一致しません。');

            return static::CODE_ERROR;
        }

        $users = $this->fetchTable('Users');
        $user = $users->newEntity(['email' => $email, 'password' => $password]);
        if (!$users->save($user)) {
            foreach ($user->getErrors() as $field => $errors) {
                foreach ($errors as $message) {
                    $io->error("{$field}: {$message}");
                }
            }

            return static::CODE_ERROR;
        }

        $io->success("ユーザーを作成しました: {$email}");

        return static::CODE_SUCCESS;
    }

    /**
     * 入力内容を表示せずに尋ねる (端末でない場合は通常の入力)
     *
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @param string $prompt 質問文
     * @return string
     */
    protected function askSecret(ConsoleIo $io, string $prompt): string
    {
        $isTty = function_exists('posix_isatty') && posix_isatty(STDIN);
        if ($isTty) {
            shell_exec('stty -echo');
        }
        try {
            $value = $io->ask($prompt);
        } finally {
            if ($isTty) {
                shell_exec('stty echo');
                $io->out('');
            }
        }

        return $value;
    }
}
