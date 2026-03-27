<?php

namespace Flexpik\FilamentStudio\Enums;

enum PanelPlacement: string
{
    case Dashboard = 'dashboard';
    case CollectionHeader = 'collection_header';
    case CollectionFooter = 'collection_footer';
    case RecordHeader = 'record_header';
    case RecordFooter = 'record_footer';

    public function label(): string
    {
        return match ($this) {
            self::Dashboard => 'Dashboard',
            self::CollectionHeader => 'Collection Header',
            self::CollectionFooter => 'Collection Footer',
            self::RecordHeader => 'Record Header',
            self::RecordFooter => 'Record Footer',
        };
    }

    public function isDashboard(): bool
    {
        return $this === self::Dashboard;
    }

    public function isCollectionPlacement(): bool
    {
        return in_array($this, [self::CollectionHeader, self::CollectionFooter]);
    }

    public function isRecordPlacement(): bool
    {
        return in_array($this, [self::RecordHeader, self::RecordFooter]);
    }

    public function isHeader(): bool
    {
        return in_array($this, [self::CollectionHeader, self::RecordHeader]);
    }

    public function isFooter(): bool
    {
        return in_array($this, [self::CollectionFooter, self::RecordFooter]);
    }

    public function requiresCollectionContext(): bool
    {
        return ! $this->isDashboard();
    }

    public function requiresRecordContext(): bool
    {
        return $this->isRecordPlacement();
    }

    public function usesGrid(): bool
    {
        return $this->isDashboard();
    }
}
