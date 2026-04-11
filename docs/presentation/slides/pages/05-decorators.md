# Техничка: декораторы

Фреймворк с PSR-интерфейсами — **мечта дебаггера**.

Интерфейс в коде очень легко накрыть прокси-адаптером:

```php {all|1-3|5-15|8|9|all}
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

Сначала пишем в коллектор. Потом передаём вызов дальше. Или наоборот.

<!--
Speaker notes:
Показываем, что прокси — это просто. На этом слайде магия
shiki — строки подсвечиваются по шагам.
-->
