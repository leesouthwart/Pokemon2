<?php

namespace App\Http\Livewire;

use App\Jobs\CreateCard;
use App\Models\CardGroup;
use Livewire\Component;

class BulkImportCards extends Component
{
    public string $rows = '';
    public ?int $defaultGroupId = null;
    public array $errors = [];
    public int $dispatched = 0;
    public bool $submitted = false;

    public function render()
    {
        return view('livewire.bulk-import-cards', [
            'groups' => CardGroup::orderBy('name')->get(),
        ]);
    }

    public function submit()
    {
        $this->errors = [];
        $this->dispatched = 0;
        $this->submitted = false;

        $defaultGroupName = '';
        if ($this->defaultGroupId) {
            $group = CardGroup::find($this->defaultGroupId);
            if ($group) {
                $defaultGroupName = $group->name;
            }
        }

        $lines = preg_split('/\r\n|\r|\n/', $this->rows);
        $lineNumber = 0;

        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode(',', $line));

            // Need at least search_term and url
            if (count($parts) < 2) {
                $this->errors[] = "Line {$lineNumber}: expected at least \"search term, url\".";
                continue;
            }

            $searchTerm = $parts[0];
            $url = $parts[1];
            $groups = isset($parts[2]) && $parts[2] !== '' ? $parts[2] : $defaultGroupName;

            if ($searchTerm === '' || $url === '') {
                $this->errors[] = "Line {$lineNumber}: search term or URL is missing.";
                continue;
            }

            CreateCard::dispatch($searchTerm, $url, $groups);
            $this->dispatched++;
        }

        $this->submitted = true;

        if ($this->dispatched > 0) {
            $this->dispatch('success', $this->dispatched . ' card import jobs queued');
            $this->rows = '';
        }
    }
}
