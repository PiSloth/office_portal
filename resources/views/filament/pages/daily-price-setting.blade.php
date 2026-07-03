<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4 mb-8">
            <x-filament::button type="submit" color="danger">
                Update Prices
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
