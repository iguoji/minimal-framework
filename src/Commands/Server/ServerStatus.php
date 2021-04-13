<?php
declare(strict_types=1);

namespace Minimal\Commands\Server;

use Throwable;
use Minimal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 服务器状态
 */
class ServerStatus extends Command
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        parent::__construct();
    }

    /**
     * 命令配置
     */
    protected function configure() : void
    {
        $this->setName('server:status')
            ->setDescription('Show current running server status')
            ->addOption('target', null, InputOption::VALUE_OPTIONAL, 'look config/server.php array key', 'default');
    }

    /**
     * 命令执行
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            // 获取服务器
            $server = $this->app->server;
            // 保存配置
            $server->setConfig($this->app->config->get('server', []));
            // 切换服务器
            $server->use($input->getOption('target'));
            // 显示状态
            if ($server->status()) {
                $output->writeln('<info>The server has started</info>');
            } else {
                $output->writeln('<error>The server not already running</error>');
            }
        } catch (Throwable $th) {
            $output->writeln('<error>' . $th->getMessage() . '</error>');
            return Command::FAILURE;
        }

        // 返回结果
        return Command::SUCCESS;
    }
}