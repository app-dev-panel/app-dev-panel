variant = "loose"

[[issues]]
file = "libs/API/src/ApiApplication.php"
code = "less-specific-nested-return-statement"
message = '''Returned type `non-empty-list<mixed>` is less specific than the declared return type `array<array-key, Psr\Http\Server\MiddlewareInterface>` for function `appdevpanel\api\apiapplication::buildpipeline` due to nested 'mixed'.'''
count = 1

[[issues]]
file = "libs/API/src/ApiApplication.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/ApiApplication.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "libs/API/src/ApiApplication.php"
code = "mixed-return-statement"
message = "Could not infer a precise return type for function `class@anonymous:5562770940335764069-4589:5423::handle`. Saw type `mixed`."
count = 1

[[issues]]
file = "libs/API/src/ApiApplication.php"
code = "string-member-selector"
message = "This member selector uses a non-literal string type (`string`); its specific value cannot be statically determined."
count = 1

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "impossible-condition"
message = "This condition (type `false`) will always evaluate to false."
count = 1

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface::getdetail`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface::getdumpobject`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface::getobject`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface::getsummary`: expected `null|string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface::getobject`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "mixed-array-assignment"
message = "Unsafe array assignment on type `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "mixed-array-index"
message = "Invalid index type `nonnull` used for array access on `array<array-key, mixed>`."
count = 3

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 7

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/API/src/Debug/Controller/DebugController.php"
code = "redundant-comparison"
message = "Redundant `>` comparison: left-hand side is never greater than right-hand side."
count = 1

[[issues]]
file = "libs/API/src/Debug/Middleware/ResponseDataWrapper.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Debug/Middleware/ResponseDataWrapper.php"
code = "mixed-argument"
message = "Invalid argument type for argument #3 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Debug/Middleware/ResponseDataWrapper.php"
code = "mixed-argument"
message = "Invalid argument type for argument #4 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Debug/Middleware/ResponseDataWrapper.php"
code = "mixed-argument"
message = "Invalid argument type for argument #5 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Debug/Middleware/ResponseDataWrapper.php"
code = "mixed-argument"
message = "Invalid argument type for argument #6 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Debug/Middleware/ResponseDataWrapper.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Debug/Repository/CollectorRepository.php"
code = "less-specific-argument"
message = "Argument type mismatch for argument #1 of `substr`: expected `string`, but provided type `array-key` is less specific."
count = 1

[[issues]]
file = "libs/API/src/Debug/Repository/CollectorRepository.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Debug/Repository/CollectorRepository.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\api\debug\repository\collectorrepository::loaddata`. Saw type `nonnull`.'
count = 1

[[issues]]
file = "libs/API/src/Ingestion/Controller/IngestionController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/API/src/Ingestion/Controller/IngestionController.php"
code = "possibly-false-argument"
message = "Argument #1 of function `yaml_parse` is possibly `false`, but parameter type `string` does not accept it."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Command/BashCommand.php"
code = "possibly-null-operand"
message = "Left operand in `>` comparison might be `null` (type `int|null`)."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Command/CodeceptionCommand.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Command/CodeceptionCommand.php"
code = "possibly-false-argument"
message = "Argument #1 of function `json_decode` is possibly `false`, but parameter type `string` does not accept it."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Command/CodeceptionCommand.php"
code = "possibly-null-operand"
message = "Left operand in `>` comparison might be `null` (type `int|null`)."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Command/PHPUnitCommand.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Command/PHPUnitCommand.php"
code = "possibly-false-argument"
message = "Argument #1 of function `json_decode` is possibly `false`, but parameter type `string` does not accept it."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Command/PHPUnitCommand.php"
code = "possibly-null-operand"
message = "Left operand in `>` comparison might be `null` (type `int|null`)."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Command/PsalmCommand.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Command/PsalmCommand.php"
code = "possibly-false-argument"
message = "Argument #1 of function `json_decode` is possibly `false`, but parameter type `string` does not accept it."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Command/PsalmCommand.php"
code = "possibly-null-operand"
message = "Left operand in `>` comparison might be `null` (type `int|null`)."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CacheController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `Psr\SimpleCache\CacheInterface::delete`: expected `string`, but found `nonnull`.'
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CacheController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `Psr\SimpleCache\CacheInterface::get`: expected `string`, but found `nonnull`.'
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CacheController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `Psr\SimpleCache\CacheInterface::has`: expected `string`, but found `nonnull`.'
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/CacheController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/CacheController.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/CacheController.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\api\inspector\controller\cachecontroller::getcache`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "invalid-iterator"
message = "The expression provided to `foreach` is not iterable. It resolved to type `nonnull`, which is not iterable."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "invalid-type-cast"
message = "Casting `mixed` to `array`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_key_exists`: expected `bool|float|int|null|string`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `implode`: expected `array<array-key, Stringable|null|scalar>|null`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "mixed-array-index"
message = "Invalid index type `mixed` used for array access on `array<array-key, array<array-key, mixed>>`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "mixed-array-index"
message = '''Invalid index type `nonnull` used for array access on `array<string, class-string<AppDevPanel\Api\Inspector\CommandInterface>|list{string('composer'), array-key}|list{string('composer'), array-key}>`.'''
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 8

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 4

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "possibly-false-argument"
message = "Argument #1 of function `json_decode` is possibly `false`, but parameter type `string` does not accept it."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/CommandController.php"
code = "possibly-static-access-on-interface"
message = 'Potential static method call on interface `AppDevPanel\Api\Inspector\CommandInterface` via `class-string`.'
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/ComposerController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `json_decode`: expected `string`, but found `mixed`."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/ComposerController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/ComposerController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #3 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/ComposerController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/API/src/Inspector/Controller/ComposerController.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/ComposerController.php"
code = "possibly-false-argument"
message = "Argument #1 of function `json_decode` is possibly `false`, but parameter type `string` does not accept it."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/DatabaseController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Api\Inspector\Database\SchemaProviderInterface::gettable`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/DatabaseController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "array-to-string-conversion"
message = "Potential array in right operand of string concatenation."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "invalid-method-access"
message = "Attempting to access a method on a non-object type (`string`)."
count = 4

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `ReflectionClass::getmethod`: expected `string`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `ReflectionClass::hasmethod`: expected `string`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `class_exists`: expected `string`, but found `truthy-mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `realpath`: expected `string`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `str_starts_with`: expected `string`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `AppDevPanel\Api\Inspector\Controller\FileController::removebasepath`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `AppDevPanel\Api\Inspector\Controller\FileController::removebasepath`: expected `string`, but found `nonnull`.'
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 3

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "mixed-operand"
message = "Invalid left operand: type `mixed` cannot be reliably used in string concatenation."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "possibly-false-argument"
message = 'Argument #1 of method `AppDevPanel\Api\Inspector\Controller\FileController::removebasepath` is possibly `false`, but parameter type `string` does not accept it.'
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "possibly-false-argument"
message = "Argument #2 of function `str_starts_with` is possibly `false`, but parameter type `string` does not accept it."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "possibly-false-operand"
message = "Possibly false left operand used in string concatenation (type `false|string`)."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "possibly-invalid-argument"
message = 'Possible argument type mismatch for argument #1 of `AppDevPanel\Api\Inspector\Controller\FileController::serializefileinfo`: expected `SplFileInfo`, but possibly received `RecursiveDirectoryIterator|SplFileInfo|null|string`.'
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "possibly-invalid-argument"
message = "Possible argument type mismatch for argument #1 of `str_starts_with`: expected `string`, but possibly received `array<array-key, mixed>|null|string`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "possibly-null-argument"
message = "Argument #1 of function `str_starts_with` is possibly `null`, but parameter type `string` does not accept it."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/FileController.php"
code = "possibly-null-operand"
message = "Possibly null right operand used in string concatenation (type `array<array-key, mixed>|null|string`)."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/GitController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `preg_match`: expected `string`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/GitController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/GitController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 5

[[issues]]
file = "libs/API/src/Inspector/Controller/InspectController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `class_exists`: expected `string`, but found `nonnull`."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/InspectController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `interface_exists`: expected `string`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/InspectController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `ksort`: expected `array<('K.ksort() extends array-key), ('V.ksort() extends mixed)>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/InspectController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/InspectController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 7

[[issues]]
file = "libs/API/src/Inspector/Controller/InspectController.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/InspectController.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 3

[[issues]]
file = "libs/API/src/Inspector/Controller/RequestController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface::getdetail`: expected `string`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/RequestController.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `guzzlehttp\psr7\message::parserequest`: expected `string`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/RequestController.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/RequestController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 5

[[issues]]
file = "libs/API/src/Inspector/Controller/RoutingController.php"
code = "ambiguous-object-method-access"
message = "Cannot statically verify method call on a generic `object` type."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/RoutingController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `ReflectionProperty::getvalue`: expected `null|object`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/RoutingController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `end`: expected `array<array-key, ('T.end() extends mixed)>|object`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/RoutingController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `reflectionobject::__construct`: expected `object`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/RoutingController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `trim`: expected `string`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/RoutingController.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 6

[[issues]]
file = "libs/API/src/Inspector/Controller/RoutingController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 8

[[issues]]
file = "libs/API/src/Inspector/Controller/RoutingController.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 3

[[issues]]
file = "libs/API/src/Inspector/Controller/RoutingController.php"
code = "unknown-iterator-type"
message = "Cannot determine the type of the expression provided to `foreach`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/ServiceController.php"
code = "invalid-type-cast"
message = "Casting `mixed` to `array`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "ambiguous-object-method-access"
message = "Cannot statically verify method call on a generic `object` type."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "invalid-array-element-key"
message = "Invalid array key type."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "invalid-iterator"
message = "The expression provided to `foreach` is not iterable. It resolved to type `mixed`, which is not iterable."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "less-specific-nested-argument-type"
message = "Argument type mismatch for argument #2 of `implode`: expected `array<array-key, Stringable|null|scalar>|null`, but provided type `array<array-key, mixed>|list<mixed>` is less specific."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_keys`: expected `array<('K.array_keys() extends array-key), ('V.array_keys() extends mixed)>`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_replace_recursive`: expected `array<array-key, mixed>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `array_map`: expected `array<('K.array_map() extends array-key), object>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `array_merge`: expected `array<array-key, mixed>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `preg_match`: expected `string`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-array-index"
message = "Invalid index type `mixed` used for array access on `array<array-key, array<string, array<array-key, mixed>>>`."
count = 4

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 10

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`mixed`)."
count = 3

[[issues]]
file = "libs/API/src/Inspector/Controller/TranslationController.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`nonnull`)."
count = 2

[[issues]]
file = "libs/API/src/Inspector/Test/CodeceptionJSONReporter.php"
code = "non-existent-class-like"
message = 'Class `AppDevPanel\Api\Inspector\Test\CodeceptionJSONReporter` cannot extend unknown type `Extension`'
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/CodeceptionJSONReporter.php"
code = "unused-method"
message = "Method `gettestfilename()` is never used."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/CodeceptionJSONReporter.php"
code = "unused-method"
message = "Method `gettestname()` is never used."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/CodeceptionJSONReporter.php"
code = "unused-property"
message = "Property `$config` is never used."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/CodeceptionJSONReporter.php"
code = "unused-property"
message = "Property `$data` is never used."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/PHPUnitJSONReporter.php"
code = "non-existent-class-like"
message = 'Class `AppDevPanel\Api\Inspector\Test\PHPUnitJSONReporter` cannot implement unknown type `ResultPrinter`'
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/PHPUnitJSONReporter.php"
code = "unused-method"
message = "Method `logerroredtest()` is never used."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/PHPUnitJSONReporter.php"
code = "unused-method"
message = "Method `parsefilename()` is never used."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/PHPUnitJSONReporter.php"
code = "unused-method"
message = "Method `parsename()` is never used."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/PHPUnitJSONReporter.php"
code = "unused-property"
message = "Property `$data` is never used."
count = 1

[[issues]]
file = "libs/API/src/Inspector/Test/PHPUnitJSONReporter.php"
code = "unused-property"
message = "Property `$prettifier` is never used."
count = 1

[[issues]]
file = "libs/API/src/Middleware/IpFilterMiddleware.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/src/Router/Route.php"
code = "less-specific-return-statement"
message = 'Returned type `array<array-key, string>` is less specific than the declared return type `array<string, string>|null` for function `appdevpanel\api\router\route::match`.'
count = 1

[[issues]]
file = "libs/API/src/Router/Route.php"
code = "possibly-null-operand"
message = "Possibly null middle operand used in string concatenation (type `null|string`)."
count = 1

[[issues]]
file = "libs/API/src/Router/Route.php"
code = "reference-to-undefined-variable"
message = "Reference created from a previously undefined variable `$matches`."
count = 1

[[issues]]
file = "libs/API/src/ServerSentEventsStream.php"
code = "docblock-type-mismatch"
message = "Docblock return type `resource` is incompatible with native return type `void`."
count = 1

[[issues]]
file = "libs/API/src/ServerSentEventsStream.php"
code = "missing-return-statement"
message = "Missing return statement in function 'detach'"
count = 1

[[issues]]
file = "libs/API/src/ServerSentEventsStream.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/src/ServerSentEventsStream.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Debug/Controller/DebugControllerTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Debug/Controller/DebugControllerTest.php"
code = "possibly-false-argument"
message = 'Argument #3 of method `guzzlehttp\psr7\response::__construct` is possibly `false`, but parameter type `Psr\Http\Message\StreamInterface|null|resource|string` does not accept it.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Debug/Middleware/ResponseDataWrapperTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Debug/Middleware/ResponseDataWrapperTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 3

[[issues]]
file = "libs/API/tests/Unit/Debug/Middleware/ResponseDataWrapperTest.php"
code = "possibly-false-argument"
message = 'Argument #3 of method `guzzlehttp\psr7\response::__construct` is possibly `false`, but parameter type `Psr\Http\Message\StreamInterface|null|resource|string` does not accept it.'
count = 3

[[issues]]
file = "libs/API/tests/Unit/Debug/Repository/CollectorRepositoryTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 6

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `AppDevPanel\Kernel\Storage\FileStorage::read`: expected `null|string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertcount`: expected `Countable|iterable<mixed, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 18

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "mixed-array-index"
message = "Invalid index type `mixed` used for array access on `array<array-key, mixed>`."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 11

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "mixed-operand"
message = "Invalid middle operand: type `mixed` cannot be reliably used in string concatenation."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "possibly-false-argument"
message = "Argument #1 of function `json_decode` is possibly `false`, but parameter type `string` does not accept it."
count = 3

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "possibly-false-argument"
message = 'Argument #2 of method `phpunit\framework\assert::assertcount` is possibly `false`, but parameter type `Countable|iterable<mixed, mixed>` does not accept it.'
count = 4

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "possibly-false-argument"
message = 'Argument #3 of method `guzzlehttp\psr7\response::__construct` is possibly `false`, but parameter type `Psr\Http\Message\StreamInterface|null|resource|string` does not accept it.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Ingestion/Controller/IngestionControllerTest.php"
code = "possibly-false-iterator"
message = "Expression being iterated (type `false|list<non-empty-string>`) might be `false` at runtime."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Command/BashCommandTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CacheControllerTest.php"
code = "less-specific-argument"
message = 'Argument type mismatch for argument #1 of `appdevpanel\api\tests\unit\inspector\controller\controllertestcase::container`: expected `array<string, object>`, but provided type `array<array-key, mixed>` is less specific.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CacheControllerTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 6

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CacheControllerTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CacheControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CommandControllerTest.php"
code = "less-specific-argument"
message = 'Argument type mismatch for argument #1 of `appdevpanel\api\tests\unit\inspector\controller\controllertestcase::container`: expected `array<string, object>`, but provided type `array<array-key, mixed>` is less specific.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CommandControllerTest.php"
code = "less-specific-argument"
message = 'Argument type mismatch for argument #4 of `appdevpanel\api\inspector\controller\commandcontroller::__construct`: expected `array<string, array<string, class-string>>`, but provided type `array<array-key, mixed>` is less specific.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CommandControllerTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 3

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CommandControllerTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_column`: expected `array<array-key, array<string('group'), ('V.array_column() extends mixed)>|object>|list<array<string('group'), ('V.array_column() extends mixed)>|object>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CommandControllerTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_column`: expected `array<array-key, array<string('name'), ('V.array_column() extends mixed)>|object>|list<array<string('name'), ('V.array_column() extends mixed)>|object>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CommandControllerTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/CommandControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ComposerControllerTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ComposerControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ComposerControllerTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 4

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ComposerControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ControllerTestCase.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 3

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ControllerTestCase.php"
code = "possibly-false-argument"
message = 'Argument #3 of method `guzzlehttp\psr7\response::__construct` is possibly `false`, but parameter type `Psr\Http\Message\StreamInterface|null|resource|string` does not accept it.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/DatabaseControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertcount`: expected `Countable|iterable<mixed, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/DatabaseControllerTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/DatabaseControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "less-specific-nested-argument-type"
message = "Argument type mismatch for argument #1 of `array_column`: expected `array<array-key, array<string('baseName'), ('V.array_column() extends mixed)>|object>|list<array<string('baseName'), ('V.array_column() extends mixed)>|object>`, but provided type `array<array-key, mixed>` is less specific."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_column`: expected `array<array-key, array<string('baseName'), ('V.array_column() extends mixed)>|object>|list<array<string('baseName'), ('V.array_column() extends mixed)>|object>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 12

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertstringcontainsstring`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 6

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 7

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "possibly-false-iterator"
message = "Expression being iterated (type `false|list<non-empty-string>`) might be `false` at runtime."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/GitControllerTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 16

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/InspectControllerTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_keys`: expected `array<('K.array_keys() extends array-key), ('V.array_keys() extends mixed)>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/InspectControllerTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `strcmp`: expected `string`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/InspectControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 5

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/InspectControllerTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `strcmp`: expected `string`, but found `mixed`."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/InspectControllerTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/InspectControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 6

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/RequestControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/RequestControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertstringcontainsstring`: expected `string`, but found `nonnull`.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/RequestControllerTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/RequestControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/RoutingControllerTest.php"
code = "invalid-argument"
message = 'Invalid argument type for argument #1 of `yiisoft\router\matchingresult::fromfailure`: expected `array<array-key, string>`, but found `list{int(405)}`.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/RoutingControllerTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 5

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/RoutingControllerTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 3

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/RoutingControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 3

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ServiceControllerTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 6

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ServiceControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 6

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/ServiceControllerTest.php"
code = "possibly-false-iterator"
message = "Expression being iterated (type `false|list<non-empty-string>`) might be `false` at runtime."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/TranslationControllerTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/TranslationControllerTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/TranslationControllerTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/TranslationControllerTest.php"
code = "possibly-invalid-argument"
message = '''Possible argument type mismatch for argument #1 of `appdevpanel\api\tests\unit\inspector\controller\controllertestcase::container`: expected `array<string, object>`, but possibly received `array{'tag@translation.categorySource': array{}}`.'''
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/TranslationControllerTest.php"
code = "possibly-invalid-argument"
message = '''Possible argument type mismatch for argument #1 of `appdevpanel\api\tests\unit\inspector\controller\controllertestcase::container`: expected `array<string, object>`, but possibly received `array{'tag@translation.categorySource': list{Yiisoft\Translator\CategorySource}}`.'''
count = 3

[[issues]]
file = "libs/API/tests/Unit/Inspector/Middleware/InspectorProxyMiddlewareTest.php"
code = "less-specific-nested-argument-type"
message = 'Argument type mismatch for argument #4 of `appdevpanel\kernel\service\servicedescriptor::__construct`: expected `array<array-key, string>`, but provided type `array<array-key, mixed>` is less specific.'
count = 1

[[issues]]
file = "libs/API/tests/Unit/Inspector/Middleware/InspectorProxyMiddlewareTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 6

[[issues]]
file = "libs/API/tests/Unit/Inspector/Middleware/InspectorProxyMiddlewareTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertstringcontainsstring`: expected `string`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Middleware/InspectorProxyMiddlewareTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 2

[[issues]]
file = "libs/API/tests/Unit/Inspector/Middleware/InspectorProxyMiddlewareTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/API/tests/Unit/ServerSentEventsStreamTest.php"
code = "mixed-array-assignment"
message = "Unsafe array assignment on type `mixed`."
count = 6

[[issues]]
file = "libs/API/tests/Unit/ServerSentEventsStreamTest.php"
code = "redundant-comparison"
message = "Redundant `<` comparison: left-hand side is always less than right-hand side."
count = 2

[[issues]]
file = "libs/API/tests/Unit/ServerSentEventsStreamTest.php"
code = "redundant-comparison"
message = "Redundant `===` comparison: left-hand side is always identical to right-hand side."
count = 1

[[issues]]
file = "libs/API/tests/Unit/ServerSentEventsStreamTest.php"
code = "redundant-condition"
message = "This condition (type `true`) will always evaluate to true."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `bool|null` for parameter `$forRead` is incompatible with native type `bool`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `int|null` for parameter `$dataType` is incompatible with native type `int`."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `int|null` for parameter `$length` is incompatible with native type `int`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `int|null|string` for parameter `$value` is incompatible with native type `int|string`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `null|string` for parameter `$delete` is incompatible with native type `string`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `null|string` for parameter `$indexMethod` is incompatible with native type `string`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `null|string` for parameter `$indexType` is incompatible with native type `string`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `null|string` for parameter `$options` is incompatible with native type `string`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `null|string` for parameter `$update` is incompatible with native type `string`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `appdevpanel\adapter\yiisoft\collector\db\commandinterfaceproxy::__construct`: expected `Yiisoft\Db\Command\CommandInterface`, but found `mixed`.'
count = 39

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 12

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 6

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "mixed-operand"
message = "Invalid left operand: type `mixed` cannot be reliably used in string concatenation."
count = 6

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "mixed-operand"
message = "Invalid right operand: type `mixed` cannot be reliably used in string concatenation."
count = 6

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\adapter\yiisoft\collector\db\commandinterfaceproxy::insertwithreturningpks`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/CommandInterfaceProxy.php"
code = "string-member-selector"
message = "This member selector uses a non-literal string type (`non-empty-string`); its specific value cannot be statically determined."
count = 42

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `null|string` for parameter `$isolationLevel` is incompatible with native type `string`."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `null|string` for parameter `$sequenceName` is incompatible with native type `string`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "docblock-type-mismatch"
message = "Docblock type `null|string` for parameter `$sql` is incompatible with native type `string`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 4

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "mixed-operand"
message = "Invalid left operand: type `mixed` cannot be reliably used in string concatenation."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/ConnectionInterfaceProxy.php"
code = "mixed-operand"
message = "Invalid right operand: type `mixed` cannot be reliably used in string concatenation."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/DatabaseCollector.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 4

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/DatabaseCollector.php"
code = "mixed-array-assignment"
message = "Unsafe array assignment on type `mixed`."
count = 14

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/DatabaseCollector.php"
code = "mixed-operand"
message = "Left operand in spaceship comparison (`<=>`) has `mixed` type."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/DatabaseCollector.php"
code = "mixed-operand"
message = "Right operand in spaceship comparison (`<=>`) has `mixed` type."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/TransactionInterfaceDecorator.php"
code = "docblock-type-mismatch"
message = "Docblock type `null|string` for parameter `$isolationLevel` is incompatible with native type `string`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/TransactionInterfaceDecorator.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 6

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/TransactionInterfaceDecorator.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 3

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/TransactionInterfaceDecorator.php"
code = "mixed-operand"
message = "Invalid left operand: type `mixed` cannot be reliably used in string concatenation."
count = 3

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/TransactionInterfaceDecorator.php"
code = "mixed-operand"
message = "Invalid right operand: type `mixed` cannot be reliably used in string concatenation."
count = 3

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Db/TransactionInterfaceDecorator.php"
code = "string-member-selector"
message = "This member selector uses a non-literal string type (`non-empty-string`); its specific value cannot be statically determined."
count = 4

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Mailer/MailerCollector.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\Mailer\MessageInterface)`).'
count = 12

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Mailer/MailerCollector.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `quoted_printable_decode`: expected `string`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Mailer/MailerCollector.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Mailer\MessageInterface`.'
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Mailer/MailerInterfaceProxy.php"
code = "non-existent-class-like"
message = 'Class `AppDevPanel\Adapter\Yiisoft\Collector\Mailer\MailerInterfaceProxy` cannot implement unknown type `MailerInterface`'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Mailer/MailerInterfaceProxy.php"
code = "unused-property"
message = "Property `$collector` is never used."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Mailer/MailerInterfaceProxy.php"
code = "unused-property"
message = "Property `$decorated` is never used."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Middleware/MiddlewareCollector.php"
code = "less-specific-nested-argument-type"
message = "Argument type mismatch for argument #2 of `implode`: expected `array<array-key, Stringable|null|scalar>|null`, but provided type `array{0: string, ...}` is less specific."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Middleware/MiddlewareCollector.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `spl_object_id`: expected `object`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Middleware/MiddlewareCollector.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Middleware/MiddlewareCollector.php"
code = "mixed-operand"
message = "Invalid left operand: type `mixed` cannot be reliably used in string concatenation."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Middleware/MiddlewareCollector.php"
code = "mixed-operand"
message = "Invalid right operand: type `mixed` cannot be reliably used in string concatenation."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Middleware/MiddlewareCollector.php"
code = "non-existent-method"
message = 'Method `__debuginfo` does not exist on type `Psr\Http\Server\MiddlewareInterface`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueCollector.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\Queue\JobStatus)`).'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueCollector.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\Queue\QueueInterface)`).'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueCollector.php"
code = "mixed-array-assignment"
message = "Unsafe array assignment on type `mixed`."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueCollector.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Queue\JobStatus`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueCollector.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Queue\Message\MessageInterface`.'
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueCollector.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueCollector.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Queue\QueueInterface`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueDecorator.php"
code = "non-existent-class-like"
message = 'Class `AppDevPanel\Adapter\Yiisoft\Collector\Queue\QueueDecorator` cannot implement unknown type `QueueInterface`'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueDecorator.php"
code = "unused-property"
message = "Property `$collector` is never used."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueDecorator.php"
code = "unused-property"
message = "Property `$queue` is never used."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueProviderInterfaceProxy.php"
code = "non-existent-class-like"
message = 'Class `AppDevPanel\Adapter\Yiisoft\Collector\Queue\QueueProviderInterfaceProxy` cannot implement unknown type `QueueProviderInterface`'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueProviderInterfaceProxy.php"
code = "unused-property"
message = "Property `$collector` is never used."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueProviderInterfaceProxy.php"
code = "unused-property"
message = "Property `$queueProvider` is never used."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueWorkerInterfaceProxy.php"
code = "non-existent-class-like"
message = 'Class `AppDevPanel\Adapter\Yiisoft\Collector\Queue\QueueWorkerInterfaceProxy` cannot implement unknown type `WorkerInterface`'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueWorkerInterfaceProxy.php"
code = "unused-property"
message = "Property `$collector` is never used."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Queue/QueueWorkerInterfaceProxy.php"
code = "unused-property"
message = "Property `$worker` is never used."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Router/RouterCollector.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 5

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Router/RouterCollector.php"
code = "mixed-method-access"
message = "Attempting to access a method on a non-object type (`nonnull`)."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Router/RouterCollector.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\adapter\yiisoft\collector\router\routercollector::getcurrentroute`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Router/RouterCollector.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\adapter\yiisoft\collector\router\routercollector::getroutebycurrentroute`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Validator/ValidatorCollector.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\Validator\Result)`).'
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Validator/ValidatorCollector.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Validator/ValidatorCollector.php"
code = "mixed-operand"
message = "Casting `mixed` to `bool`."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Validator/ValidatorCollector.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\Validator\Result`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Validator/ValidatorInterfaceProxy.php"
code = "non-existent-class-like"
message = 'Class `AppDevPanel\Adapter\Yiisoft\Collector\Validator\ValidatorInterfaceProxy` cannot implement unknown type `ValidatorInterface`'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Validator/ValidatorInterfaceProxy.php"
code = "unused-property"
message = "Property `$collector` is never used."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/Validator/ValidatorInterfaceProxy.php"
code = "unused-property"
message = "Property `$validator` is never used."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/View/WebViewCollector.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Yiisoft\View\Event\WebView\AfterRender)`).'
count = 3

[[issues]]
file = "libs/Adapter/Yiisoft/src/Collector/View/WebViewCollector.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Yiisoft\View\Event\WebView\AfterRender`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/DebugServiceProvider.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `appdevpanel\adapter\yiisoft\proxy\containerinterfaceproxy::__construct`: expected `AppDevPanel\Adapter\Yiisoft\Proxy\ContainerProxyConfig`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Inspector/DbSchemaProvider.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Adapter\Yiisoft\Inspector\DbSchemaProvider::serializearcolumnsschemas`: expected `array<array-key, Yiisoft\Db\Schema\ColumnSchemaInterface>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Inspector/DbSchemaProvider.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `Yiisoft\Db\Query\Query::from`: expected `Yiisoft\Db\Expression\ExpressionInterface|array<array-key, mixed>|string`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Inspector/DbSchemaProvider.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Inspector/DbSchemaProvider.php"
code = "possible-method-access-on-null"
message = "Attempting to call a method on `null`."
count = 5

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "incompatible-property-visibility"
message = 'Property `$decorated` is defined differently in `AppDevPanel\Kernel\ProxyDecoratedCalls` and `appdevpanel\adapter\yiisoft\proxy\containerinterfaceproxy` used by `appdevpanel\adapter\yiisoft\proxy\containerinterfaceproxy`: visibility differs (public vs protected)'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Adapter\Yiisoft\Proxy\ContainerInterfaceProxy::getserviceproxyfromcallable`: expected `(callable(...mixed=): mixed)`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `AppDevPanel\Adapter\Yiisoft\Proxy\ContainerInterfaceProxy::getserviceproxyfromarray`: expected `array<array-key, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #3 of `AppDevPanel\Adapter\Yiisoft\Proxy\ContainerInterfaceProxy::getcommonmethodproxy`: expected `array<array-key, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\adapter\yiisoft\proxy\containerinterfaceproxy::getserviceproxy`. Saw type `nonnull`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\adapter\yiisoft\proxy\containerinterfaceproxy::getserviceproxyfromcallable`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "possibly-invalid-argument"
message = 'Possible argument type mismatch for argument #1 of `yiisoft\proxy\proxymanager::__construct`: expected `non-empty-string|null`, but possibly received `null|string`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerInterfaceProxy.php"
code = "unknown-class-instantiation"
message = "Cannot determine the concrete class for instantiation."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ContainerProxyConfig.php"
code = "property-type-coercion"
message = "A value of a less specific type `array<array-key, mixed>` is being assigned to property `$$decoratedServices` (array<string, mixed>)."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ProxyLogTrait.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #3 of `AppDevPanel\Adapter\Yiisoft\Proxy\ProxyLogTrait::processlogdata`: expected `null|object`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ProxyLogTrait.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ProxyLogTrait.php"
code = "non-existent-method"
message = 'Method `getcurrenterror` does not exist on type `AppDevPanel\Adapter\Yiisoft\Proxy\ProxyLogTrait`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ProxyLogTrait.php"
code = "non-existent-method"
message = 'Method `hascurrenterror` does not exist on type `AppDevPanel\Adapter\Yiisoft\Proxy\ProxyLogTrait`.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ServiceMethodProxy.php"
code = "invalid-callable"
message = "Expression of type `nonnull` cannot be called as a function or method."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ServiceMethodProxy.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ServiceMethodProxy.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ServiceMethodProxy.php"
code = "unsafe-instantiation"
message = 'Unsafe `new static()`: constructor of `AppDevPanel\Adapter\Yiisoft\Proxy\ServiceMethodProxy` is not final and its signature might change in child classes, potentially leading to runtime errors.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/ServiceProxy.php"
code = "unsafe-instantiation"
message = 'Unsafe `new static()`: constructor of `AppDevPanel\Adapter\Yiisoft\Proxy\ServiceProxy` is not final and its signature might change in child classes, potentially leading to runtime errors.'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/VarDumperHandlerInterfaceProxy.php"
code = "incompatible-property-visibility"
message = 'Property `$decorated` is defined differently in `AppDevPanel\Kernel\ProxyDecoratedCalls` and `appdevpanel\adapter\yiisoft\proxy\vardumperhandlerinterfaceproxy` used by `appdevpanel\adapter\yiisoft\proxy\vardumperhandlerinterfaceproxy`: visibility differs (public vs private protected(set))'
count = 1

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/VarDumperHandlerInterfaceProxy.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `str_ends_with`: expected `string`, but found `nonnull`."
count = 2

[[issues]]
file = "libs/Adapter/Yiisoft/src/Proxy/VarDumperHandlerInterfaceProxy.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Cli/src/Command/DebugQueryCommand.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/Cli/src/Command/DebugQueryCommand.php"
code = "mixed-operand"
message = "Casting `mixed` to `bool`."
count = 2

[[issues]]
file = "libs/Cli/src/Command/DebugServerBroadcastCommand.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Cli/src/Command/DebugServerCommand.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `Symfony\Component\Console\Style\SymfonyStyle::block`: expected `array<array-key, mixed>|string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Cli/src/Command/DebugServerCommand.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 3

[[issues]]
file = "libs/Cli/src/Command/DebugServerCommand.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/Cli/src/Command/ServeCommand.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `is_dir`: expected `string`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/Cli/src/Command/ServeCommand.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `mkdir`: expected `string`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/Cli/src/Command/ServeCommand.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `mixed`."
count = 2

[[issues]]
file = "libs/Cli/src/Command/ServeCommand.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 2

[[issues]]
file = "libs/Cli/src/Command/ServeCommand.php"
code = "mixed-argument"
message = "Invalid argument type for argument #3 of `sprintf`: expected `Stringable|null|scalar`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Cli/src/Command/ServeCommand.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/Cli/src/Command/ServeCommand.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/Cli/src/Server/server-router.php"
code = "invalid-argument"
message = "Invalid type for the first value."
count = 1

[[issues]]
file = "libs/Cli/src/Server/server-router.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `appdevpanel\api\inspector\controller\gitcontroller::__construct`: expected `AppDevPanel\Api\Inspector\Controller\GitRepositoryProvider`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Cli/src/Server/server-router.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Cli/src/Server/server-router.php"
code = "possibly-false-argument"
message = 'Argument #1 of method `appdevpanel\api\pathresolver::__construct` is possibly `false`, but parameter type `string` does not accept it.'
count = 1

[[issues]]
file = "libs/Cli/src/Server/server-router.php"
code = "possibly-null-argument"
message = "Argument #1 of function `str_starts_with` is possibly `null`, but parameter type `string` does not accept it."
count = 5

[[issues]]
file = "libs/Cli/src/Server/server-router.php"
code = "possibly-null-operand"
message = "Possibly null right operand used in string concatenation (type `null|string`)."
count = 1

[[issues]]
file = "libs/Cli/src/Server/server-router.php"
code = "redundant-comparison"
message = "Redundant `!==` comparison: left-hand side is always not identical to right-hand side."
count = 1

[[issues]]
file = "libs/Cli/src/Server/server-router.php"
code = "redundant-logical-operation"
message = "Redundant `&&` operation: left operand is evaluated and right operand is always truthy."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Console/CommandCollector.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_key_exists`: expected `bool|float|int|null|string`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Console/CommandCollector.php"
code = "mixed-array-access"
message = "Unsafe array access on type `nonnull`."
count = 3

[[issues]]
file = "libs/Kernel/src/Collector/Console/CommandCollector.php"
code = "mixed-array-index"
message = "Invalid index type `mixed` used for array access on `non-empty-array<array-key, mixed>`."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Console/CommandCollector.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/Kernel/src/Collector/Console/CommandCollector.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\console\commandcollector::fetchoutput`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/EventDispatcherInterfaceProxy.php"
code = "incompatible-property-visibility"
message = 'Property `$decorated` is defined differently in `AppDevPanel\Kernel\ProxyDecoratedCalls` and `appdevpanel\kernel\collector\eventdispatcherinterfaceproxy` used by `appdevpanel\kernel\collector\eventdispatcherinterfaceproxy`: visibility differs (public vs private protected(set))'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/HttpClientCollector.php"
code = "possibly-invalid-argument"
message = "Possible argument type mismatch for argument #1 of `array_sum`: expected `array<array-key, float|int>`, but possibly received `array<array-key, array<array-key, array<array-key, string>>|float|int|string>`."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/HttpClientCollector.php"
code = "possibly-null-array-index"
message = "Possibly using `null` as an array index to access elementof variable $this->requests."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/HttpClientInterfaceProxy.php"
code = "incompatible-property-visibility"
message = 'Property `$decorated` is defined differently in `AppDevPanel\Kernel\ProxyDecoratedCalls` and `appdevpanel\kernel\collector\httpclientinterfaceproxy` used by `appdevpanel\kernel\collector\httpclientinterfaceproxy`: visibility differs (public vs private protected(set))'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/LoggerInterfaceProxy.php"
code = "incompatible-property-visibility"
message = 'Property `$decorated` is defined differently in `AppDevPanel\Kernel\ProxyDecoratedCalls` and `appdevpanel\kernel\collector\loggerinterfaceproxy` used by `appdevpanel\kernel\collector\loggerinterfaceproxy`: visibility differs (public vs private protected(set))'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/LoggerInterfaceProxy.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Kernel\Collector\LogCollector::collect`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/LoggerInterfaceProxy.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 3

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamCollector.php"
code = "invalid-argument"
message = "Invalid argument type for argument #1 of `array_map`: expected `(callable(array-key): non-empty-array<string, non-negative-int>)|(callable(array-key, ('S.array_map() extends mixed)): non-empty-array<string, non-negative-int>)|null`, but found `(closure(string): non-empty-array<string, non-negative-int>)`."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "less-specific-argument"
message = 'Argument type mismatch for argument #1 of `AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector::collect`: expected `string`, but provided type `array-key` is less specific.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector::collect`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #3 of `AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector::collect`: expected `array<array-key, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 2

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::dir_closedir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::dir_opendir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::dir_readdir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::dir_rewinddir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::mkdir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::rename`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::rmdir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_cast`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_eof`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_flush`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_lock`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_metadata`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_open`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_read`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_seek`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_set_option`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_stat`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_tell`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_truncate`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::stream_write`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::unlink`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\filesystemstreamproxy::url_stat`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "string-member-selector"
message = "This member selector uses a non-literal string type (`string`); its specific value cannot be statically determined."
count = 2

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "unused-statement"
message = "Expression has no effect as a statement"
count = 3

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamCollector.php"
code = "invalid-argument"
message = "Invalid argument type for argument #1 of `array_map`: expected `(callable(array-key): non-empty-array<string, non-negative-int>)|(callable(array-key, ('S.array_map() extends mixed)): non-empty-array<string, non-negative-int>)|null`, but found `(closure(string): non-empty-array<string, non-negative-int>)`."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamCollector.php"
code = "mixed-array-assignment"
message = "Unsafe array assignment on type `mixed`."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "invalid-type-cast"
message = "Casting `mixed` to `array`."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "less-specific-argument"
message = 'Argument type mismatch for argument #1 of `AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector::collect`: expected `string`, but provided type `array-key` is less specific.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector::collect`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #3 of `AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector::collect`: expected `array<array-key, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 2

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::dir_closedir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::dir_opendir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::dir_readdir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::dir_rewinddir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::mkdir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::rename`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::rmdir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_cast`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_eof`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_flush`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_lock`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_metadata`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_open`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_read`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_seek`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_set_option`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_stat`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_tell`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_truncate`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::stream_write`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::unlink`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\collector\stream\httpstreamproxy::url_stat`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "possibly-null-argument"
message = "Argument #1 of function `stream_get_meta_data` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "string-member-selector"
message = "This member selector uses a non-literal string type (`string`); its specific value cannot be statically determined."
count = 2

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "unused-statement"
message = "Expression has no effect as a statement"
count = 3

[[issues]]
file = "libs/Kernel/src/Collector/Web/RequestCollector.php"
code = "mixed-property-type-coercion"
message = "A value with a less specific type `mixed` is being assigned to property `$$userIp` (null|string)."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Web/RequestCollector.php"
code = "possibly-null-argument"
message = 'Argument #1 of method `guzzlehttp\psr7\message::rewindbody` is possibly `null`, but parameter type `Psr\Http\Message\MessageInterface` does not accept it.'
count = 2

[[issues]]
file = "libs/Kernel/src/DebugServer/Broadcaster.php"
code = "possibly-false-argument"
message = "Argument #1 of function `fclose` is possibly `false`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/Broadcaster.php"
code = "possibly-false-argument"
message = 'Argument #1 of method `AppDevPanel\Kernel\DebugServer\Broadcaster::fwritestream` is possibly `false`, but parameter type `resource` does not accept it.'
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/Broadcaster.php"
code = "possibly-false-iterator"
message = "Expression being iterated (type `false|list<non-empty-string>`) might be `false` at runtime."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/Broadcaster.php"
code = "possibly-invalid-argument"
message = "Possible argument type mismatch for argument #1 of `usleep`: expected `non-negative-int`, but possibly received `int`."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/Broadcaster.php"
code = "possibly-undefined-variable"
message = "Variable `$errno` might not have been defined on all execution paths leading to this point."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/Broadcaster.php"
code = "reference-to-undefined-variable"
message = "Reference created from a previously undefined variable `$errstr`."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/Connection.php"
code = "possibly-false-argument"
message = "Argument #1 of function `socket_last_error` is possibly `false`, but parameter type `Socket|null` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/Connection.php"
code = "possibly-false-argument"
message = 'Argument #1 of method `appdevpanel\kernel\debugserver\connection::__construct` is possibly `false`, but parameter type `Socket` does not accept it.'
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/Connection.php"
code = "reference-to-undefined-variable"
message = "Reference created from a previously undefined variable `$path`."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "invalid-yield-value-type"
message = "Invalid value type yielded; expected `array{0: int(27)|int(43)|int(59), 1: string, 2: int|string, 3?: int}`, but found `list{int(27), false|string}`."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "invalid-yield-value-type"
message = "Invalid value type yielded; expected `array{0: int(27)|int(43)|int(59), 1: string, 2: int|string, 3?: int}`, but found `list{int(43), int<36, max>|int<min, 34>, string}`."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "invalid-yield-value-type"
message = "Invalid value type yielded; expected `array{0: int(27)|int(43)|int(59), 1: string, 2: int|string, 3?: int}`, but found `list{int(59), int, string}`."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "possibly-invalid-argument"
message = "Possible argument type mismatch for argument #1 of `usleep`: expected `non-negative-int`, but possibly received `int`."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "redundant-cast"
message = "Redundant cast to `(string)`: the expression already has this type."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "reference-to-undefined-variable"
message = "Reference created from a previously undefined variable `$buffer`."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "reference-to-undefined-variable"
message = "Reference created from a previously undefined variable `$header`."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "reference-to-undefined-variable"
message = "Reference created from a previously undefined variable `$path`."
count = 1

[[issues]]
file = "libs/Kernel/src/Debugger.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `yiisoft\strings\wildcardpattern::__construct`: expected `string`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/Kernel/src/Debugger.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "falsable-return-statement"
message = '''Function `appdevpanel\kernel\dumper::asjsoninternal` is declared to return `string` but possibly returns 'false' (inferred as `false|non-empty-string`).'''
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "invalid-iterator"
message = "The expression provided to `foreach` is not iterable. It resolved to type `mixed`, which is not iterable."
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "invalid-return-statement"
message = 'Invalid return type for function `appdevpanel\kernel\dumper::asjsoninternal`: expected `string`, but found `false|non-empty-string`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "invalid-type-cast"
message = "Casting `mixed` to `array`."
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "less-specific-nested-argument-type"
message = "Argument type mismatch for argument #1 of `array_flip`: expected `array<array-key, ('V.array_flip() extends array-key)>`, but provided type `array<array-key, mixed>` is less specific."
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Kernel\Dumper::getobjectdescription`: expected `object`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `AppDevPanel\Kernel\Dumper::getobjectproperties`: expected `object`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_key_exists`: expected `bool|float|int|null|string`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `count`: expected `Countable|array<array-key, mixed>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 7

[[issues]]
file = "libs/Kernel/src/Dumper.php"
code = "template-constraint-violation"
message = "Argument type mismatch for template `V`."
count = 1

[[issues]]
file = "libs/Kernel/src/FlattenException.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Kernel/src/FlattenException.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\flattenexception::getcode`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/FlattenException.php"
code = "possibly-invalid-argument"
message = '''Possible argument type mismatch for argument #1 of `AppDevPanel\Kernel\FlattenException::settrace`: expected `list<array{'args'?: array<array-key, mixed>, 'class'?: class-string, 'file'?: string, 'function'?: string, 'line'?: int, 'type'?: string}>`, but possibly received `array<array-key, mixed>`.'''
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/BacktraceIgnoreMatcher.php"
code = "less-specific-nested-argument-type"
message = 'Argument type mismatch for argument #1 of `yiisoft\strings\combinedregexp::__construct`: expected `array<array-key, string>`, but provided type `non-empty-array<array-key, mixed>` is less specific.'
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/BacktraceIgnoreMatcher.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `appdevpanel\kernel\helper\backtraceignorematcher::doesstringmatchpattern`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/BacktraceIgnoreMatcher.php"
code = "mixed-array-access"
message = "Unsafe array access on type `nonnull`."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/BacktraceIgnoreMatcher.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "docblock-type-mismatch"
message = "Docblock return type `resource` is incompatible with native return type `void`."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "falsable-return-statement"
message = '''Function `appdevpanel\kernel\helper\streamwrapper\streamwrapper::stream_tell` is declared to return `int` but possibly returns 'false' (inferred as `false|non-negative-int`).'''
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "falsable-return-statement"
message = '''Function `appdevpanel\kernel\helper\streamwrapper\streamwrapper::stream_write` is declared to return `int` but possibly returns 'false' (inferred as `false|non-negative-int`).'''
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "invalid-property-assignment-value"
message = "Invalid type for property `$stream`: expected `null|resource`, but got `false|resource`."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "invalid-return-statement"
message = 'Invalid return type for function `appdevpanel\kernel\helper\streamwrapper\streamwrapper::stream_tell`: expected `int`, but found `false|non-negative-int`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "invalid-return-statement"
message = 'Invalid return type for function `appdevpanel\kernel\helper\streamwrapper\streamwrapper::stream_write`: expected `int`, but found `false|non-negative-int`.'
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "missing-return-statement"
message = "Missing return statement in function 'stream_cast'"
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "missing-return-statement"
message = "Missing return statement in function 'url_stat'"
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `chgrp`: expected `int|string`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `chmod`: expected `int`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `chown`: expected `int|string`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `feof` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `fflush` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `flock` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `fread` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `fseek` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `fstat` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `ftell` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `ftruncate` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `fwrite` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `readdir` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `stream_set_blocking` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `stream_set_timeout` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "possibly-null-argument"
message = "Argument #1 of function `stream_set_write_buffer` is possibly `null`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "redundant-type-comparison"
message = "Redundant type assertion: `$this->stream` of type `open-resource` is always not `open-resource`."
count = 1

[[issues]]
file = "libs/Kernel/src/ProxyDecoratedCalls.php"
code = "ambiguous-object-property-access"
message = "Cannot statically verify property access on a generic `object` type."
count = 2

[[issues]]
file = "libs/Kernel/src/ProxyDecoratedCalls.php"
code = "string-member-selector"
message = "This member selector uses a non-literal string type (`string`); its specific value cannot be statically determined."
count = 3

[[issues]]
file = "libs/Kernel/src/Service/ServiceDescriptor.php"
code = "invalid-type-cast"
message = "Casting `mixed` to `array`."
count = 1

[[issues]]
file = "libs/Kernel/src/Service/ServiceDescriptor.php"
code = "invalid-type-cast"
message = "Casting `mixed` to `float`."
count = 2

[[issues]]
file = "libs/Kernel/src/Service/ServiceDescriptor.php"
code = "less-specific-nested-argument-type"
message = 'Argument type mismatch for argument #4 of `appdevpanel\kernel\service\servicedescriptor::__construct`: expected `array<array-key, string>`, but provided type `array<array-key, mixed>` is less specific.'
count = 1

[[issues]]
file = "libs/Kernel/src/Storage/FileStorage.php"
code = "possibly-false-argument"
message = "Argument #1 of function `uasort` is possibly `false`, but parameter type `array<non-negative-int, non-empty-string>` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/src/Storage/FileStorage.php"
code = "possibly-false-argument"
message = 'Argument #1 of method `yiisoft\json\json::decode` is possibly `false`, but parameter type `string` does not accept it.'
count = 1

[[issues]]
file = "libs/Kernel/src/Storage/FileStorage.php"
code = "possibly-false-operand"
message = "Left operand in spaceship comparison (`<=>`) might be `false` (type `false|int<1750595956, max>`)."
count = 2

[[issues]]
file = "libs/Kernel/src/Storage/FileStorage.php"
code = "possibly-false-operand"
message = "Right operand in spaceship comparison (`<=>`) might be `false` (type `false|int<1750595956, max>`)."
count = 2

[[issues]]
file = "libs/Kernel/src/Storage/MemoryStorage.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_merge`: expected `array<array-key, mixed>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Shared/AbstractCollectorTestCase.php"
code = "possibly-null-argument"
message = 'Argument #1 of method `AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase::checksummarydata` is possibly `null`, but parameter type `array<array-key, mixed>` does not accept it.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/BrokenProxyImplementation.php"
code = "unused-property"
message = "Property `$decorated` is never used."
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::dir_closedir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::dir_opendir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::dir_readdir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::dir_rewinddir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::mkdir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::rename`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::rmdir`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_cast`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_eof`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_flush`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_lock`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_metadata`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_open`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_read`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_seek`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_set_option`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_stat`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_tell`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_truncate`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::stream_write`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::unlink`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "mixed-return-statement"
message = 'Could not infer a precise return type for function `appdevpanel\kernel\tests\support\stub\phpstreamproxy::url_stat`. Saw type `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "string-member-selector"
message = "This member selector uses a non-literal string type (`string`); its specific value cannot be statically determined."
count = 2

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "unused-statement"
message = "Expression has no effect as a statement"
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/ThreeProperties.php"
code = "unused-property"
message = "Property `$third` is never used."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/CommandCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/CommandCollectorTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 4

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/CommandCollectorTest.php"
code = "non-existent-method"
message = 'Method `collect` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 3

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ConsoleAppInfoCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 7

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ConsoleAppInfoCollectorTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 6

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ConsoleAppInfoCollectorTest.php"
code = "non-existent-method"
message = 'Method `collect` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 3

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ConsoleAppInfoCollectorTest.php"
code = "non-existent-method"
message = 'Method `markapplicationfinished` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ConsoleAppInfoCollectorTest.php"
code = "non-existent-method"
message = 'Method `markapplicationstarted` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/EventCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #1 of `phpunit\framework\assert::assertfileexists`: expected `string`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/EventCollectorTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/EventCollectorTest.php"
code = "non-existent-method"
message = 'Method `collect` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/EventDispatcherInterfaceProxyTest.php"
code = "missing-return-statement"
message = "Missing return statement in function 'dispatch'"
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/EventDispatcherInterfaceProxyTest.php"
code = "non-documented-method"
message = 'Ambiguous method call to `getproxiedcall` on class `AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/EventDispatcherInterfaceProxyTest.php"
code = "non-documented-method"
message = 'Ambiguous method call to `setproxiedcall` on class `AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/EventDispatcherInterfaceProxyTest.php"
code = "non-documented-property"
message = 'Ambiguous property access: $var on class `AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ExceptionCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 12

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ExceptionCollectorTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 9

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ExceptionCollectorTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ExceptionCollectorTest.php"
code = "non-existent-method"
message = 'Method `collect` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/FilesystemStreamCollectorTest.php"
code = "incompatible-parameter-type"
message = 'Parameter `$collector` of `AppDevPanel\Kernel\Tests\Unit\Collector\FilesystemStreamCollectorTest::collecttestdata()` expects type `AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector` but parent `AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase::collecttestdata()` expects type `AppDevPanel\Kernel\Collector\CollectorInterface`'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/FilesystemStreamCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertcount`: expected `Countable|iterable<mixed, mixed>`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientCollectorTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `round`: expected `float|int`, but found `mixed`."
count = 3

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertcount`: expected `Countable|iterable<mixed, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientCollectorTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 23

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientCollectorTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 3

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientCollectorTest.php"
code = "non-existent-method"
message = 'Method `collect` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 3

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientCollectorTest.php"
code = "non-existent-method"
message = 'Method `collecttotaltime` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 3

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientInterfaceProxyTest.php"
code = "missing-return-statement"
message = "Missing return statement in function 'sendrequest'"
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientInterfaceProxyTest.php"
code = "non-documented-method"
message = 'Ambiguous method call to `getproxiedcall` on class `AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientInterfaceProxyTest.php"
code = "non-documented-method"
message = 'Ambiguous method call to `setproxiedcall` on class `AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpClientInterfaceProxyTest.php"
code = "non-documented-property"
message = 'Ambiguous property access: $var on class `AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpStreamCollectorTest.php"
code = "incompatible-parameter-type"
message = 'Parameter `$collector` of `AppDevPanel\Kernel\Tests\Unit\Collector\HttpStreamCollectorTest::collecttestdata()` expects type `AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector` but parent `AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase::collecttestdata()` expects type `AppDevPanel\Kernel\Collector\CollectorInterface`'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpStreamCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpStreamCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertcount`: expected `Countable|iterable<mixed, mixed>`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpStreamCollectorTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 4

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpStreamCollectorTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpStreamCollectorTest.php"
code = "redundant-comparison"
message = "Redundant `!==` comparison: left-hand side is always not identical to right-hand side."
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/HttpStreamCollectorTest.php"
code = "redundant-logical-operation"
message = "Redundant `&&` operation: left operand is always truthy and right operand is evaluated."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/LogCollectorTest.php"
code = "non-existent-method"
message = 'Method `collect` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/LoggerInterfaceProxyTest.php"
code = "non-documented-method"
message = 'Ambiguous method call to `getproxiedcall` on class `AppDevPanel\Kernel\Collector\LoggerInterfaceProxy`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/LoggerInterfaceProxyTest.php"
code = "non-documented-method"
message = 'Ambiguous method call to `setproxiedcall` on class `AppDevPanel\Kernel\Collector\LoggerInterfaceProxy`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/LoggerInterfaceProxyTest.php"
code = "non-documented-property"
message = 'Ambiguous property access: $var on class `AppDevPanel\Kernel\Collector\LoggerInterfaceProxy`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/LoggerInterfaceProxyTest.php"
code = "string-member-selector"
message = "This member selector uses a non-literal string type (`string`); its specific value cannot be statically determined."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/RequestCollectorTest.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 13

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/RequestCollectorTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 3

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/RequestCollectorTest.php"
code = "non-existent-method"
message = 'Method `collectrequest` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/RequestCollectorTest.php"
code = "non-existent-method"
message = 'Method `collectresponse` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/ServiceCollectorTest.php"
code = "non-existent-method"
message = 'Method `collect` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/Stream/FilesystemStreamProxyTest.php"
code = "impossible-type-comparison"
message = 'Impossible type assertion: `AppDevPanel\Kernel\Collector\Stream\FilesystemStreamProxy::$registered` of type `false` can never be `true`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/Stream/FilesystemStreamProxyTest.php"
code = "invalid-property-assignment-value"
message = "Invalid type for property `$stream`: expected `null|resource`, but got `false|resource`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/Stream/FilesystemStreamProxyTest.php"
code = "no-value"
message = 'Argument #1 passed to method `phpunit\framework\assert::asserttrue` has type `never`, meaning it cannot produce a value.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/Stream/FilesystemStreamProxyTest.php"
code = "possibly-false-argument"
message = "Argument #1 of function `readdir` is possibly `false`, but parameter type `resource` does not accept it."
count = 4

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/Stream/FilesystemStreamProxyTest.php"
code = "possibly-false-argument"
message = "Argument #1 of function `rewinddir` is possibly `false`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/Stream/FilesystemStreamProxyTest.php"
code = "redundant-type-comparison"
message = 'Redundant type assertion: `AppDevPanel\Kernel\Collector\Stream\FilesystemStreamProxy::$registered` of type `never` is always not `true`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/Stream/HttpStreamProxyTest.php"
code = "impossible-type-comparison"
message = 'Impossible type assertion: `AppDevPanel\Kernel\Collector\Stream\HttpStreamProxy::$registered` of type `false` can never be `true`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/Stream/HttpStreamProxyTest.php"
code = "no-value"
message = 'Argument #1 passed to method `phpunit\framework\assert::asserttrue` has type `never`, meaning it cannot produce a value.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/Stream/HttpStreamProxyTest.php"
code = "redundant-type-comparison"
message = 'Redundant type assertion: `AppDevPanel\Kernel\Collector\Stream\HttpStreamProxy::$registered` of type `never` is always not `true`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/TimelineCollectorTest.php"
code = "less-specific-argument"
message = 'Argument type mismatch for argument #1 of `appdevpanel\kernel\collector\logcollector::__construct`: expected `AppDevPanel\Kernel\Collector\TimelineCollector`, but provided type `AppDevPanel\Kernel\Collector\CollectorInterface|AppDevPanel\Kernel\Collector\TimelineCollector` is less specific.'
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/TimelineCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertcount`: expected `Countable|iterable<mixed, mixed>`, but found `mixed`.'
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/TimelineCollectorTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 6

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/TimelineCollectorTest.php"
code = "non-existent-method"
message = 'Method `collect` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/VarDumperCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertarrayhaskey`: expected `ArrayAccess<array-key, mixed>|array<array-key, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/VarDumperCollectorTest.php"
code = "mixed-argument"
message = 'Invalid argument type for argument #2 of `phpunit\framework\assert::assertcount`: expected `Countable|iterable<mixed, mixed>`, but found `mixed`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/VarDumperCollectorTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 3

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/VarDumperCollectorTest.php"
code = "non-existent-method"
message = 'Method `collect` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/WebAppInfoCollectorTest.php"
code = "non-existent-method"
message = 'Method `markrequestfinished` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Collector/WebAppInfoCollectorTest.php"
code = "non-existent-method"
message = 'Method `markrequeststarted` does not exist on type `AppDevPanel\Kernel\Collector\CollectorInterface`.'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DebugServer/BroadcasterTest.php"
code = "redundant-type-comparison"
message = "Redundant type assertion: `$errors` of type `array<array-key, mixed>` is always not `array<array-key, mixed>`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "ambiguous-object-property-access"
message = "Cannot statically verify property access on a generic `object` type."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "invalid-argument"
message = "Invalid argument type for argument #1 of `spl_object_id`: expected `object`, but found `(closure(): DateTimeZone)`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "invalid-argument"
message = "Invalid argument type for argument #1 of `spl_object_id`: expected `object`, but found `(closure(): int(1))`."
count = 5

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "invalid-argument"
message = "Invalid argument type for argument #1 of `spl_object_id`: expected `object`, but found `(closure(): null|string)`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "invalid-argument"
message = "Invalid argument type for argument #1 of `spl_object_id`: expected `object`, but found `(closure(): true)`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "invalid-argument"
message = 'Invalid argument type for argument #1 of `spl_object_id`: expected `object`, but found `(closure(AppDevPanel\Kernel\Dumper): DateTimeZone)`.'
count = 3

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `spl_object_id`: expected `object`, but found `mixed`."
count = 9

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 20

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "mixed-property-access"
message = "Attempting to access a property on a non-object type (`mixed`)."
count = 11

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "possibly-false-argument"
message = "Argument #1 of function `fclose` is possibly `false`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "possibly-false-argument"
message = "Argument #1 of function `spl_object_id` is possibly `false`, but parameter type `object` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "possibly-false-argument"
message = "Argument #1 of function `stream_get_meta_data` is possibly `false`, but parameter type `resource` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/DumperTest.php"
code = "string-member-selector"
message = "This member selector uses a non-literal string type (`truthy-lowercase-string`); its specific value cannot be statically determined."
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/FlattenExceptionTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 4

[[issues]]
file = "libs/Kernel/tests/Unit/FlattenExceptionTest.php"
code = "possible-method-access-on-null"
message = "Attempting to call a method on `null`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/FlattenExceptionTest.php"
code = "unevaluated-code"
message = "Unreachable code detected."
count = 48

[[issues]]
file = "libs/Kernel/tests/Unit/Helper/BacktraceIgnoreMatcherTest.php"
code = "possibly-false-argument"
message = "Argument #1 of function `dirname` is possibly `false`, but parameter type `string` does not accept it."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Helper/BacktraceIgnoreMatcherTest.php"
code = "possibly-false-argument"
message = "Argument #1 of function `preg_quote` is possibly `false`, but parameter type `string` does not accept it."
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Helper/StreamWrapper/StreamWrapperTest.php"
code = "non-existent-property"
message = 'Property `$stream` does not exist on interface `AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapperInterface`.'
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/ProxyDecoratedCallsTest.php"
code = "ambiguous-object-method-access"
message = "Cannot statically verify method call on a generic `object` type."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/ProxyDecoratedCallsTest.php"
code = "ambiguous-object-property-access"
message = "Cannot statically verify property access on a generic `object` type."
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/ProxyDecoratedCallsTest.php"
code = "incompatible-property-visibility"
message = 'Property `$decorated` is defined differently in `AppDevPanel\Kernel\ProxyDecoratedCalls` and `class@anonymous:9720836125357557984-1142:1323` used by `class@anonymous:9720836125357557984-1142:1323`: visibility differs (public vs private protected(set))'
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Service/FileServiceRegistryTest.php"
code = "possibly-false-iterator"
message = "Expression being iterated (type `false|list<non-empty-string>`) might be `false` at runtime."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Storage/AbstractStorageTestCase.php"
code = "missing-magic-method"
message = "Call to documented magic method `method()` on a class that cannot handle it."
count = 7

[[issues]]
file = "libs/Kernel/tests/Unit/Storage/AbstractStorageTestCase.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/Kernel/tests/Unit/Storage/AbstractStorageTestCase.php"
code = "too-few-arguments"
message = 'Too few arguments provided for method `AppDevPanel\Kernel\Storage\StorageInterface::read`.'
count = 1

[[issues]]
file = "libs/Testing/src/Assertion/ExpectationEvaluator.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/Testing/src/Command/DebugFixturesCommand.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 2

[[issues]]
file = "libs/Testing/src/Command/DebugFixturesCommand.php"
code = "possibly-invalid-argument"
message = 'Possible argument type mismatch for argument #1 of `AppDevPanel\Testing\Runner\FixtureRunner::runall`: expected `list<AppDevPanel\Testing\Fixture\Fixture>`, but possibly received `non-empty-array<array-key, mixed>`.'
count = 1

[[issues]]
file = "libs/Testing/src/Command/DebugFixturesCommand.php"
code = "redundant-cast"
message = "Redundant cast to `(string)`: the expression already has this type."
count = 2

[[issues]]
file = "libs/Testing/src/Runner/FixtureResult.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Testing/src/Runner/FixtureResult.php"
code = "mixed-property-access"
message = "Attempting to access a property on a non-object type (`mixed`)."
count = 1

[[issues]]
file = "libs/Testing/src/Runner/FixtureResult.php"
code = "possibly-invalid-argument"
message = 'Possible argument type mismatch for argument #3 of `appdevpanel\testing\runner\fixtureresult::__construct`: expected `list<AppDevPanel\Testing\Assertion\AssertionResult>`, but possibly received `array<array-key, mixed>`.'
count = 1

[[issues]]
file = "libs/Testing/src/Runner/FixtureRunner.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/Testing/src/Runner/FixtureRunner.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Testing/src/Runner/FixtureRunner.php"
code = "possibly-invalid-argument"
message = "Possible argument type mismatch for argument #1 of `usleep`: expected `non-negative-int`, but possibly received `int`."
count = 2

[[issues]]
file = "libs/Testing/src/Runner/FixtureRunner.php"
code = "redundant-logical-operation"
message = "Redundant `&&` operation: left operand is always truthy and right operand is evaluated."
count = 2

[[issues]]
file = "libs/Testing/src/Runner/FixtureRunner.php"
code = "redundant-type-comparison"
message = "Redundant type assertion: `$key` of type `string` is always not `string`."
count = 4

[[issues]]
file = "libs/Testing/tests/E2E/DebugApiTest.php"
code = "less-specific-argument"
message = "Argument type mismatch for argument #1 of `urlencode`: expected `string`, but provided type `array-key` is less specific."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/DebugApiTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `array_keys`: expected `array<('K.array_keys() extends array-key), ('V.array_keys() extends mixed)>`, but found `mixed`."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/DebugApiTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #1 of `reset`: expected `array<('K.reset() extends mixed), ('V.reset() extends mixed)>|object`, but found `mixed`."
count = 3

[[issues]]
file = "libs/Testing/tests/E2E/DebugApiTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 4

[[issues]]
file = "libs/Testing/tests/E2E/DebugApiTest.php"
code = "mixed-array-access"
message = "Unsafe array access on type `mixed`."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/DebugApiTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 17

[[issues]]
file = "libs/Testing/tests/E2E/FixtureTestCase.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #2 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "mixed-argument"
message = "Invalid argument type for argument #3 of `sprintf`: expected `Stringable|null|scalar`, but found `nonnull`."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 4

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "mixed-assignment"
message = "Assigning `nonnull` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "redundant-cast"
message = "Redundant cast to `(string)`: the expression already has this type."
count = 4

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "redundant-condition"
message = "Redundant ternary operator: condition is always truthy."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "redundant-type-comparison"
message = "Redundant type assertion: `$body` of type `array<string, mixed>` is always not `array<array-key, mixed>`."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "redundant-type-comparison"
message = "Redundant type assertion: `$entry` of type `array<string, mixed>` is always not `array<array-key, mixed>`."
count = 1

[[issues]]
file = "libs/Testing/tests/E2E/ScenarioTest.php"
code = "redundant-type-comparison"
message = "Redundant type assertion: `$key` of type `string` is always not `string`."
count = 1
