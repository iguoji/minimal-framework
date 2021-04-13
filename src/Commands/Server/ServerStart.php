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
 * 开启服务
 */
class ServerStart extends Command
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
        $this->setName('server:start')
            ->setDescription('Start a new server')
            ->addOption('target', null, InputOption::VALUE_OPTIONAL, 'look config/server.php array key', 'default');
    }

    /**
     * 命令执行
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            // 获取服务
            $server = $this->app->server;
            // 保存配置
            $server->setConfig($this->app->config->get('server', []));
            // 切换服务
            $server->use($input->getOption('target'));
            // 启动服务
            $server->start();
        } catch (Throwable $th) {
            $output->writeln('<error>' . $th->getMessage() . '</error>');
            return Command::FAILURE;
        }

        // 返回结果
        return Command::SUCCESS;
    }
}