<?php

namespace App\Filament\Resources\VentaResource\RelationManagers;

use App\Services\Sales\ManagePago;
use App\Support\Access;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';
    protected static ?string $title = 'Pagos';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Access::allows('ventas.ver') && Access::allows('pagos.ver');
    }

    protected function canCreate(): bool { return $this->canManage('crear'); }
    protected function canEdit(Model $record): bool { return $this->canManage('editar'); }
    protected function canDelete(Model $record): bool { return $this->canManage('eliminar'); }
    protected function canDeleteAny(): bool { return false; }

    private function canManage(string $action): bool
    {
        return Access::allows('ventas.ver') && Access::allows('pagos.ver') && Access::allows('pagos.'.$action)
            && in_array($this->getOwnerRecord()->ven_estado, ['draft', 'confirmed', 'certified'], true);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('vpa_monto')->label('Monto')->numeric()->minValue(0.01)->prefix('Q')->required(),
            Select::make('vpa_metodo')->label('Método')->options([
                'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia',
                'tarjeta' => 'Tarjeta', 'cheque' => 'Cheque', 'otro' => 'Otro',
            ])->searchable(),
            TextInput::make('vpa_referencia')->label('Referencia')->maxLength(100),
            DateTimePicker::make('vpa_fecha')->label('Fecha')->default(now())->seconds(false)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('vpa_id')->columns([
            TextColumn::make('vpa_fecha')->label('Fecha')->dateTime(),
            TextColumn::make('vpa_metodo')->label('Método')->badge(),
            TextColumn::make('vpa_referencia')->label('Referencia')->wrap(),
            TextColumn::make('vpa_monto')->label('Monto')->money('GTQ'),
        ])->headerActions([
            Tables\Actions\CreateAction::make()->label('Agregar Pago')
                ->using(fn (array $data) => app(ManagePago::class)->save($this->getOwnerRecord(), $data))
                ->after(fn () => $this->dispatch('refreshVentaTotals')),
        ])->actions([
            Tables\Actions\EditAction::make()
                ->using(fn (Model $record, array $data) => app(ManagePago::class)->save($this->getOwnerRecord(), $data, $record))
                ->after(fn () => $this->dispatch('refreshVentaTotals')),
            Tables\Actions\DeleteAction::make()
                ->using(function (Model $record): bool {
                    app(ManagePago::class)->delete($record);
                    return true;
                })
                ->recordTitle(fn ($record) => "Eliminar pago Q{$record->vpa_monto}")
                ->after(fn () => $this->dispatch('refreshVentaTotals')),
        ]);
    }
}
