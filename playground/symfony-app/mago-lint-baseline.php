variant = "loose"

[[issues]]
file = "src/Controller/PageController.php"
code = "no-empty"
message = "Use of the `empty` construct."
count = 4

[[issues]]
file = "src/Controller/TestFixtures/CoverageAction.php"
code = "prefer-static-closure"
message = "This arrow function does not use `$this` and should be declared static."
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
