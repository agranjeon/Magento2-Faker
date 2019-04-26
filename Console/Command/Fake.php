<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Console\Command;

use Agranjeon\Faker\Model\FakerProvider;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class Fake extends Command
{
    const CODE_ARGUMENT = 'code';
    /**
     * @var FakerProvider $fakerProvider
     */
    protected $fakerProvider;
    /**
     * @var State $appState
     */
    protected $appState;

    /**
     * Fake constructor
     *
     * @param FakerProvider $fakerProvider
     * @param State         $appState
     * @param string|null   $name
     */
    public function __construct(
        FakerProvider $fakerProvider,
        State $appState,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->fakerProvider = $fakerProvider;
        $this->appState      = $appState;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('agranjeon:fake:data')->setDescription('Create lot of fake customer and order')->setDefinition(
            [
                new InputArgument(
                    self::CODE_ARGUMENT,
                    InputArgument::REQUIRED,
                    'Code of the fake data to generate (All to generate all fake data'
                ),
            ]
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Exception $exception) {
        }

        $io   = new SymfonyStyle($input, $output);
        $code = $input->getArgument(self::CODE_ARGUMENT);

        if ($code !== 'all') {
            $faker = $this->fakerProvider->getFaker($code);
            $faker->generateFakeData();

            $io->success('Fake data has been successfully generated for ' . $code);

            return;
        }

        $fakers      = $this->fakerProvider->getFakers();
        $progressBar = new ProgressBar($output, count($fakers));
        $progressBar->setFormat(
            '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
        );
        $progressBar->start();
        $progressBar->display();
        foreach ($fakers as $code => $faker) {
            $progressBar->setMessage($code . '...');
            $progressBar->display();

            $faker->generateFakeData();

            $progressBar->advance();
        }
        $progressBar->finish();
        $io->success('Fake data has been successfully generated');
    }
}
