<?php


namespace Modules\customer\MiddleWare;


use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\App;

class DenyNotLogged
{
    protected $app;
    protected $session;

    public function __construct(App $app, SessionInterface $session)
    {
        $this->app = $app;
        $this->session = $session;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        if (!$this->session->has('customer.login') || (!$this->session->get('customer.login') == true)) {
            $finaluri = (string) $this->app->getBasePath() . '/';
            return $response->withHeader('Location', $finaluri)->withStatus(302);
        }
        return $response;
    }
}