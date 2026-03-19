export enum CollectorsMap {
    // Core collectors (AppDevPanel\Kernel)
    LogCollector = 'AppDevPanel\\Kernel\\Collector\\LogCollector',
    EventCollector = 'AppDevPanel\\Kernel\\Collector\\EventCollector',
    ExceptionCollector = 'AppDevPanel\\Kernel\\Collector\\ExceptionCollector',
    ServiceCollector = 'AppDevPanel\\Kernel\\Collector\\ServiceCollector',
    TimelineCollector = 'AppDevPanel\\Kernel\\Collector\\TimelineCollector',
    HttpClientCollector = 'AppDevPanel\\Kernel\\Collector\\HttpClientCollector',
    FilesystemStreamCollector = 'AppDevPanel\\Kernel\\Collector\\Stream\\FilesystemStreamCollector',
    HttpStreamCollector = 'AppDevPanel\\Kernel\\Collector\\Stream\\HttpStreamCollector',
    ConsoleAppInfoCollector = 'AppDevPanel\\Kernel\\Collector\\Console\\ConsoleAppInfoCollector',
    WebAppInfoCollector = 'AppDevPanel\\Kernel\\Collector\\Web\\WebAppInfoCollector',
    CommandCollector = 'AppDevPanel\\Kernel\\Collector\\Console\\CommandCollector',
    RequestCollector = 'AppDevPanel\\Kernel\\Collector\\Web\\RequestCollector',
    VarDumperCollector = 'AppDevPanel\\Kernel\\Collector\\VarDumperCollector',
    EnvironmentCollector = 'AppDevPanel\\Kernel\\Collector\\EnvironmentCollector',

    // Yiisoft adapter collectors
    MiddlewareCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Middleware\\MiddlewareCollector',
    DatabaseCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Db\\DatabaseCollector',
    MailerCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Mailer\\MailerCollector',
    QueueCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Queue\\QueueCollector',
    ValidatorCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Validator\\ValidatorCollector',
    WebViewCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\View\\WebViewCollector',
    RouterCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Router\\RouterCollector',

    // Symfony adapter collectors
    CacheCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\CacheCollector',
    DoctrineCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\DoctrineCollector',
    TwigCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\TwigCollector',
    SecurityCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\SecurityCollector',
    MessengerCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\MessengerCollector',
    SymfonyMailerCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\MailerCollector',

    // Core asset bundle collector
    AssetBundleCollector = 'AppDevPanel\\Kernel\\Collector\\AssetBundleCollector',

    // Yii 2 adapter collectors
    Yii2DbCollector = 'AppDevPanel\\Adapter\\Yii2\\Collector\\DbCollector',
    Yii2MailerCollector = 'AppDevPanel\\Adapter\\Yii2\\Collector\\MailerCollector',
}
