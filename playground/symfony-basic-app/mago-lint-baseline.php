variant = "loose"

[[issues]]
file = "config/reference.php"
code = "inline-variable-return"
message = "Variable assignment can be inlined into the return statement."
count = 1

[[issues]]
file = "config/reference.php"
code = "strict-types"
message = "Missing `declare(strict_types=1);` statement at the beginning of the file."
count = 1

[[issues]]
file = "src/Controller/TestFixtures/DumpAction.php"
code = "no-debug-symbols"
message = "Do not commit debug functions."
count = 1

[[issues]]
file = "src/Controller/TestFixtures/ExceptionAction.php"
code = "no-redundant-use"
message = "Unused import: `JsonResponse`."
count = 1

[[issues]]
file = "src/Controller/TestFixtures/ExceptionChainedAction.php"
code = "no-redundant-use"
message = "Unused import: `JsonResponse`."
count = 1
