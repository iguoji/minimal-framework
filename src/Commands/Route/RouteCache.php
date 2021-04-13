<?php
declare(strict_types=1);

namespace Minimal\Commands\Route;

use Throwable;
use RuntimeException;
use Minimal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 路由缓存
 */
class RouteCache extends Command
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
        $this->setName('route:cache')
            ->setDescription('Reload route cache');
    }

    /**
     * 命令执行
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {

            $this->app->route->reset()->readRouteFiles(glob($this->app->routePath('*.php')));

        } catch (Throwable $th) {
            $output->writeln('<error>' . $th->getMessage() . '</error>');
            return Command::FAILURE;
        }

        // 返回结果
        return Command::SUCCESS;
    }
}