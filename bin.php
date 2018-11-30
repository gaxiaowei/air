<?php
/**! 此文件为包含文件 在真实的文件中必须定义常量 ROOT 代表的是项目根目录 !**/

if (!defined('ROOT')) {
    die("Undefined the project root directory \n");
}

require_once __DIR__ . '/vendor/autoload.php';

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("include composer autoload.php fail\n");
}

$version = phpversion('swoole');
if (version_compare($version, '4.2.6', '<')) {
    die("the swoole extension version must be >= 4.2.6 (current: {$version})\n");
}

$commandList = $argv;
$scriptName = array_shift($commandList);

class AirBin
{
    public static function showTag($name, $value)
    {
        echo "\e[32m" . str_pad($name, 20, ' ', STR_PAD_RIGHT) . "\e[34m" . $value . "\e[0m\n";
    }

    public static function cacheClear()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    public static function createDir(\Air\Air $air)
    {
        if (!is_dir($air->getAppDirPath())) {
            mkdir($air->getAppDirPath(), 755);
        }

        if (!is_dir($air->getConfigDirPath())) {
            mkdir($air->getConfigDirPath(), 755);
        }

        if (!is_dir($air->getRoutesDirPath())) {
            mkdir($air->getRoutesDirPath(), 755);
        }

        if (!is_dir($air->getRuntimeDirPath())) {
            mkdir($air->getRuntimeDirPath(), 755);
        }

        if (!is_dir($air->getLogsDirPath())) {
            mkdir($air->getLogsDirPath(), 755);
        }
    }

    public static function showHelpForStart()
    {
        echo <<<HELP_START
\e[33m操作:\e[0m
\e[31m  {$GLOBALS['scriptName']} start\e[0m
\e[33m简介:\e[0m
\e[36m  执行本命令可以启动框架 可选的操作参数如下\e[0m
\e[33m参数:\e[0m
\e[32m  -d \e[0m                   以守护模式启动框架

HELP_START;
    }

    public static function showHelpForStop()
    {
        echo <<<HELP_STOP
\e[33m操作:\e[0m
\e[31m  {$GLOBALS['scriptName']} stop\e[0m
\e[33m简介:\e[0m
\e[36m  执行本命令可以停止框架 可选的操作参数如下\e[0m
\e[33m参数:\e[0m
\e[32m  -force \e[0m             强制停止服务

HELP_STOP;
    }

    public static function showHelpForReload()
    {
        echo <<<HELP_RELOAD
\e[33m操作:\e[0m
\e[31m  {$GLOBALS['scriptName']} reload\e[0m
\e[33m简介:\e[0m
\e[36m  执行本命令可以重启所有Worker 可选的操作参数如下\e[0m
\e[33m参数:\e[0m
\e[32m  -all \e[0m           重启所有worker和task_worker进程

HELP_RELOAD;
    }

    public static function showHelp()
    {
        echo <<<DEFAULTHELP
\e[33m使用:\e[0m  [脚本文件] [操作] [选项]

\e[33m操作:\e[0m
\e[32m  start \e[0m        启动服务
\e[32m  stop \e[0m         停止服务
\e[32m  reload \e[0m       重载服务
\e[32m  help \e[0m         查看命令的帮助信息\n
\e[31m有关某个操作的详细信息 请使用\e[0m help \e[31m命令查看 \e[0m
\e[31m如查看\e[0m start \e[31m操作的详细信息 请输入\e[0m {$GLOBALS['scriptName']} help start\n\n
DEFAULTHELP;
    }
}

$mainCommand = array_shift($commandList);

try {
    $air = new Air\Air(ROOT);
    $config = $air->make('config');

    AirBin::createDir($air);

    switch ($mainCommand) {
        case 'start' : {
            if ($config->get('sw.tcp.enable')) {
                AirBin::showTag('tcp server', $config->get('app.name'));
                AirBin::showTag('listen address', $config->get('sw.tcp.bind'));
                AirBin::showTag('listen port', $config->get('sw.tcp.port'));
            }

            if ($config->get('sw.http.enable')) {
                AirBin::showTag('http server', $config->get('app.name'));
                AirBin::showTag('listen address', $config->get('sw.http.bind'));
                AirBin::showTag('listen port', $config->get('sw.http.port'));
            }

            foreach (swoole_get_local_ip() as $eth => $val) {
                AirBin::showTag('ip@'.$eth, $val);
            }

            /**! 守护进程运行 !**/
            if (in_array('-d', $commandList)) {
                $config->set('sw.set.daemonize', 1);
                $config->set('sw.set.pid_file', $air->getRuntimeDirPath().DIRECTORY_SEPARATOR.'pid.pid');
                $config->set('sw.set.log_file', $air->getRuntimeDirPath().DIRECTORY_SEPARATOR.'sw.log');
            }

            AirBin::showTag('worker num', $config->get('sw.set.worker_num'));
            AirBin::showTag('task worker num', $config->get('sw.set.task_worker_num'));
            AirBin::showTag('run at user', get_current_user());
            AirBin::showTag('daemonize', $config->get('sw.set.daemonize') ? 'true' : 'false');
            AirBin::showTag('swoole version', $version);
            AirBin::showTag('php version', phpversion());

            $air::server('sw')->run();
            break;
        }

        case 'stop' : {
            if (is_file($pid = $air->getRuntimeDirPath().DIRECTORY_SEPARATOR.'pid.pid')) {
                $processNumber = intval(file_get_contents($pid));
                if (!swoole_process::kill($processNumber, 0)) {
                    echo "PID :{$pid} not exist \n";
                    return false;
                }

                swoole_process::kill($processNumber, in_array('-force', $commandList) ? SIGKILL : SIGTERM);

                /**! 等待5秒 !**/
                $time = time();
                $flag = false;
                while (true) {
                    usleep(1000);
                    if (!swoole_process::kill($processNumber, 0)) {
                        echo "server stop at " . date("y-m-d h:i:s") . "\n";

                        @unlink($pid);
                        $flag = true;
                        break;
                    } else {
                        if (time() - $time > 5) {
                            echo "stop server fail.try again \n";
                            break;
                        }
                    }
                }
            } else {
                echo "PID file does not exist, please check whether to run in the daemon mode!\n";
            }

            break;
        }

        case 'reload' : {
            $all = false;
            if (in_array('-all', $commandList)) {
                $all = true;
            }

            if (is_file($pid = $air->getRuntimeDirPath().DIRECTORY_SEPARATOR.'pid.pid')) {
                if (!$all) {
                    if (!$config->get('sw.set.task_worker_num')) {
                        AirBin::showTag('reload error', 'no task run');
                        return;
                    }

                    $sig = SIGUSR2;
                    AirBin::showTag('reloadType', 'only-task-worker');
                } else {
                    $sig = SIGUSR1;

                    AirBin::showTag('reloadType', 'all-worker');
                }

                AirBin::cacheClear();

                $processNumber = intval(file_get_contents($pid));
                if (!swoole_process::kill($processNumber, 0)) {
                    echo "pid :{$pid} not exist \n";
                    return;
                }

                swoole_process::kill($processNumber, $sig);
                echo "send server reload command at " . date("y-m-d h:i:s") . "\n";
            } else {
                echo "PID file does not exist, please check whether to run in the daemon mode!\n";
            }

            break;
        }

        case 'help':
        default:{
            $com = array_shift($commandList);
            if ($com == 'start'){
                AirBin::showHelpForStart();
            } elseif ($com == 'stop'){
                AirBin::showHelpForStop();
            } elseif ($com == 'reload'){
                AirBin::showHelpForReload();
            } else {
                AirBin::showHelp();
            }
            break;
        }
    }
} catch (\Throwable $ex) {
    die($ex->getMessage()."\n");
}