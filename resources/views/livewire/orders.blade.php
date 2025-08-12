<div>
    <div class="w-full">
        <p>Total orders({{ count($orders) }})</p>
        <table class="table-auto w-full">
            <thead>
                <tr class="bg-teal-500 text-white">
                    <th class="border px-2 py-4">ID</th>
                    <th class="border px-2 py-4">Reference</th>
                    <th class="border px-2 py-4">Order Date</th>
                    <th class="border px-2 py-4">Customer Code</th>
                    <th class="border px-2 py-4">Status</th>
                    <th class="border px-2 py-4">Action</th>
                    {{-- <th class="border px-2 py-4">Action</th> --}}
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border h-12"><input wire:model="queryID" type="text"
                            class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryRef" type="text"
                            class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryDate" type="text"
                            class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryCust" type="text"
                            class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12">
                        <select wire:model="querySta" class="w-full border-2 m-auto h-full">
                            <option value=""></option>
                            <option value="0">New</option>
                            <option value="1">Inserted</option>
                        </select>
                    </td>
                    <td class="border h-12"></td>
                </tr>
                @if (count($orders) > 0)
                    @foreach ($orders as $order)
                        <tr>
                            <td class="border px-2 py-4">{{ $order->transaction_id }}</td>
                            <td class="border px-2 py-4">{{ $order->sage_ref }}</td>
                            <td class="border px-2 py-4">{{ date('Y-m-d', strtotime($order->transaction_date)) }}</td>
                            <td class="border px-2 py-4">{{ $order->customer_code }}</td>
                            <td class="border px-2 py-4">
                                @if ($order->status == 0)
                                    New
                                @else
                                    Inserted
                                @endif
                            </td>
                            <td class="border px-2 py-4">
                                @if ($order->status == 0)
                                    <!-- <button class="rounded-md shadow hover:bg-blue-500 px-2 bg-green-500 text-white"
                                        wire:click="insertOrder('{{ $order->transaction_id }}')"
                                        >Sync Sage</button> -->
                                    <button class="rounded-md shadow hover:bg-blue-500 px-2 bg-red-500 text-white"
                                        wire:click="deleteOrder('{{ $order->transaction_id }}')">Delete</button>
                                @endif
                            </td>
                            {{-- <td class="border px-2 py-4"><button class="rounded-md shadow hover:bg-blue-500 px-2 bg-green-500 text-white" wire:click="invoiceDetails({{ $order->transaction_id }})">Details</button></td> --}}
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2">No orders made at the moment!!!</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    @if ($invoiceDetailsModal)
        <x-jet-dialog-modal wire:model="invoiceDetailsModal" id="invoiceDetailsModal">
            <x-slot name="title">
                {{ __(' Invoice Details') }}
            </x-slot>
            <x-slot name="content">
                <table class="table-auto w-full">
                    <thead>
                        <tr class="bg-blue-500 text-white">
                            <th class="border px-2 py-4">Stock Code</th>
                            <th class="border px-2 py-4">Warehouse Code</th>
                            <th class="border px-2 py-4">UOM Code</th>
                            <th class="border px-2 py-4">Price</th>
                            <th class="border px-2 py-4">Quantity</th>
                            <th class="border px-2 py-4">Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoicedetails as $invoiceline)
                            <tr>
                                <td class="border px-2 py-4">{{ $invoiceline->item_code }}</td>
                                <td class="border px-2 py-4">{{ $invoiceline->warehouse_code }}</td>
                                <td class="border px-2 py-4">{{ $invoiceline->uom_code }}</td>
                                <td class="border px-2 py-4">{{ $invoiceline->item_price }}</td>
                                <td class="border px-2 py-4">{{ $invoiceline->item_quantity }}</td>
                                <td class="border px-2 py-4">{{ $invoiceline->transaction_id }}</td>
                            </tr>
                        @endforeach
                    </tbody>
            </x-slot>

            <x-slot name="footer">

            </x-slot>
        </x-jet-dialog-modal>
    @endif
</div>
