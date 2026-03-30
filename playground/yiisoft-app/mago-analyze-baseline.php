variant = "loose"

[[issues]]
file = "config/common/bootstrap.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Container\ContainerInterface`.'
count = 1

[[issues]]
file = "config/common/di/application.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 3

[[issues]]
file = "config/common/di/db.php"
code = "non-existent-class"
message = 'Class `Yiisoft\Db\Sqlite\Driver` not found.'
count = 1

[[issues]]
file = "config/common/di/error-handler.php"
code = "invalid-return-statement"
message = "Invalid return type for function `10610436458813642739:228`: expected `null|string`, but found `array<array-key, string>|string`."
count = 1

[[issues]]
file = "config/common/di/error-handler.php"
code = "mixed-argument"
message = "Invalid argument type for argument #3 of `str_replace`: expected `array<array-key, string>|string`, but found `nonnull`."
count = 1

[[issues]]
file = "config/common/di/logger.php"
code = "non-existent-method"
message = 'Method `from` does not exist on type `Yiisoft\Definitions\ReferencesArray`.'
count = 1

[[issues]]
file = "config/common/di/router.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\Config\Config)`).'
count = 1

[[issues]]
file = "config/common/di/router.php"
code = "non-existent-class"
message = 'Class `Yiisoft\Router\RouteCollector` not found.'
count = 1

[[issues]]
file = "config/common/di/router.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Config\Config`.'
count = 1

[[issues]]
file = "config/common/di/router.php"
code = "non-existent-method"
message = 'Method `to` does not exist on type `Yiisoft\Definitions\DynamicReference`.'
count = 1

[[issues]]
file = "config/common/params.php"
code = "non-existent-method"
message = 'Method `to` does not exist on type `Yiisoft\Definitions\Reference`.'
count = 6

[[issues]]
file = "config/common/routes.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 61

[[issues]]
file = "config/common/routes.php"
code = "non-existent-method"
message = 'Method `create` does not exist on type `Yiisoft\Router\Group`.'
count = 2

[[issues]]
file = "config/common/routes.php"
code = "non-existent-method"
message = 'Method `get` does not exist on type `Yiisoft\Router\Route`.'
count = 27

[[issues]]
file = "config/common/routes.php"
code = "non-existent-method"
message = 'Method `methods` does not exist on type `Yiisoft\Router\Route`.'
count = 2

[[issues]]
file = "config/web/di/application.php"
code = "non-existent-method"
message = 'Method `to` does not exist on type `Yiisoft\Definitions\DynamicReference`.'
count = 1

[[issues]]
file = "config/web/di/application.php"
code = "non-existent-method"
message = 'Method `to` does not exist on type `Yiisoft\Definitions\Reference`.'
count = 3

[[issues]]
file = "public/index.php"
code = "impossible-assignment"
message = "Invalid assignment: the right-hand side has type `never` and cannot produce a value."
count = 1

[[issues]]
file = "public/index.php"
code = "non-existent-class"
message = 'Class `Yiisoft\ErrorHandler\ErrorHandler` not found.'
count = 1

[[issues]]
file = "public/index.php"
code = "non-existent-class"
message = 'Class `Yiisoft\ErrorHandler\Renderer\HtmlRenderer` not found.'
count = 1

[[issues]]
file = "public/index.php"
code = "non-existent-class"
message = 'Class `Yiisoft\Log\Logger` not found.'
count = 1

[[issues]]
file = "public/index.php"
code = "non-existent-class"
message = 'Class `Yiisoft\Log\Target\File\FileTarget` not found.'
count = 1

[[issues]]
file = "public/index.php"
code = "non-existent-class"
message = 'Class `Yiisoft\Yii\Runner\Http\HttpApplicationRunner` not found.'
count = 1

[[issues]]
file = "public/index.php"
code = "non-existent-class-like"
message = 'Class, interface, enum, or trait `Psr\Log\LogLevel` not found.'
count = 3

[[issues]]
file = "src/Console/HelloCommand.php"
code = "non-existent-attribute-class"
message = 'Attribute class `Symfony\Component\Console\Attribute\AsCommand` not found or could not be autoloaded.'
count = 1

[[issues]]
file = "src/Console/HelloCommand.php"
code = "non-existent-class-like"
message = 'Class `App\Console\HelloCommand` cannot extend unknown type `Command`'
count = 1

[[issues]]
file = "src/Console/HelloCommand.php"
code = "unused-method"
message = "Method `execute()` is never used."
count = 1

[[issues]]
file = "src/Environment.php"
code = "impossible-condition"
message = "This condition (type `false`) will always evaluate to false."
count = 1

[[issues]]
file = "src/Environment.php"
code = "less-specific-nested-argument-type"
message = "Argument type mismatch for argument #2 of `implode`: expected `array<array-key, Stringable|null|scalar>|null`, but provided type `list{mixed, mixed, mixed}` is less specific."
count = 1

[[issues]]
file = "src/Environment.php"
code = "redundant-comparison"
message = "Redundant `===` comparison: left-hand side is never identical to right-hand side."
count = 1

[[issues]]
file = "src/Environment.php"
code = "unused-method"
message = "Method `setinteger()` is never used."
count = 1

[[issues]]
file = "src/Environment.php"
code = "unused-method"
message = "Method `setstring()` is never used."
count = 1

[[issues]]
file = "src/Web/HomePage/Action.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\Yii\View\Renderer\ViewRenderer)`).'
count = 1

[[issues]]
file = "src/Web/HomePage/Action.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `app\web\homepage\action::__invoke`. Saw type `mixed`.'
count = 1

[[issues]]
file = "src/Web/HomePage/Action.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Psr\Http\Message\ResponseInterface`.'
count = 1

[[issues]]
file = "src/Web/HomePage/Action.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Yii\View\Renderer\ViewRenderer`.'
count = 1

[[issues]]
file = "src/Web/HomePage/template.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\View\WebView)`).'
count = 1

[[issues]]
file = "src/Web/HomePage/template.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\View\WebView`.'
count = 1

[[issues]]
file = "src/Web/NotFound/NotFoundHandler.php"
code = "non-existent-class-like"
message = 'Class `App\Web\NotFound\NotFoundHandler` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/NotFound/NotFoundHandler.php"
code = "unused-property"
message = "Property `$currentRoute` is never used."
count = 1

[[issues]]
file = "src/Web/NotFound/NotFoundHandler.php"
code = "unused-property"
message = "Property `$urlGenerator` is never used."
count = 1

[[issues]]
file = "src/Web/NotFound/NotFoundHandler.php"
code = "unused-property"
message = "Property `$viewRenderer` is never used."
count = 1

[[issues]]
file = "src/Web/NotFound/template.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\Router\CurrentRoute)`).'
count = 1

[[issues]]
file = "src/Web/NotFound/template.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\Router\UrlGeneratorInterface)`).'
count = 1

[[issues]]
file = "src/Web/NotFound/template.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\View\WebView)`).'
count = 1

[[issues]]
file = "src/Web/NotFound/template.php"
code = "mixed-argument"
message = "The first value for `echo` is too general."
count = 2

[[issues]]
file = "src/Web/NotFound/template.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "src/Web/NotFound/template.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Router\CurrentRoute`.'
count = 1

[[issues]]
file = "src/Web/NotFound/template.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Router\UrlGeneratorInterface`.'
count = 1

[[issues]]
file = "src/Web/NotFound/template.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\View\WebView`.'
count = 1

[[issues]]
file = "src/Web/NotFound/template.php"
code = "non-existent-method"
message = 'Method `encode` does not exist on type `Yiisoft\Html\Html`.'
count = 1

[[issues]]
file = "src/Web/Shared/Layout/Main/MainAsset.php"
code = "non-existent-class-like"
message = 'Class `App\Web\Shared\Layout\Main\MainAsset` cannot extend unknown type `AssetBundle`'
count = 1

[[issues]]
file = "src/Web/Shared/Layout/Main/layout.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\Aliases\Aliases)`).'
count = 1

[[issues]]
file = "src/Web/Shared/Layout/Main/layout.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\Assets\AssetManager)`).'
count = 6

[[issues]]
file = "src/Web/Shared/Layout/Main/layout.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\View\WebView)`).'
count = 11

[[issues]]
file = "src/Web/Shared/Layout/Main/layout.php"
code = "mixed-argument"
message = "The first value for `echo` is too general."
count = 5

[[issues]]
file = "src/Web/Shared/Layout/Main/layout.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Aliases\Aliases`.'
count = 1

[[issues]]
file = "src/Web/Shared/Layout/Main/layout.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Assets\AssetManager`.'
count = 1

[[issues]]
file = "src/Web/Shared/Layout/Main/layout.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Router\CurrentRoute`.'
count = 1

[[issues]]
file = "src/Web/Shared/Layout/Main/layout.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Router\UrlGeneratorInterface`.'
count = 1

[[issues]]
file = "src/Web/Shared/Layout/Main/layout.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\View\WebView`.'
count = 1

[[issues]]
file = "src/Web/Shared/Layout/Main/layout.php"
code = "non-existent-method"
message = 'Method `encode` does not exist on type `Yiisoft\Html\Html`.'
count = 4

[[issues]]
file = "src/Web/TestFixtures/CacheAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\CacheAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/CacheAction.php"
code = "unused-property"
message = "Property `$cacheCollector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/CacheAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/CacheHeavyAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\CacheHeavyAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/CacheHeavyAction.php"
code = "unused-property"
message = "Property `$cacheCollector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/CacheHeavyAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/CoverageAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\CoverageAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/CoverageAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/DatabaseAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\DatabaseAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/DatabaseAction.php"
code = "unused-property"
message = "Property `$databaseCollector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/DatabaseAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/DumpAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\DumpAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/DumpAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/ElasticsearchAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\ElasticsearchAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/ElasticsearchAction.php"
code = "unused-property"
message = "Property `$elasticsearchCollector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/ElasticsearchAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/EventsAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\EventsAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/EventsAction.php"
code = "unused-property"
message = "Property `$dispatcher` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/EventsAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/ExceptionAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\ExceptionAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/ExceptionChainedAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\ExceptionChainedAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/FileStreamAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\FileStreamAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/FileStreamAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/FilesystemAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\FilesystemAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/FilesystemAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/HttpClientAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\HttpClientAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/HttpClientAction.php"
code = "unused-property"
message = "Property `$httpClient` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/HttpClientAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/LogsAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\LogsAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/LogsAction.php"
code = "unused-property"
message = "Property `$logger` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/LogsAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/LogsContextAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\LogsContextAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/LogsContextAction.php"
code = "unused-property"
message = "Property `$logger` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/LogsContextAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/LogsHeavyAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\LogsHeavyAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/LogsHeavyAction.php"
code = "unused-property"
message = "Property `$logger` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/LogsHeavyAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/MailerAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\MailerAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/MailerAction.php"
code = "unused-property"
message = "Property `$mailerCollector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/MailerAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/MessengerAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\MessengerAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/MessengerAction.php"
code = "unused-property"
message = "Property `$queueCollector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/MessengerAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/MultiAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\MultiAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/MultiAction.php"
code = "unused-property"
message = "Property `$dispatcher` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/MultiAction.php"
code = "unused-property"
message = "Property `$logger` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/MultiAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/OpenTelemetryAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\OpenTelemetryAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/OpenTelemetryAction.php"
code = "unused-property"
message = "Property `$collector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/OpenTelemetryAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/RedisAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\RedisAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/RedisAction.php"
code = "unused-property"
message = "Property `$redisCollector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/RedisAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/RequestInfoAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\RequestInfoAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/RequestInfoAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/ResetAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\ResetAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/ResetAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/ResetAction.php"
code = "unused-property"
message = "Property `$storage` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/ResetCliAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\ResetCliAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/ResetCliAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/RouterAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\RouterAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/RouterAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/RouterAction.php"
code = "unused-property"
message = "Property `$routerCollector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/SecurityAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\SecurityAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/SecurityAction.php"
code = "unused-property"
message = "Property `$authorizationCollector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/SecurityAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/TimelineAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\TimelineAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/TimelineAction.php"
code = "unused-property"
message = "Property `$logger` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/TimelineAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/TranslatorAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\TranslatorAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/TranslatorAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/TranslatorAction.php"
code = "unused-property"
message = "Property `$translatorCollector` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/ValidatorAction.php"
code = "non-existent-class-like"
message = 'Class `App\Web\TestFixtures\ValidatorAction` cannot implement unknown type `RequestHandlerInterface`'
count = 1

[[issues]]
file = "src/Web/TestFixtures/ValidatorAction.php"
code = "unused-property"
message = "Property `$responseFactory` is never used."
count = 1

[[issues]]
file = "src/Web/TestFixtures/ValidatorAction.php"
code = "unused-property"
message = "Property `$validatorCollector` is never used."
count = 1
