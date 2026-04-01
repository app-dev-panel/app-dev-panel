variant = "loose"

[[issues]]
file = "app/Http/Controllers/PageController.php"
code = "no-empty"
message = "Use of the `empty` construct."
count = 4

[[issues]]
file = "app/Http/Controllers/TestFixtures/CoverageAction.php"
code = "prefer-static-closure"
message = "This arrow function does not use `$this` and should be declared static."
count = 1

[[issues]]
file = "app/Http/Controllers/TestFixtures/DumpAction.php"
code = "no-debug-symbols"
message = "Do not commit debug functions."
count = 1

[[issues]]
file = "app/Providers/AppServiceProvider.php"
code = "no-empty-comment"
message = "Empty comments are not allowed."
count = 2

[[issues]]
file = "bootstrap/app.php"
code = "no-empty-comment"
message = "Empty comments are not allowed."
count = 2

[[issues]]
file = "routes/api.php"
code = "no-redundant-file"
message = "Redundant file with no executable code or declarations."
count = 1
