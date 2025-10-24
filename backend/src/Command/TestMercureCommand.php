<?php

namespace App\Command;

use App\Service\MercurePublisher;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-mercure',
    description: 'Test de publication Mercure',
)]
class TestMercureCommand extends Command
{
    public function __construct(
        private readonly MercurePublisher $mercurePublisher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Envoi d\'un événement de test...');

        try {
            $this->mercurePublisher->publishChatMessage(1, [
                'messageId' => 999,
                'userId' => 1,
                'userName' => 'Test User',
                'content' => 'Message de test depuis la commande !',
                'type' => 'chat',
                'isIC' => false,
                'recipientId' => null,
                'recipientName' => null,
                'createdAt' => (new DateTimeImmutable())->format('c'),
            ]);

            $output->writeln('Événement publié avec succès !');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln('Erreur : ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
