<?php
namespace Air\Log;

use Air\Air;
use InvalidArgumentException;
use Monolog\Logger as MonologLogger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    /**
     * The Monolog logger instance.
     *
     * @var \Monolog\Logger
     */
    private $monolog;

    /**
     * The Log levels.
     *
     * @var array
     */
    private $levels = [
        'debug'     => MonologLogger::DEBUG,
        'info'      => MonologLogger::INFO,
        'notice'    => MonologLogger::NOTICE,
        'warning'   => MonologLogger::WARNING,
        'error'     => MonologLogger::ERROR,
        'critical'  => MonologLogger::CRITICAL,
        'alert'     => MonologLogger::ALERT,
        'emergency' => MonologLogger::EMERGENCY,
    ];

    /**
     * Create a new log writer instance
     *
     * Logger constructor.
     * @param Air $air
     * @throws \Exception
     */
    public function __construct(Air $air)
    {
        $this->monolog = new MonologLogger($air->get('config')->get('app.env'));

        $this->useFiles(
            $air->getLogsFilePath(),
            $air->get('config')->get('log.level')
        );
    }

    /**
     * Log an emergency message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an alert message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a critical message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an error message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a warning message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a notice to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an informational message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a debug message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a message to the logs.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->writeLog($level, $message, $context);
    }

    /**
     * Dynamically pass log calls into the writer.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function write($level, $message, array $context = [])
    {
        $this->writeLog($level, $message, $context);
    }

    /**
     * Write a message to Monolog.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    private function writeLog($level, $message, $context)
    {
        $this->monolog->{$level}($message, $context);
    }

    /**
     * Register a file log handler.
     *
     * @param $path
     * @param string $level
     * @throws \Exception
     */
    private function useFiles($path, $level = 'debug')
    {
        $this->monolog->pushHandler($handler = new StreamHandler($path, $this->parseLevel($level)));

        $handler->setFormatter($this->getDefaultFormatter());
    }

    /**
     * Register a daily file log handler.
     *
     * @param  string  $path
     * @param  int     $days
     * @param  string  $level
     * @return void
     */
    private function useDailyFiles($path, $days = 0, $level = 'debug')
    {
        $this->monolog->pushHandler(
            $handler = new RotatingFileHandler($path, $days, $this->parseLevel($level))
        );

        $handler->setFormatter($this->getDefaultFormatter());
    }

    /**
     * Register a Syslog handler.
     *
     * @param  string  $name
     * @param  string  $level
     * @param  mixed  $facility
     * @return \Psr\Log\LoggerInterface
     */
    private function useSyslog($name = 'air', $level = 'debug', $facility = LOG_USER)
    {
        return $this->monolog->pushHandler(new SyslogHandler($name, $facility, $level));
    }

    /**
     * Register an error_log handler.
     *
     * @param  string  $level
     * @param  int  $messageType
     * @return void
     */
    private function useErrorLog($level = 'debug', $messageType = ErrorLogHandler::OPERATING_SYSTEM)
    {
        $this->monolog->pushHandler(new ErrorLogHandler($messageType, $this->parseLevel($level)));
    }

    /**
     * Parse the string level into a Monolog constant.
     *
     * @param  string  $level
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    private function parseLevel($level)
    {
        if (isset($this->levels[$level])) {
            return $this->levels[$level];
        }

        throw new InvalidArgumentException('Invalid log level.');
    }

    /**
     * Get the underlying Monolog instance.
     *
     * @return \Monolog\Logger
     */
    public function getMonolog()
    {
        return $this->monolog;
    }

    /**
     * Get a default Monolog formatter instance.
     *
     * @return \Monolog\Formatter\LineFormatter
     */
    private function getDefaultFormatter()
    {
        $formatter = new LineFormatter(null, null, true, true);
        $formatter->includeStacktraces();

        return $formatter;
    }
}