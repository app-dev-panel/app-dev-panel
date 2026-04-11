# Декораторы PSR-интерфейсов

```php
final class LoggerInterfaceProxy implements LoggerInterface
{
    public function log($level, string $message, array $context = []): void
    {
        $this->collector->record($level, $message, $context);

        $this->original->log($level, $message, $context);
    }
}
```

- у Monolog есть процессоры — но только для Monolog
- дебаггер должен уметь с каждой библиотекой — PSR помогает

<!--
Speaker notes:
Возьмём логгер. Monolog сам предоставляет расширяемость —
процессоры, форматтеры. Можно добавить свой процессор
и писать данные в дебаггер прямо оттуда.

Но это работает только с Monolog. Используешь что-то
другое — пишешь интеграцию с нуля. А у дебаггера таких
сервисов — десятки: кэш, HTTP-клиент, event dispatcher.

PSR решает это в корне. Если сервис реализует PSR-3
LoggerInterface — прокси заворачивает его одинаково,
независимо от реализации. Один подход, любая библиотека.

Адаптер делает две вещи: пишет данные в коллектор,
потом передаёт вызов в настоящий сервис. Снаружи ничего
не меняется — $logger->info() работает как раньше.
-->
