<?php
require_once __DIR__.'/../vendor/autoload.php';

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    die("include composer autoload.php fail\n");
}

$version = phpversion('swoole');
if (version_compare($version, '4.2.6', '<')) {
    die("the swoole extension version must be >= 4.2.6 (current: {$version})\n");
}

class Run
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
}

$commandList = $argv;
array_shift($commandList);

$mainCommand = array_shift($commandList);

try {
    $air = new Air\Air(realpath(__DIR__ . '/../'));
    $config = $air->make('config');

    Run::createDir($air);

    switch ($mainCommand) {
        case 'start' : {
            if ($config->get('sw.tcp.enable')) {
                Run::showTag('tcp server', $config->get('app.name'));
                Run::showTag('listen address', $config->get('sw.tcp.bind'));
                Run::showTag('listen port', $config->get('sw.tcp.port'));
            }

            if ($config->get('sw.http.enable')) {
                Run::showTag('http server', $config->get('app.name'));
                Run::showTag('listen address', $config->get('sw.http.bind'));
                Run::showTag('listen port', $config->get('sw.http.port'));
            }

            foreach (swoole_get_local_ip() as $eth => $val) {
                Run::showTag('ip@'.$eth, $val);
            }

            $bg = array_shift($commandList);
            if ($bg === 'bg') {
                $config->set('sw.set.daemonize', 1);

                /**! 主进程号文件 !**/
                $config->set('sw.set.pid_file', $air->getRuntimeDirPath().DIRECTORY_SEPARATOR.'pid.pid');
            }

            Run::showTag('worker num', $config->get('sw.set.worker_num'));
            Run::showTag('task worker num', $config->get('sw.set.task_worker_num'));
            Run::showTag('run at user', get_current_user());
            Run::showTag('daemonize', $config->get('sw.set.daemonize') ? 'true' : 'false');
            Run::showTag('swoole version', $version);
            Run::showTag('php version', phpversion());

            $air::server('sw')->run();
            break;
        }

        case 'stop' : {
            if (is_file($pid = $air->getRuntimeDirPath().DIRECTORY_SEPARATOR.'pid.pid')) {
                $processNumber = intval(file_get_contents($pid));
                swoole_process::kill($processNumber);

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
            if(in_array('all', $commandList)){
                $all = true;
            }

            if (is_file($pid = $air->getRuntimeDirPath().DIRECTORY_SEPARATOR.'pid.pid')) {
                if (!$all) {
                    if (!$config->get('sw.set.task_worker_num')) {
                        Run::showTag('reload error', 'no task run');
                        return;
                    }

                    $sig = SIGUSR2;
                    Run::showTag('reloadType', 'only-task');
                } else {
                    $sig = SIGUSR1;

                    Run::showTag('reloadType', 'all-worker');
                }

                Run::cacheClear();

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

        default : {
            Run::showTag('Available commands', 'start stop reload');

            break;
        }
    }
} catch (\Throwable $ex) {
    die($ex->getMessage()."\n");
}
