<?php

namespace App\Notification\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Repository\EventRepositoryInterface;
use App\Notification\Handler\SendEventNotification;

class SendEventNotificationEmailsCommand extends Command
{
    protected static $defaultName = 'app:send-notifications';
    private EventRepositoryInterface $eventRepository;

    public function __construct(MessageBusInterface $bus, EventRepositoryInterface $eventRepository)
    {
        parent::__construct();
        $this->eventRepository = $eventRepository;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eventAttenders = $this->eventRepository->findUpcommingEventAttenders(30, 5);
        $eventNotification = new SendEventNotification($eventAttenders);
        $sentSuccesfully = $eventNotification->sendNotifications();
        return ($sentSuccesfully)? 0 : 1;
    }
}