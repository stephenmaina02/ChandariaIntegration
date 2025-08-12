<div>
    <div class="w-full">
        Total Order Status: {{ count($orderstatus) }}
        <table class="table-auto w-full">
            <thead>
                <tr class="bg-teal-500 text-white">
                    <th class="border px-2 py-4">Transaction ID</th>
                    <th class="border px-2 py-4">Doc Num</th>
                    <th class="border px-2 py-4">Customer Code</th>
                    <th class="border px-2 py-4">Date</th>
                    <th class="border px-2 py-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border h-12"><input wire:model="queryTranID" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryDocNum" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryDate" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="status" type="text" class="w-full border-2 m-auto h-full"></td>
                </tr>
                @if(count($orderstatus)>0)
                @foreach ($orderstatus as $orderstate)
                <tr>
                    <td class="border px-2 py-4">{{ $orderstate->transaction_id }}</td>
                    <td class="border px-2 py-4">{{ $orderstate->doc_num }}</td>
                    <td class="border px-2 py-4">{{ $orderstate->customer_code }}</td>
                    <td class="border px-2 py-4">{{ $orderstate->date }}</td>
                    <td class="border px-2 py-4">{{ $orderstate->status }}</td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td colspan="5">No orders posted at the moment!!!</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
