variant = "loose"

[[issues]]
file = "libs/API/tests/Unit/Inspector/Controller/FileControllerTest.php"
code = "no-isset"
message = "Use of the `isset` construct."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 1

[[issues]]
file = "libs/Kernel/src/Collector/Stream/HttpStreamProxy.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 2

[[issues]]
file = "libs/Kernel/src/DebugServer/Broadcaster.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 1

[[issues]]
file = "libs/Kernel/src/DebugServer/Connection.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 2

[[issues]]
file = "libs/Kernel/src/DebugServer/SocketReader.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 2

[[issues]]
file = "libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Support/Stub/PhpStreamProxy.php"
code = "no-error-control-operator"
message = "Unsafe use of error control operator `@`."
count = 1

[[issues]]
file = "libs/Kernel/tests/Unit/Helper/BacktraceIgnoreMatcherTest.php"
code = "require-preg-quote-delimiter"
message = "Missing delimiter argument in `preg_quote()` call"
count = 5
