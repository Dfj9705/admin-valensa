<x-filament::page>
    {{ $this->form }}

    <div class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-3 xl:grid-cols-3">
        @foreach($this->cards() as $c)
            <x-filament::section>
                <div class="text-sm text-gray-500">{{ $c['label'] }}</div>
                <div class="text-2xl font-semibold mt-1">{{ $c['value'] }}</div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section class="mt-6">
        <div class="text-lg font-semibold mb-3">Saldos Venta de Maquinaria</div>

        <div class="overflow-x-auto">
            <table class="min-w-full w-full text-sm border">
                <thead class="text-left">
                    <tr class="border-b border-gray-200">
                        <th class="py-2 pr-4">Fecha</th>
                        <th class="py-2 pr-4">Tipo</th>
                        <th class="py-2 pr-4">Origen</th>
                        <th class="py-2 pr-4">Detalle</th>
                        <th class="py-2 pr-4 text-center">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->movimientosCaja() as $m)
                        <tr class="border-b">
                            <td class="py-2 pr-4">{{ $m['fecha_fmt'] }}</td>
                            <td class="py-2 pr-4 font-semibold">{{ $m['tipo'] }}</td>
                            <td class="py-2 pr-4">{{ $m['origen'] }}</td>
                            <td class="py-2 pr-4">{{ $m['detalle'] }}</td>
                            <td class="py-2 pr-4 text-right text-green-600 dark:text-green-400 ">
                                <x-filament::badge :color="$m['tipo'] === 'Ingreso' ? 'success' : 'danger'">
                                    {{ $m['monto_fmt'] }}
                                </x-filament::badge>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="border-b">

                        <td colspan="4" class="py-2 pr-4 text-center font-semibold text-lg">TOTAL</td>
                        <td class="py-2 pr-4 text-right font-semibold">
                            <x-filament::badge :color="$this->getCajaGlobal() >= 0 ? 'success' : 'danger'">
                                Q {{ number_format($this->getCajaGlobal(), 2) }}
                            </x-filament::badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section class="mt-8">
        <div class="text-lg font-semibold mb-3">Saldos de Servicios</div>

        <div class="overflow-x-auto">
            <table class="min-w-full w-full text-sm">
                <thead class="text-left">
                    <tr class="border-b">
                        <th class="py-2 pr-4">Fecha</th>
                        <th class="py-2 pr-4">Tipo</th>
                        <th class="py-2 pr-4">Detalle</th>
                        <th class="py-2 pr-4 text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->movimientosServicios() as $m)
                                    <tr class="border-b">
                                        <td class="py-2 pr-4">{{ $m['fecha_fmt'] }}</td>

                                        <td class="py-2 pr-4">
                                            <span
                                                class="font-semibold
                                                                                                                                                                                                                                                                                                                {{ $m['tipo'] === 'Ingreso'
                        ? 'text-green-600 dark:text-green-400'
                        : 'text-red-600 dark:text-red-400'
                                                                                                                                                                                                                                                                                                                }}">
                                                {{ $m['tipo'] }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-4">{{ $m['detalle'] }}</td>
                                        <td>
                                            <x-filament::badge :color="$m['tipo'] === 'Ingreso' ? 'success' : 'danger'">
                                                {{ $m['monto_fmt'] }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                    @endforeach

                    <tr class="border-b">

                        <td colspan="3" class="py-2 pr-4 text-center font-semibold text-lg">TOTAL</td>
                        <td class="py-2 pr-4 text-right font-semibold">
                            <x-filament::badge :color="$this->getSaldoServicios() >= 0 ? 'success' : 'danger'">
                                Q {{ number_format($this->getSaldoServicios(), 2) }}
                            </x-filament::badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament::page>