variant = "loose"

[[issues]]
file = "config/routes/app_dev_panel.php"
code = "invalid-method-access"
message = 'Attempting to access a method on a non-object type (`unknown-ref(Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator)`).'
count = 1

[[issues]]
file = "config/routes/app_dev_panel.php"
code = "non-existent-class-like"
message = 'Cannot find class, interface, enum, or type alias `Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator`.'
count = 1

[[issues]]
file = "public/index.php"
code = "mixed-operand"
message = "Casting `mixed` to `bool`."
count = 1

[[issues]]
file = "public/index.php"
code = "too-many-arguments"
message = 'Class `App\Kernel` has no `__construct` method, but arguments were provided to `new`.'
count = 1

[[issues]]
file = "src/Command/TestLoggingCommand.php"
code = "non-existent-attribute-class"
message = 'Attribute class `Symfony\Component\Console\Attribute\AsCommand` not found or could not be autoloaded.'
count = 1

[[issues]]
file = "src/Command/TestLoggingCommand.php"
code = "non-existent-class-like"
message = 'Class `App\Command\TestLoggingCommand` cannot extend unknown type `Command`'
count = 1

[[issues]]
file = "src/Command/TestLoggingCommand.php"
code = "unused-method"
message = "Method `execute()` is never used."
count = 1

[[issues]]
file = "src/Command/TestLoggingCommand.php"
code = "unused-property"
message = "Property `$logger` is never used."
count = 1

[[issues]]
file = "src/Controller/HomeController.php"
code = "non-existent-class-like"
message = 'Class `App\Controller\HomeController` cannot extend unknown type `AbstractController`'
count = 1

[[issues]]
file = "src/Kernel.php"
code = "non-existent-class-like"
message = 'Class `App\Kernel` cannot extend unknown type `BaseKernel`'
count = 1

[[issues]]
file = "src/Kernel.php"
code = "non-existent-class-like"
message = 'Class `App\Kernel` cannot use unknown type `MicroKernelTrait`'
count = 1
