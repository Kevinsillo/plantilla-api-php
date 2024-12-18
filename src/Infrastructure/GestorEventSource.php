<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

class GestorEventSource
{
    private array $events = [];

    public function addEvent(string $event, array $data): void
    {
        $this->events[] = ['event' => $event, 'data' => $data];
    }

    private function getEvents(): array
    {
        return $this->events;
    }

    private function clearEvents(): void
    {
        $this->events = [];
    }

    public function sendEvents(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $events = $this->getEvents();
        foreach ($events as $event) {
            echo "event: {$event['event']}\n";
            echo 'data: ' . json_encode($event['data']) . "\n\n";
        }
        $this->clearEvents();
        flush();
    }
}
