<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Exception;

use Exception;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class PackageNotInstalledException extends Exception implements FriendlyExceptionInterface
{
    public function __construct(
        private string $packageName,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getName(): string
    {
        return sprintf(
            'Package "%s" is not installed.',
            $this->packageName,
        );
    }

    public function getSolution(): string
    {
        return <<<MARKDOWN
            Probably you forgot to install the package.

            Run `composer require {$this->packageName}` and configure the package in your application.
            Visit [yiisoft/yii-debug-api](https://github.com/{$this->packageName}) for more details.
            MARKDOWN;
    }
}
