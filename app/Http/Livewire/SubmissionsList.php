<?php

namespace App\Http\Livewire;

use App\Models\Form;
use Livewire\Component;
use Livewire\WithPagination;

class SubmissionsList extends Component
{
    use WithPagination;

    public Form $form;
    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $submissions = $this->form->submissions()
            ->when($this->search, function ($q) {
                // Simple LIKE search over the JSON blob — fine at demo scale;
                // README notes this as the spot to add a search index at scale.
                $q->where('data', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(15);

        return view('livewire.submissions-list', compact('submissions'));
    }
}
