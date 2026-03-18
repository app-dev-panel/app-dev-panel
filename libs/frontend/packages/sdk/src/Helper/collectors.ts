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

    // Adapter collectors
    MiddlewareCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Web\\MiddlewareCollector',

    // Symfony adapter collectors
    CacheCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\CacheCollector',
    DoctrineCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\DoctrineCollector',
    TwigCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\TwigCollector',
    SecurityCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\SecurityCollector',
    MessengerCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\MessengerCollector',

    // Yii 2 adapter collectors
    Yii2DbCollector = 'AppDevPanel\\Adapter\\Yii2\\Collector\\DbCollector',
    Yii2MailerCollector = 'AppDevPanel\\Adapter\\Yii2\\Collector\\MailerCollector',
    Yii2AssetBundleCollector = 'AppDevPanel\\Adapter\\Yii2\\Collector\\AssetBundleCollector',

    // External package collectors (not yet migrated, kept for future adapters)
    AssetCollector = 'Yiisoft\\Assets\\Debug\\AssetCollector',
    ValidatorCollector = 'Yiisoft\\Validator\\Debug\\ValidatorCollector',
    DatabaseCollector = 'Yiisoft\\Db\\Debug\\DatabaseCollector',
    QueueCollector = 'Yiisoft\\Queue\\Debug\\QueueCollector',
    MailerCollector = 'Yiisoft\\Mailer\\Debug\\MailerCollector',
    WebViewCollector = 'Yiisoft\\Yii\\View\\Renderer\\Debug\\WebViewCollector',
}
