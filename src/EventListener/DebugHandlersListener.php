<?php

declare(strict_types=1);

namespace Gmo\Web\EventListener;

use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\EventListener\DebugHandlersListener as BaseListener;

/**
 * Extended for CLI changes.
 * We allow verbosity output to be set so the exception trace can be printed.
 * We also erase the output buffer so Symfony\Component\Debug\ExceptionHandler's HTML isn't dumped.
 */
class DebugHandlersListener extends BaseListener
{
    private $cliPrintTrace;
    private $firstCall = true;

    public function __construct(bool $cliPrintTrace)
    {
        $this->cliPrintTrace = $cliPrintTrace;
        parent::__construct(null);
    }

    public function configure(Event $event = null)
    {
        if (!$this->firstCall) {
            return;
        }
        $this->firstCall = false;

        if ($event instanceof ConsoleEvent && $app = $event->getCommand()->getApplication()) {
            $output = $event->getOutput();
            if ($output instanceof ConsoleOutputInterface) {
                $output = $output->getErrorOutput();
            }

            $verbosity = $this->cliPrintTrace ? OutputInterface::VERBOSITY_VERBOSE : OutputInterface::VERBOSITY_NORMAL;

            $handler = function ($e) use ($app, $output, $verbosity) {
                $prevVerbosity = $output->getVerbosity();
                $output->setVerbosity($verbosity);

                $app->renderException($e, $output);
                ob_clean(); // Don't output HTML

                $output->setVerbosity($prevVerbosity);
            };

            // Set on parent
            (function () use ($handler) {
                $this->exceptionHandler = $handler;
            })->bindTo($this, BaseListener::class)();
        }

        parent::configure($event);
    }
}
