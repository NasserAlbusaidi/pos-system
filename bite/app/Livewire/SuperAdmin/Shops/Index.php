<?php

namespace App\Livewire\SuperAdmin\Shops;

use App\Models\Shop;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    #[Layout('layouts.super-admin')]
    public function render()
    {
        $shops = Shop::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('slug', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.super-admin.shops.index', [
            'shops' => $shops,
        ]);
    }
}
