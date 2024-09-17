<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class RunCommand extends Component
{
    public $selected_uuid = 'default';

    public $servers = [];

    public $containers = [];

    public function mount($servers)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
        $this->servers = $servers;
        $this->containers = $this->getAllActiveContainers();
    }

    private function getAllActiveContainers()
    {
        return collect($this->servers)->flatMap(function ($server) {
            if (! $server->isFunctional()) {
                return [];
            }

            return $server->loadAllContainers()->map(function ($container) use ($server) {
                $state = data_get_str($container, 'State')->lower();
                if ($state->contains('running')) {
                    return [
                        'name' => data_get($container, 'Names'),
                        'connection_name' => data_get($container, 'Names'),
                        'uuid' => data_get($container, 'Names'),
                        'status' => data_get_str($container, 'State')->lower(),
                        'server' => $server,
                        'server_uuid' => $server->uuid,
                    ];
                }

                return null;
            })->filter();
        });
    }

    public function updatedSelectedUuid()
    {
        $this->connectToContainer();
    }

    #[On('connectToContainer')]
    public function connectToContainer()
    {
        if ($this->selected_uuid === 'default') {
            $this->dispatch('error', 'Please select a server or a container.');

            return;
        }
        $container = collect($this->containers)->firstWhere('uuid', $this->selected_uuid);
        $this->dispatch('send-terminal-command',
            isset($container),
            $container['connection_name'] ?? $this->selected_uuid,
            $container['server_uuid'] ?? $this->selected_uuid
        );
    }
}
