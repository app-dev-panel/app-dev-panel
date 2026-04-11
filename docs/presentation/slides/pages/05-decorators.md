# Техничка: декораторы

Фреймворк с PSR-интерфейсами — **мечта дебаггера**.

Интерфейс в коде очень легко накрыть прокси-адаптером:

```php {1-4|6-14|10|12|all}
interface LoggerInterface
{
    public function log($level, string $message, array $context = []): void;
}

final class LoggerInterfaceProxy implements LoggerInterface
{
    public function log($level, string $message, array $context = []): void
    {
        $this->collector->record($level, $message, $context);

        $this->original->log($level, $message, $context);
    }
}
```

<v-click>

Сначала пишем в коллектор. Потом передаём вызов дальше. Или наоборот.

</v-click>

<!--
Speaker notes:
Показываем, что прокси — это просто. На этом слайде магия
shiki — строки подсвечиваются по шагам.
-->
