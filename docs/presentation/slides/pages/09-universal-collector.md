# Коллектор с ручным API — это просто дампер

```php
public function show(
    int $id,
    UserRepository $users,
    Collector $collector,
): Response {
    $user   = $users->find($id);
    $collector->dump($user);                  // объект

    $orders = $this->orders->forUser($user);
    $collector->dump($orders);                // массив

    $stats  = $this->db->query('SHOW STATUS');
    $collector->dump($stats);                 // результат SQL
}
```

<!--
Speaker notes:
Была идея дать пользователям вручную дёргать коллекторы —
чтобы они могли вывести в панель что угодно. Представь,
как это выглядело бы в реальном контроллере: объект
пользователя, массив заказов, результат SQL-запроса,
всё в одно и то же API.

И тут я остановился. Это же просто дампер. Обычный
dump() или var_dump(), только с другим именем. Пользователь
и так уже этим пользуется каждый день. Зачем ему учить
ещё один API?

Следующий слайд показывает честное решение — перехват
существующего handler'а.
-->
