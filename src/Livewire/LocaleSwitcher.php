<?php

namespace Flexpik\FilamentStudio\Livewire;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Services\LocaleResolver;
use Livewire\Component;

class LocaleSwitcher extends Component
{
    public int $collectionId;

    public string $activeLocale = '';

    /** @var array<string> */
    public array $locales = [];

    public bool $visible = false;

    public function mount(int $collectionId): void
    {
        $this->collectionId = $collectionId;
        $collection = StudioCollection::find($collectionId);

        if (! $collection) {
            return;
        }

        $resolver = app(LocaleResolver::class);

        if (! $resolver->isEnabled()) {
            return;
        }

        // Only show if collection has multilingual enabled and has translatable fields
        $hasTranslatableFields = $collection->fields()
            ->where('is_translatable', true)
            ->exists();

        if (empty($collection->supported_locales) || ! $hasTranslatableFields) {
            return;
        }

        $this->visible = true;
        $this->locales = $resolver->availableLocales($collection);
        $this->activeLocale = $resolver->resolve($collection);
    }

    public function switchLocale(string $locale): void
    {
        if (in_array($locale, $this->locales, true)) {
            $this->activeLocale = $locale;
            session(['studio_locale' => $locale]);

            $this->dispatch('locale-switched', locale: $locale);
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filament-studio::livewire.locale-switcher');
    }
}
