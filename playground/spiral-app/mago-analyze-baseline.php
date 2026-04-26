variant = "loose"

[[issues]]
file = "src/Application/Kernel.php"
code = "impossible-assignment"
message = "Invalid assignment: the right-hand side has type `never` and cannot produce a value."
count = 4

[[issues]]
file = "src/Application/Kernel.php"
code = "invalid-iterator"
message = "The expression provided to `foreach` is not iterable. It resolved to type `mixed`, which is not iterable."
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Http\Message\ResponseInterface)`).'
count = 5

[[issues]]
file = "src/Application/Kernel.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Http\Server\RequestHandlerInterface)`).'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Spiral\Core\Container)`).'
count = 44

[[issues]]
file = "src/Application/Kernel.php"
code = "invalid-return-statement"
message = 'Invalid return type for function `app\application\kernel::buildpipeline`: expected `unknown-ref(Psr\Http\Server\RequestHandlerInterface)`, but found `App\Application\MiddlewarePipeline`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `App\Application\Kernel::emit`: expected `unknown-ref(Psr\Http\Message\ResponseInterface)`, but found `mixed`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `App\Application\TracingPdo::setcollector`: expected `null|unknown-ref(AppDevPanel\Kernel\Collector\DatabaseCollector)`, but found `mixed`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `app\application\pathrouter::__construct`: expected `unknown-ref(Psr\Http\Message\ResponseFactoryInterface)`, but found `mixed`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `mixed`."
count = 2

[[issues]]
file = "src/Application/Kernel.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #3 of `app\application\pathrouter::__construct`: expected `unknown-ref(Psr\Http\Message\StreamFactoryInterface)`, but found `mixed`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "mixed-argument"
message = "Invalid argument type for argument #3 of `sprintf`: expected `Stringable|null|scalar`, but found `mixed`."
count = 2

[[issues]]
file = "src/Application/Kernel.php"
code = "mixed-argument"
message = "Invalid argument type for argument #4 of `sprintf`: expected `Stringable|null|scalar`, but found `mixed`."
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "mixed-argument"
message = "The first value for `echo` is too general."
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 9

[[issues]]
file = "src/Application/Kernel.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 4

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class"
message = 'Class `AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader` not found.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class"
message = 'Class `Monolog\Handler\StreamHandler` not found.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class"
message = 'Class `Monolog\Logger` not found.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7Server\ServerRequestCreator` not found.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7\Factory\Psr17Factory` not found.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class"
message = 'Class `Spiral\Core\Container` not found.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ResponseInterface`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Server\MiddlewareInterface`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Server\RequestHandlerInterface`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Spiral\Core\Container`.'
count = 2

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class-like"
message = "Class `class@anonymous:8718526727076628416-4104:4531` cannot implement unknown type `ListenerProviderInterface`"
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class-like"
message = "Class `class@anonymous:8718526727076628416-4892:5373` cannot implement unknown type `EventDispatcherInterface`"
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "non-existent-class-like"
message = 'Class, interface, enum, or trait `Monolog\Level` not found.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "possibly-invalid-argument"
message = 'Possible argument type mismatch for argument #1 of `app\application\pathrouter::__construct`: expected `unknown-ref(Psr\Container\ContainerInterface)`, but possibly received `unknown-ref(Spiral\Core\Container)`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "possibly-invalid-argument"
message = 'Possible argument type mismatch for argument #1 of `class@anonymous:8718526727076628416-4892:5373::__construct`: expected `unknown-ref(Psr\EventDispatcher\ListenerProviderInterface)`, but possibly received `class@anonymous:8718526727076628416-4104:4531`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "possibly-invalid-argument"
message = 'Possible argument type mismatch for argument #2 of `app\application\middlewarepipeline::__construct`: expected `unknown-ref(Psr\Http\Server\RequestHandlerInterface)`, but possibly received `App\Application\PathRouter`.'
count = 1

[[issues]]
file = "src/Application/Kernel.php"
code = "unknown-iterator-type"
message = "Cannot determine the type of the expression provided to `foreach`."
count = 2

[[issues]]
file = "src/Application/LoopbackHttpClient.php"
code = "non-existent-class-like"
message = 'Class `App\Application\LoopbackHttpClient` cannot implement unknown type `ClientInterface`'
count = 1

[[issues]]
file = "src/Application/MiddlewarePipeline.php"
code = "non-existent-class-like"
message = 'Class `App\Application\MiddlewarePipeline` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Application/MiddlewarePipeline.php"
code = "unused-property"
message = "Property `$finalHandler` is never used."
count = 1

[[issues]]
file = "src/Application/MiddlewarePipeline.php"
code = "unused-property"
message = "Property `$middlewares` is never used."
count = 1

[[issues]]
file = "src/Application/PathRouter.php"
code = "non-existent-class-like"
message = 'Class `App\Application\PathRouter` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Application/PathRouter.php"
code = "unused-method"
message = "Method `feedroutercollector()` is never used."
count = 1

[[issues]]
file = "src/Application/PathRouter.php"
code = "unused-method"
message = "Method `namefor()` is never used."
count = 1

[[issues]]
file = "src/Application/PathRouter.php"
code = "unused-method"
message = "Method `normalize()` is never used."
count = 1

[[issues]]
file = "src/Application/PathRouter.php"
code = "unused-property"
message = "Property `$container` is never used."
count = 1

[[issues]]
file = "src/Application/PathRouter.php"
code = "unused-property"
message = "Property `$fallback` is never used."
count = 1

[[issues]]
file = "src/Application/PathRouter.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Application/PathRouter.php"
code = "unused-property"
message = "Property `$routes` is never used."
count = 1

[[issues]]
file = "src/Application/PathRouter.php"
code = "unused-property"
message = "Property `$streamFactory` is never used."
count = 1

[[issues]]
file = "src/Application/TracingPdo.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Collector\DatabaseCollector)`).'
count = 1

[[issues]]
file = "src/Application/TracingPdo.php"
code = "invalid-return-statement"
message = 'Invalid return type for function `app\application\tracingpdo::prepare`: expected `PDOStatement|false`, but found `App\Application\TracingPdoStatement`.'
count = 1

[[issues]]
file = "src/Application/TracingPdo.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "src/Application/TracingPdo.php"
code = "non-existent-class"
message = 'Class `AppDevPanel\Kernel\Collector\QueryRecord` not found.'
count = 1

[[issues]]
file = "src/Application/TracingPdo.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Collector\DatabaseCollector`.'
count = 2

[[issues]]
file = "src/Application/TracingPdo.php"
code = "redundant-cast"
message = "Redundant cast to `(string)`: the expression already has this type."
count = 1

[[issues]]
file = "src/Application/TracingPdoStatement.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Collector\DatabaseCollector)`).'
count = 1

[[issues]]
file = "src/Application/TracingPdoStatement.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "src/Application/TracingPdoStatement.php"
code = "non-existent-class"
message = 'Class `AppDevPanel\Kernel\Collector\QueryRecord` not found.'
count = 1

[[issues]]
file = "src/Application/TracingPdoStatement.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Collector\DatabaseCollector`.'
count = 1

[[issues]]
file = "src/Application/TracingPdoStatement.php"
code = "redundant-cast"
message = "Redundant cast to `(string)`: the expression already has this type."
count = 1

[[issues]]
file = "src/Application/TracingPdoStatement.php"
code = "string-member-selector"
message = "This member selector uses a non-literal string type (`string`); its specific value cannot be statically determined."
count = 3

[[issues]]
file = "src/Controller/TestFixtures/CacheAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Collector\CacheCollector)`).'
count = 3

[[issues]]
file = "src/Controller/TestFixtures/CacheAction.php"
code = "non-existent-class"
message = 'Class `AppDevPanel\Kernel\Collector\CacheOperationRecord` not found.'
count = 3

[[issues]]
file = "src/Controller/TestFixtures/CacheAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Collector\CacheCollector`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/CacheHeavyAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Collector\CacheCollector)`).'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/CacheHeavyAction.php"
code = "non-existent-class"
message = 'Class `AppDevPanel\Kernel\Collector\CacheOperationRecord` not found.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/CacheHeavyAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Collector\CacheCollector`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/DatabaseAction.php"
code = "invalid-method-access"
message = "Attempting to access a method on a non-object type (`false`)."
count = 5

[[issues]]
file = "src/Controller/TestFixtures/DatabaseAction.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "src/Controller/TestFixtures/DumpAction.php"
code = "non-existent-method"
message = 'Method `dump` does not exist on type `Symfony\Component\VarDumper\VarDumper`.'
count = 3

[[issues]]
file = "src/Controller/TestFixtures/EventsAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\EventDispatcher\EventDispatcherInterface)`).'
count = 3

[[issues]]
file = "src/Controller/TestFixtures/EventsAction.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 3

[[issues]]
file = "src/Controller/TestFixtures/EventsAction.php"
code = "mixed-property-access"
message = "Attempting to access a property on a non-object type (`mixed`)."
count = 3

[[issues]]
file = "src/Controller/TestFixtures/EventsAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\EventDispatcher\EventDispatcherInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/HttpClientAction.php"
code = "impossible-assignment"
message = "Invalid assignment: the right-hand side has type `never` and cannot produce a value."
count = 1

[[issues]]
file = "src/Controller/TestFixtures/HttpClientAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Http\Client\ClientInterface)`).'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/HttpClientAction.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "src/Controller/TestFixtures/HttpClientAction.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "src/Controller/TestFixtures/HttpClientAction.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7\Factory\Psr17Factory` not found.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/HttpClientAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Client\ClientInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/LogsAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Log\LoggerInterface)`).'
count = 5

[[issues]]
file = "src/Controller/TestFixtures/LogsAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Log\LoggerInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/LogsAction.php"
code = "non-existent-function"
message = 'Could not find definition for function `App\Controller\TestFixtures\dump` (also tried as `dump` in a broader scope).'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/LogsContextAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Log\LoggerInterface)`).'
count = 2

[[issues]]
file = "src/Controller/TestFixtures/LogsContextAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Log\LoggerInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/LogsHeavyAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Log\LoggerInterface)`).'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/LogsHeavyAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Log\LoggerInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/MailerAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Collector\MailerCollector)`).'
count = 2

[[issues]]
file = "src/Controller/TestFixtures/MailerAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Collector\MailerCollector`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/MultiAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\EventDispatcher\EventDispatcherInterface)`).'
count = 2

[[issues]]
file = "src/Controller/TestFixtures/MultiAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Log\LoggerInterface)`).'
count = 2

[[issues]]
file = "src/Controller/TestFixtures/MultiAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\EventDispatcher\EventDispatcherInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/MultiAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Log\LoggerInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/MultiAction.php"
code = "non-existent-function"
message = 'Could not find definition for function `App\Controller\TestFixtures\dump` (also tried as `dump` in a broader scope).'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/NotFoundAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Http\Message\ServerRequestInterface)`).'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/NotFoundAction.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 2

[[issues]]
file = "src/Controller/TestFixtures/NotFoundAction.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `app\controller\testfixtures\notfoundaction::__invoke`. Saw type `mixed`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/NotFoundAction.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7\Response` not found.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/NotFoundAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ResponseInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/NotFoundAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ServerRequestInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/NotFoundAction.php"
code = "non-existent-method"
message = 'Method `create` does not exist on type `Nyholm\Psr7\Stream`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/QueueAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Collector\QueueCollector)`).'
count = 3

[[issues]]
file = "src/Controller/TestFixtures/QueueAction.php"
code = "non-existent-class"
message = 'Class `AppDevPanel\Kernel\Collector\MessageRecord` not found.'
count = 3

[[issues]]
file = "src/Controller/TestFixtures/QueueAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Collector\QueueCollector`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/RequestInfoAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Http\Message\ServerRequestInterface)`).'
count = 5

[[issues]]
file = "src/Controller/TestFixtures/RequestInfoAction.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_keys`: expected `array<('K.array_keys() extends array-key), ('V.array_keys() extends mixed)>`, but found `mixed`."
count = 1

[[issues]]
file = "src/Controller/TestFixtures/RequestInfoAction.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 2

[[issues]]
file = "src/Controller/TestFixtures/RequestInfoAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ServerRequestInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/ResetAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Storage\StorageInterface)`).'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/ResetAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Storage\StorageInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/TimelineAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Log\LoggerInterface)`).'
count = 3

[[issues]]
file = "src/Controller/TestFixtures/TimelineAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Log\LoggerInterface`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/TranslatorAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Collector\TranslatorCollector)`).'
count = 5

[[issues]]
file = "src/Controller/TestFixtures/TranslatorAction.php"
code = "non-existent-class"
message = 'Class `AppDevPanel\Kernel\Collector\TranslationRecord` not found.'
count = 5

[[issues]]
file = "src/Controller/TestFixtures/TranslatorAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Collector\TranslatorCollector`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/ValidatorAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Collector\ValidatorCollector)`).'
count = 2

[[issues]]
file = "src/Controller/TestFixtures/ValidatorAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Collector\ValidatorCollector`.'
count = 1

[[issues]]
file = "src/Controller/TestFixtures/ViewAction.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Collector\TemplateCollector)`).'
count = 4

[[issues]]
file = "src/Controller/TestFixtures/ViewAction.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Collector\TemplateCollector`.'
count = 1

[[issues]]
file = "src/Controller/Web/ApiPlaygroundPage.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "src/Controller/Web/ApiPlaygroundPage.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `app\controller\web\apiplaygroundpage::__invoke`. Saw type `mixed`.'
count = 1

[[issues]]
file = "src/Controller/Web/ApiPlaygroundPage.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7\Response` not found.'
count = 1

[[issues]]
file = "src/Controller/Web/ApiPlaygroundPage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ResponseInterface`.'
count = 1

[[issues]]
file = "src/Controller/Web/ApiPlaygroundPage.php"
code = "non-existent-method"
message = 'Method `create` does not exist on type `Nyholm\Psr7\Stream`.'
count = 1

[[issues]]
file = "src/Controller/Web/ContactPage.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(AppDevPanel\Kernel\Collector\ValidatorCollector)`).'
count = 1

[[issues]]
file = "src/Controller/Web/ContactPage.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Http\Message\ServerRequestInterface)`).'
count = 2

[[issues]]
file = "src/Controller/Web/ContactPage.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "src/Controller/Web/ContactPage.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "src/Controller/Web/ContactPage.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `app\controller\web\contactpage::__invoke`. Saw type `mixed`.'
count = 1

[[issues]]
file = "src/Controller/Web/ContactPage.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7\Response` not found.'
count = 1

[[issues]]
file = "src/Controller/Web/ContactPage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `AppDevPanel\Kernel\Collector\ValidatorCollector`.'
count = 1

[[issues]]
file = "src/Controller/Web/ContactPage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ResponseInterface`.'
count = 1

[[issues]]
file = "src/Controller/Web/ContactPage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ServerRequestInterface`.'
count = 1

[[issues]]
file = "src/Controller/Web/ContactPage.php"
code = "non-existent-method"
message = 'Method `create` does not exist on type `Nyholm\Psr7\Stream`.'
count = 1

[[issues]]
file = "src/Controller/Web/HomePage.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "src/Controller/Web/HomePage.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `app\controller\web\homepage::__invoke`. Saw type `mixed`.'
count = 1

[[issues]]
file = "src/Controller/Web/HomePage.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7\Response` not found.'
count = 1

[[issues]]
file = "src/Controller/Web/HomePage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ResponseInterface`.'
count = 1

[[issues]]
file = "src/Controller/Web/HomePage.php"
code = "non-existent-method"
message = 'Method `create` does not exist on type `Nyholm\Psr7\Stream`.'
count = 1

[[issues]]
file = "src/Controller/Web/LogDemoPage.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Log\LoggerInterface)`).'
count = 6

[[issues]]
file = "src/Controller/Web/LogDemoPage.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "src/Controller/Web/LogDemoPage.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `app\controller\web\logdemopage::__invoke`. Saw type `mixed`.'
count = 1

[[issues]]
file = "src/Controller/Web/LogDemoPage.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7\Response` not found.'
count = 1

[[issues]]
file = "src/Controller/Web/LogDemoPage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ResponseInterface`.'
count = 1

[[issues]]
file = "src/Controller/Web/LogDemoPage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Log\LoggerInterface`.'
count = 1

[[issues]]
file = "src/Controller/Web/LogDemoPage.php"
code = "non-existent-method"
message = 'Method `create` does not exist on type `Nyholm\Psr7\Stream`.'
count = 1

[[issues]]
file = "src/Controller/Web/OpenApiPage.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "src/Controller/Web/OpenApiPage.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `app\controller\web\openapipage::__invoke`. Saw type `mixed`.'
count = 1

[[issues]]
file = "src/Controller/Web/OpenApiPage.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7\Response` not found.'
count = 1

[[issues]]
file = "src/Controller/Web/OpenApiPage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ResponseInterface`.'
count = 1

[[issues]]
file = "src/Controller/Web/OpenApiPage.php"
code = "non-existent-method"
message = 'Method `create` does not exist on type `Nyholm\Psr7\Stream`.'
count = 1

[[issues]]
file = "src/Controller/Web/UsersPage.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Psr\Log\LoggerInterface)`).'
count = 1

[[issues]]
file = "src/Controller/Web/UsersPage.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "src/Controller/Web/UsersPage.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `app\controller\web\userspage::__invoke`. Saw type `mixed`.'
count = 1

[[issues]]
file = "src/Controller/Web/UsersPage.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7\Response` not found.'
count = 1

[[issues]]
file = "src/Controller/Web/UsersPage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ResponseInterface`.'
count = 1

[[issues]]
file = "src/Controller/Web/UsersPage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Log\LoggerInterface`.'
count = 1

[[issues]]
file = "src/Controller/Web/UsersPage.php"
code = "non-existent-method"
message = 'Method `create` does not exist on type `Nyholm\Psr7\Stream`.'
count = 1

[[issues]]
file = "src/Controller/Web/VarDumperPage.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "src/Controller/Web/VarDumperPage.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `app\controller\web\vardumperpage::__invoke`. Saw type `mixed`.'
count = 1

[[issues]]
file = "src/Controller/Web/VarDumperPage.php"
code = "non-existent-class"
message = 'Class `Nyholm\Psr7\Response` not found.'
count = 1

[[issues]]
file = "src/Controller/Web/VarDumperPage.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ResponseInterface`.'
count = 1

[[issues]]
file = "src/Controller/Web/VarDumperPage.php"
code = "non-existent-method"
message = 'Method `create` does not exist on type `Nyholm\Psr7\Stream`.'
count = 1

[[issues]]
file = "src/Controller/Web/VarDumperPage.php"
code = "non-existent-method"
message = 'Method `dump` does not exist on type `Symfony\Component\VarDumper\VarDumper`.'
count = 3
