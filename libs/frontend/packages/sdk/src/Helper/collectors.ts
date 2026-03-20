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
    DatabaseCollector = 'AppDevPanel\\Kernel\\Collector\\DatabaseCollector',
    MailerCollector = 'AppDevPanel\\Kernel\\Collector\\MailerCollector',
    AssetBundleCollector = 'AppDevPanel\\Kernel\\Collector\\AssetBundleCollector',

    // Yiisoft adapter collectors
    MiddlewareCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Middleware\\MiddlewareCollector',
    QueueCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Queue\\QueueCollector',
    ValidatorCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Validator\\ValidatorCollector',
    WebViewCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\View\\WebViewCollector',
    RouterCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Router\\RouterCollector',

    // Symfony adapter collectors
    CacheCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\CacheCollector',
    TwigCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\TwigCollector',
    SecurityCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\SecurityCollector',
    MessengerCollector = 'AppDevPanel\\Adapter\\Symfony\\Collector\\MessengerCollector',
}
