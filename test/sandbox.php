<?php

require_once __DIR__ . '/../vendor/autoload.php';

class User {
    /**
     * @var array
     */
    private $attributes;

    /**
     * Constructs a new user object.
     *
     * @param array $attributes
     */
    private function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Creates a new user object.
     *
     * @param array $attributes
     * @return User
     */
    public static function create($attributes)
    {
        return new User($attributes);
    }

    /**
     * Gets an attribute off the user object.
     *
     * @param string $attribute
     * @return mixed|void
     */
    public function __get($attribute)
    {
        if (array_key_exists($this->attributes, $attribute))
        {
            return $this->attributes[$attribute];
        }
    }
}

interface IUserRepository {
    /**
     * @return User[]
     */
    public function getAll();
}

class MockUserRepository implements IUserRepository {
    /**
     * @return User[]
     */
    public function getAll()
    {
        return [
            User::create([
                'name' => 'jacob',
                'email' => 'jacob+whatever@manage.net.nz',
                'password' => '1234'
            ]),
            User::create([
                'name' => 'support',
                'email' => 'support@manage.net.nz',
                'password' => '1234'
            ])
        ];
    }
}

//

class HomeController {
    /**
     * @var IUserRepository
     */
    protected $users;

    /**
     * A constructor that depends on a user repository.
     *
     * @param IUserRepository $users
     */
    public function __construct(IUserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * GET /
     */
    public function index()
    {
        var_dump($this->users->getAll());

        return 'Hello World';
    }
}

//

$ioc = new Marshall\IoC\Container();

$ioc->bind('IUserRepository', 'MockUserRepository');

$controller = $ioc->create('HomeController');
$method = $ioc->invoke($controller, 'index');

var_dump($method);
