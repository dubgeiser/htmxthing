<?php

namespace htmxthing;

use PDO;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

/**
 * Aftelklok web application
 *
 * Small web app to show a countdown timer for a given date.
 * Can be hit with an url like:
 * https://website.com/<basedir>/<year>/<month>/<day>/<hour>/<minute>/<second>
 * year, month and day are required; the application will throw an exception if
 * missing.
 * day, hour, minute are all optional.
 *
 * Code wise, Application takes in a configuration as an associative array.
 * See the doc comments of the constructor.
 * See Application::$defaultConf to see the default configuration values.
 */
class Application
{
    const INITIAL_NUMBER_OF_PEOPLE = 1000;

    private array $conf = [
        'db' => [
            'dsn' => 'mysql:dbname=test;socket=/tmp/mysql.sock',
            'user' => '',
            'pass' => ''
        ],
        'debug' => true,
        'template_dir' => __DIR__ . '/../../templates',
        'template_cache_dir' => __DIR__ . '/../../templates/cached_templates',
    ];
    private Environment $tpl;

    private Storage $storage;

    public function __construct(array $conf = [])
    {
        foreach ($conf as $k => $v) {
            $this->conf[$k] = $v;
        }
    }

    public function run(): void
    {
        $this->setupStorage();
        $this->setupTemplating();
        $this->setupRouting();
    }

    public function index(Request $request): Response
    {
        return $this->peopleList('index.twig.html');
    }

    public function people(Request $request): Response
    {
        return $this->peopleList('people.twig.html');
    }

    public function person(Request $request): Response
    {
        switch($request->getMethod()) {
            case "PUT":
                $this->storage->savePerson([
                    'name' => $request->get("name"),
                    'email' => $request->get("email"),
                    'id' => $request->get("id"),
                ]);
            // Intentional fall-through, since same rendering is used as
            // normal GET when updating a person.
            case "GET":
                return new Response(
                    $this->tpl->render('person.twig.html', [
                        'person' => $this->storage->getPerson((int)$request->get('id'))
                    ])
                );
            case "DELETE":
                $this->storage->deletePerson($request->get("id"));
                return $this->peopleCountChanged("");
        }
    }

    public function personAdd(Request $request): Response
    {
        if ($request->getMethod() == "POST") {
            $id = $this->storage->addPerson([
                'name' => $request->get("name"),
                'email' => $request->get("email"),
            ]);
            return $this->peopleCountChanged(
                $this->tpl->render('person.twig.html', [
                    'person' => $this->storage->getPerson($id)
                ])
            );
        }
        return new Response($this->tpl->render('person-add.twig.html'));
    }

    public function personEdit(Request $request): Response
    {
        return new Response(
            $this->tpl->render('person-edit.twig.html', [
                'person' => $this->storage->getPerson((int)$request->get('id'))
            ])
        );
    }

    public function initDb(): Response
    {
        $this->storage->seedDatabase(self::INITIAL_NUMBER_OF_PEOPLE);
        return $this->peopleCountChanged("Database seeded");
    }

    public function peopleCount(Request $request): Response
    {
        return new Response($this->storage->getPeopleCount());
    }

    private function peopleList(string $template): Response
    {
        return new Response($this->tpl->render($template, [
            'people' => $this->storage->getPeople(),
            'people_count' => $this->storage->getPeopleCount(),
        ]));
    }

    private function peopleCountChanged(string $content): Response
    {
        $r = new Response($content);
        $r->headers->add(["HX-Trigger" => "people_changed"]);
        return $r;
    }

    private function getRoutes(): RouteCollection
    {
        $routes = new RouteCollection();
        $routes->add("home", new Route("/", ["_controller" => [$this, "index"]]));
        $routes->add("people", new Route("/people", ["_controller" => [$this, "people"]]));
        $routes->add("person", new Route("/person/{id}", ["_controller" => [$this, "person"]], ['id' => '\d+']));
        $routes->add(
            "person_edit",
            new Route("/person/{id}/edit", ["_controller" => [$this, "personEdit"]], ['id' => '\d+'])
        );
        $routes->add(
            "person_add",
            new Route("/person/add", ["_controller" => [$this, "personAdd"]])
        );
        $routes->add("initdb", new Route("/initdb", ["_controller" => [$this, "initDb"]]));
        $routes->add("people_count", new Route("/people_count", ["_controller" => [$this, "peopleCount"]]));
        return $routes;
    }

    private function setupRouting(): void
    {
        $routes = $this->getRoutes();
        $request = Request::createFromGlobals();
        $matcher = new UrlMatcher($routes, new RequestContext());
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RouterListener($matcher, new RequestStack()));
        $controllerResolver = new ControllerResolver();
        $argumentResolver = new ArgumentResolver();
        $kernel = new HttpKernel($dispatcher, $controllerResolver, new RequestStack(), $argumentResolver);
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
    }

    private function setupTemplating(): void
    {
        $this->tpl = new Environment(
            new FilesystemLoader($this->conf['template_dir']),
            [
                'debug' => $this->conf['debug'],
                'cache' => $this->conf['template_cache_dir'],
            ]
        );
    }

    private function setupStorage(): void
    {
        $this->storage = new Storage(
            new PDO($this->conf['db']['dsn'], $this->conf['db']['user'], $this->conf['db']['pass'])
        );
    }
}
