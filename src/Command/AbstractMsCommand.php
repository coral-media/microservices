<?php

namespace MsShared\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractMsCommand extends Command
{
    protected array $msConfig;

    private const CONFIG_FILEPATH = __DIR__ . '/../../config/apps.yaml';

    public function __construct(?string $name = null)
    {
        $this->msConfig = $this->getMicroServicesConfig();

        parent::__construct($name);
    }

    private function getMicroServicesConfig(): array
    {
        return Yaml::parse(
            file_get_contents(realpath(self::CONFIG_FILEPATH))
        )['apps'];
    }

    protected function getActiveMicroServices(): array
    {
        return array_keys(
            array_filter(
                $this->msConfig,
                function ($config) {
                    return true === $config['enabled'];
                }
            )
        );
    }
}
