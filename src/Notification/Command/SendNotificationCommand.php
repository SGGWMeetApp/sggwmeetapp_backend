<?php

namespace App\NotificationEmail\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SendNotificationEmailsCommand extends Command
{
    protected static $defaultName = 'app:send-notifications';
    private MessageBusInterface $bus;
    private PublicEventRepositoryInterface $eventRepository;

    public function __construct(MessageBusInterface $bus)
    {
        parent::__construct();
        $this->bus = $bus;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $upcommingEvents = 
        $this->bus->dispatch(new SendNotifications($users, $notifications));
        return 0;
    }
}