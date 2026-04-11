# Универсальный коллектор

Была идея: дать пользователю **вручную** дёргать коллекторы.

<v-click>

Как это выглядело бы в контроллере:

```php
public function show(int $id): Response
{
    $user = $this->users->find($id);
    $collector->dump($user);                   // объект

    $orders = $this->orders->forUser($user);
    $collector->dump($orders);                 // массив

    $stats = $this->db->query('SHOW STATUS');
    $collector->dump($stats);                  // результат SQL

    return $this->render('user/show', [...]);
}
```

</v-click>

<v-click>

<div class="mt-6 text-lg">
Подожди. Это же обычный <b>логгер</b>. Или <b>дампер</b>.<br/>
Пользователь и так уже умеет <code>dump($x)</code> — и уже им пользуется.
</div>

</v-click>

<!--
Speaker notes:
Сначала показываем гипотетический API (три разных типа —
объект, массив, SQL). Потом — пауза — и вывод: "это же dump".
Это переход к идее перехвата существующего handler'а на 09b.
-->
