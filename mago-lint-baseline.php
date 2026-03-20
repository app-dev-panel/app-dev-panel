variant = "loose"

[[issues]]
file = "libs/API/src/ApiConfig.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/API/src/Debug/Middleware/TokenAuthMiddleware.php"
code = "no-insecure-comparison"
message = "Insecure comparison of sensitive data."
count = 1

[[issues]]
file = "libs/API/src/Debug/Middleware/TokenAuthMiddleware.php"
code = "sensitive-parameter"
message = "Parameters that may contain sensitive information should be marked with the `#[SensitiveParameter]` attribute."
count = 1

[[issues]]
file = "libs/API/src/Debug/Repository/CollectorRepository.php"
code = "no-empty"
message = "Use of the `empty` construct."
count = 1

[[issues]]
file = "libs/API/src/Debug/Repository/CollectorRepository.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/API/src/Ingestion/Controller/IngestionController.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/API/src/Ingestion/Controller/IngestionController.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 4

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "kan-defect"
message = "Class has a high kan defect score (2.25)."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/DatabaseController.php"
code = "readable-literal"
message = "Numeric literal could use underscore separators for readability."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "no-empty"
message = "Use of the `empty` construct."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/InspectController.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/InspectController.php"
code = "no-debug-symbols"
message = "Do not commit debug functions."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/ServiceController.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 3

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Middleware/InspectorProxyMiddleware.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/PHPUnitJSONReporter.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/API/src/Middleware/MiddlewarePipeline.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/API/src/ServerSentEventsStream.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Debug/Controller/DebugControllerTest.php"
code = "no-redundant-use"
message = "Unused import: `ResponseInterface`."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Debug/Controller/DebugControllerTest.php"
code = "prefer-static-closure"
message = "This closure does not use `$this` and should be declared static."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Debug/Controller/DebugControllerTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Debug/Middleware/ResponseDataWrapperTest.php"
code = "prefer-static-closure"
message = "This closure does not use `$this` and should be declared static."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "explicit-octal"
message = "Use explicit octal numeral notation."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "prefer-static-closure"
message = "This closure does not use `$this` and should be declared static."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "readable-literal"
message = "Numeric literal could use underscore separators for readability."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CacheControllerTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ComposerControllerTest.php"
code = "explicit-octal"
message = "Use explicit octal numeral notation."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ComposerControllerTest.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 3

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ControllerTestCase.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ControllerTestCase.php"
code = "prefer-static-closure"
message = "This closure does not use `$this` and should be declared static."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/DatabaseControllerTest.php"
code = "readable-literal"
message = "Numeric literal could use underscore separators for readability."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "explicit-octal"
message = "Use explicit octal numeral notation."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/InspectControllerTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ServiceControllerTest.php"
code = "explicit-octal"
message = "Use explicit octal numeral notation."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ServiceControllerTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Middleware/InspectorProxyMiddlewareTest.php"
code = "no-redundant-use"
message = "Unused import: `ServerRequestInterface`."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Middleware/InspectorProxyMiddlewareTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/API/tests/Unit/ServerSentEventsStreamTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$dataType` is implicitly nullable and relies on a deprecated feature."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$delete` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$forRead` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$indexMethod` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$indexType` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$length` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$options` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$update` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$value` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$isolationLevel` is implicitly nullable and relies on a deprecated feature."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$sequenceName` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "explicit-nullable-param"
message = "Parameter `$sql` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/TransactionInterfaceDecorator.php"
code = "explicit-nullable-param"
message = "Parameter `$isolationLevel` is implicitly nullable and relies on a deprecated feature."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "no-empty-catch-clause"
message = "Do not use empty `catch` blocks."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerProxyConfig.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerProxyConfig.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 4

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerProxyConfig.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ProxyLogTrait.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 3

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ServiceMethodProxy.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/VarDumperHandlerInterfaceProxy.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/Cli/src/Command/DebugQueryCommand.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Cli/src/Command/DebugQueryCommand.php"
code = "kan-defect"
message = "Class has a high kan defect score (2.27)."
count = 1

[[issues]]
file = "libs/Cli/src/Command/ServeCommand.php"
code = "explicit-octal"
message = "Use explicit octal numeral notation."
count = 1

[[issues]]
file = "libs/Cli/src/Command/ServeCommand.php"
code = "prefer-static-closure"
message = "This closure does not use `$this` and should be declared static."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Console/CommandCollector.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Console/CommandCollector.php"
code = "no-empty"
message = "Use of the `empty` construct."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/DatabaseCollector.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/DatabaseCollector.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 4

[[issues]]
file = "libs/Kernel/src/Collector/DatabaseCollector.php"
code = "prefer-static-closure"
message = "This arrow function does not use `$this` and should be declared static."
count = 2

[[issues]]
file = "libs/Kernel/src/Collector/DatabaseCollector.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/HttpClientCollector.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/LoggerInterfaceProxy.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/QueueCollector.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/ServiceCollector.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 2

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/Broadcaster.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 2

[[issues]]
file = "libs/Kernel/src/DebugServer/Connection.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 3

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 3

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "strict-behavior"
message = "Call to `base64_decode` must enforce strict comparison."
count = 1

[[issues]]
file = "libs/Kernel/src/Debugger.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Kernel/src/Debugger.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Kernel/src/Debugger.php"
code = "kan-defect"
message = "Class has a high kan defect score (2.0700000000000003)."
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "kan-defect"
message = "Class has a high kan defect score (2.7600000000000002)."
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "no-empty"
message = "Use of the `empty` construct."
count = 2

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/src/Event/ProxyMethodCallEvent.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Kernel/src/FlattenException.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Kernel/src/FlattenException.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 2

[[issues]]
file = "libs/Kernel/src/FlattenException.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/BacktraceIgnoreMatcher.php"
code = "no-empty"
message = "Use of the `empty` construct."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/BacktraceIgnoreMatcher.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 2

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapperInterface.php"
code = "too-many-methods"
message = "Interface has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/src/Service/FileServiceRegistry.php"
code = "explicit-octal"
message = "Use explicit octal numeral notation."
count = 1

[[issues]]
file = "libs/Kernel/src/Service/FileServiceRegistry.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 2

[[issues]]
file = "libs/Kernel/src/Service/ServiceDescriptor.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Kernel/src/Service/ServiceDescriptor.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/Kernel/src/Storage/FileStorage.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Kernel/src/Storage/FileStorage.php"
code = "no-empty"
message = "Use of the `empty` construct."
count = 1

[[issues]]
file = "libs/Kernel/src/Storage/FileStorage.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 2

[[issues]]
file = "libs/Kernel/src/Storage/FileStorage.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/src/Storage/MemoryStorage.php"
code = "no-empty"
message = "Use of the `empty` construct."
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/CacheCollectorTest.php"
code = "assert-description"
message = "Missing description in assert function."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/FilesystemStreamCollectorTest.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/FilesystemStreamCollectorTest.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpStreamCollectorTest.php"
code = "braced-string-interpolation"
message = "Unbraced variable in string interpolation."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpStreamCollectorTest.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpStreamCollectorTest.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/MiddlewareCollectorTest.php"
code = "assert-description"
message = "Missing description in assert function."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/QueueCollectorTest.php"
code = "assert-description"
message = "Missing description in assert function."
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/RouterCollectorTest.php"
code = "assert-description"
message = "Missing description in assert function."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/SecurityCollectorTest.php"
code = "assert-description"
message = "Missing description in assert function."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/TemplateCollectorTest.php"
code = "assert-description"
message = "Missing description in assert function."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ValidatorCollectorTest.php"
code = "assert-description"
message = "Missing description in assert function."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ViewCollectorTest.php"
code = "assert-description"
message = "Missing description in assert function."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DebuggerTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "braced-string-interpolation"
message = "Unbraced variable in string interpolation."
count = 3

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/FlattenExceptionTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Helper/BacktraceIgnoreMatcherTest.php"
code = "require-preg-quote-delimiter"
message = "Missing delimiter argument in `preg_quote()` call"
count = 5

[[issues]]
file = "libs/Kernel/tests/Unit/ProxyDecoratedCallsTest.php"
code = "braced-string-interpolation"
message = "Unbraced variable in string interpolation."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Service/FileServiceRegistryTest.php"
code = "explicit-octal"
message = "Use explicit octal numeral notation."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Service/FileServiceRegistryTest.php"
code = "readable-literal"
message = "Numeric literal could use underscore separators for readability."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Service/FileServiceRegistryTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1

[[issues]]
file = "libs/Testing/src/Assertion/ExpectationEvaluator.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Testing/src/Assertion/ExpectationEvaluator.php"
code = "kan-defect"
message = "Class has a high kan defect score (2.34)."
count = 1

[[issues]]
file = "libs/Testing/src/Assertion/ExpectationEvaluator.php"
code = "prefer-early-continue"
message = "Consider using early continue pattern to reduce nesting."
count = 2

[[issues]]
file = "libs/Testing/src/Command/DebugFixturesCommand.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Testing/src/Command/DebugFixturesCommand.php"
code = "kan-defect"
message = "Class has a high kan defect score (2.4400000000000004)."
count = 1

[[issues]]
file = "libs/Testing/src/Command/DebugFixturesCommand.php"
code = "prefer-early-continue"
message = "Consider using early continue pattern to reduce nesting."
count = 2

[[issues]]
file = "libs/Testing/src/Fixture/Fixture.php"
code = "excessive-parameter-list"
message = "Parameter list is too long."
count = 1

[[issues]]
file = "libs/Testing/src/Runner/FixtureResult.php"
code = "prefer-early-continue"
message = "Consider using early continue pattern to reduce nesting."
count = 1

[[issues]]
file = "libs/Testing/src/Runner/FixtureRunner.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Testing/src/Runner/FixtureRunner.php"
code = "kan-defect"
message = "Class has a high kan defect score (1.9800000000000002)."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/DebugApiTest.php"
code = "prefer-early-continue"
message = "Consider using early continue pattern to reduce nesting."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/FixtureTestCase.php"
code = "prefer-early-continue"
message = "Consider using early continue pattern to reduce nesting."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "cyclomatic-complexity"
message = "Class has high complexity."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "kan-defect"
message = "Class has a high kan defect score (4.1000000000000005)."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 4

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "prefer-early-continue"
message = "Consider using early continue pattern to reduce nesting."
count = 3

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "too-many-methods"
message = "Class has too many methods."
count = 1
