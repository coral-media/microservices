<?php

namespace MsShared\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'ms:app:toggle', description: 'Enable or disable a micro-service app')]
class MsAppToggleCommand extends AbstractMsCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('appName', InputArgument::REQUIRED, 'The name of the app or microservice')
            ->addOption('enable', null, InputOption::VALUE_NONE, 'Enable the app or microservice (default)')
            ->addOption('disable', null, InputOption::VALUE_NONE, 'Disable the app or microservice');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $appName = $input->getArgument('appName');
        $action = $input->getOption('enable') ? 'enable' : ($input->getOption('disable') ? 'disable' : null);

        if (!$action) {
            $io->error('Please specify either --enable or --disable option.');

            return 1;
        }

        try {
            $this->updateAppConfig($appName, $action);
            $this->updateComposerJson($appName, $action);
            $this->updatePHPUnitConfigFile($appName, $action);
            $io->success(sprintf('App "%s" successfully %s.', $appName, $action));

            return 0;
        } catch (\Exception $e) {
            $io->error(sprintf('An error occurred: %s', $e->getMessage()));

            return 1;
        }
    }

    private function updateAppConfig(string $appName, string $action): void
    {
        $appsConfigPath = __DIR__ . '/../../config/apps.yaml';
        $appsConfig = Yaml::parseFile($appsConfigPath);

        if (!isset($appsConfig['apps'][$appName])) {
            throw new \Exception(sprintf('App "%s" not found in configuration.', $appName));
        }

        $appsConfig['apps'][$appName]['enabled'] = 'enable' === $action;

        file_put_contents($appsConfigPath, Yaml::dump($appsConfig, 3, 4));
    }

    private function updateComposerJson(string $appName, string $action): void
    {
        // Load apps config
        $appsConfig = Yaml::parseFile(__DIR__ . '/../../config/apps.yaml'); // Adjust path if needed
        $appConfig = $appsConfig['apps'][$appName];

        // Determine Composer package name based on namespace
        $packageName = $appConfig['namespace'] . '\\' ?? 'App\\'; // Fallback if namespace is missing

        // Load composer.json
        $composerJsonPath = __DIR__ . '/../../composer.json';
        $composerJson = json_decode(file_get_contents($composerJsonPath), true);

        if ('disable' === $action) {
            if (!isset($composerJson['autoload']['psr-4'][$packageName])
                && !isset($composerJson['autoload-dev']['psr-4'][$packageName . 'Tests\\'])) {
                // Skip if app namespace doesn't exist in either autoload or autoload-dev section
                return;
            }
            unset($composerJson['autoload']['psr-4'][$packageName]);
            unset($composerJson['autoload-dev']['psr-4'][$packageName . 'Tests\\']);
        } else {
            // Update both autoload and autoload-dev sections
            $composerJson['autoload']['psr-4'][$packageName] = 'apps/' . $appName . '/src/';
            $composerJson['autoload-dev']['psr-4'][$packageName . 'Tests\\'] = 'apps/' . $appName . '/tests/';
        }

        file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function updatePHPUnitConfigFile(string $appName, string $action): void
    {
        $phpunitConfigFile = __DIR__ . '/../../phpunit.xml.dist'; // Adjust path if needed
        $xml = simplexml_load_file($phpunitConfigFile);

        $appTestSuitePath = sprintf('apps/%s/tests', $appName);

        // Add or remove test suite entry
        $testsuites = $xml->testsuites;
        $existingTestSuite = $testsuites->xpath("testsuite[@name='$appName']")[0] ?? null;

        if ('enable' === $action && !$existingTestSuite) {
            $newTestSuite = $testsuites->addChild('testsuite');
            $newTestSuite->addAttribute('name', $appName);
            $newTestSuite->addChild('directory', $appTestSuitePath);
        } elseif ('disable' === $action && $existingTestSuite) {
            unset($existingTestSuite[0]);
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        file_put_contents($phpunitConfigFile, $dom->saveXML());
    }
}
