<div>
    <div class="w-full">
        Total Deliveries {{ count($deliveries) }}
        <table class="table-auto w-full">
            <thead>
                <tr class="bg-teal-500 text-white">
                    {{-- <th class="border px-2 py-4">Branch</th> --}}
                    <th class="border px-2 py-4">Delivery ID</th>
                    <th class="border px-2 py-4">Invoice Number</th>
                    <th class="border px-2 py-4">Vehicle Details</th>
                    <th class="border px-2 py-4">Delivery Status</th>
                    <th class="border px-2 py-4">Transaction ID</th>
                    <th class="border px-2 py-4">Delivery Date</th>
                    <th class="border px-2 py-4">Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>

                    <td class="border h-12"><input wire:model="queryDelId" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryInvNum" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryVehicle" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryDelStatus" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryDelDate" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td></td>
                    <td class="border h-12"><input wire:model="notes" type="text" class="w-full border-2 m-auto h-full"></td>
                </tr>
                @if(count($deliveries)>0)
                @foreach ($deliveries as $delivery)
                <tr>
                    {{-- <td class="border px-2 py-4">{{ $delivery->region }}</td> --}}
                    <td class="border px-2 py-4">{{ $delivery->delivery_id }}</td>
                    <td class="border px-2 py-4">{{ $delivery->invoice_number }}</td>
                    <td class="border px-2 py-4">{{ $delivery->vehicle_details }}</td>
                    <td class="border px-2 py-4">{{ $delivery->delivery_status }}</td>
                    <td class="border px-2 py-4">{{ $delivery->transaction_id }}</td>
                    <td class="border px-2 py-4">{{ $delivery->delivery_date }}</td>
                    <td class="border px-2 py-4">{{ $delivery->notes }}</td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td colspan="2">No deliveries to show!!!</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
