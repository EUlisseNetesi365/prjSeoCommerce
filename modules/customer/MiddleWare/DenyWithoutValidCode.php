<?php


namespace Modules\customer\MiddleWare;


use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\App;
use Slim\Routing\RouteContext;


class DenyWithoutValidCode
{
    protected $app;
    protected $session;
    protected $db;

    public function __construct(App $app, DBDefault $db, SessionInterface $session)
    {
        $this->app = $app;
        $this->session = $session;
        $this->db = $db;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $codeValid = $route->getArgument('code');
        $db = $this->db;
        $confirm = $this->db->get('customer_emails', 'confirmation_code',
            [
                'confirmation_code' => $codeValid
            ]);
        if (!$confirm) {
            $uriHost = (string) $this->app->getBasePath() . '/';
            return $response->withHeader('Location', $uriHost)->withStatus(302);
        }
        return $response;
    }
}