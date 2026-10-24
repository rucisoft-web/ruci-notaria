<?php

namespace App\Filament\Resources\SerahTerimaProtokol\Pages;

use App\Filament\Resources\SerahTerimaProtokol\SerahTerimaProtokolResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSerahTerimaProtokol extends ManageRecords
{
    protected static string $resource = SerahTerimaProtokolResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
