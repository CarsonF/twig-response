<?php declare(strict_types=1);

namespace Gmo\Web;

use Silex;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Application extends Silex\Application
{
    /**
     * {@inheritdoc}
     */
    public function run(SymfonyRequest $request = null)
    {
        parent::run($request ? Request::cast($request) : Request::createFromGlobals());
    }

    /**
     * {@inheritdoc}
     */
    public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        return parent::handle(Request::cast($request), $type, $catch);
    }
}
