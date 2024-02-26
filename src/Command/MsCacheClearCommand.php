<?php

namespace MsShared\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ms:cache:clear',
    description: 'Clear cache for micro-services apps',
)]
class MsCacheClearCommand extends AbstractMsCommand
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $activeMicroservices = $this->getActiveMicroServices();

        $io->progressStart(count($activeMicroservices));
        foreach ($activeMicroservices as $activeMicroservice) {
            $io->comment(
                sprintf('Clearing the cache for %s microservice', $activeMicroservice)
            );
            $this->getApplication()->doRun(
                new ArrayInput([
                    'command' => 'cache:clear',
                    '--ms-id' => $activeMicroservice,
                ]),
                $output
            );
            $io->progressAdvance();
        }
        $io->progressFinish();

        return Command::SUCCESS;
    }
}
