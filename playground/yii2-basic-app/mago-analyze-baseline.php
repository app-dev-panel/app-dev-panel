variant = "loose"

[[issues]]
file = "config/console.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "config/web.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "config/web.php"
code = "non-existent-class-like"
message = 'Class, interface, enum, or trait `yii\web\Response` not found.'
count = 1

[[issues]]
file = "config/web.php"
code = "redundant-condition"
message = "Redundant ternary operator: condition is always truthy."
count = 1

[[issues]]
file = "public/index.php"
code = "mixed-assignment"
message = "Assigning `mixed` type to a variable may lead to unexpected behavior."
count = 1

[[issues]]
file = "public/index.php"
code = "non-existent-class"
message = 'Class `yii\web\Application` not found.'
count = 1

[[issues]]
file = "public/index.php"
code = "unused-statement"
message = "Expression has no effect as a statement"
count = 2

[[issues]]
file = "src/commands/TestLoggingController.php"
code = "non-existent-class-like"
message = 'Class `App\commands\TestLoggingController` cannot extend unknown type `Controller`'
count = 1

[[issues]]
file = "src/controllers/SiteController.php"
code = "non-existent-class-like"
message = 'Class `App\controllers\SiteController` cannot extend unknown type `Controller`'
count = 1
