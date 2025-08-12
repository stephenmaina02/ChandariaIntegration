<div>
    <div class="w-full">
        Total Customers {{ count($customers) }}
        <table class="table-auto w-full">
            <thead>
                <tr class="bg-teal-500 text-white">
                    <th class="border px-2 py-4">Code</th>
                    <th class="border px-2 py-4">Name</th>
                    {{-- <th class="border px-2 py-4">Phone</th> --}}
                    {{-- <th class="border px-2 py-4">Email</th> --}}
                    <th class="border px-2 py-4">Region</th>
                    <th class="border px-2 py-4">Category</th>
                    <th class="border px-2 py-4">Credit Limit</th>
                    <th class="border px-2 py-4">PriceList Code</th>
                    <th class="border px-2 py-4">Sync Status</th>
                </tr>
            </thead>
            <tbody>
                {{-- <tr>
                    <td class="border h-12"><input wire:model="queryCode" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryName" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryRegion" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryCat" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryCredit" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryPricel" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12">
                    <select wire:model="status" class="w-full border-2 m-auto h-full">
                        <option value=""></option>
                        <option value="0">Pending</option>
                        <option value="1">Posted</option>
                    </select>
                </td>
                </tr> --}}
                @if(count($customers)>0)
                @foreach ($customers as $customer)
                <tr>
                    <td class="border px-2 py-4">{{ $customer->customer_code }}</td>
                    <td class="border px-2 py-4">{{ $customer->name }}</td>
                    {{-- <td class="border px-2 py-4">{{ $customer->phone_number }}</td> --}}
                    {{-- <td class="border px-2 py-4">{{ $customer->email }}</td> --}}
                    <td class="border px-2 py-4">{{ $customer->region }}</td>
                    <td class="border px-2 py-4">{{ $customer->category }}</td>
                    <td class="border px-2 py-4">{{ $customer->credit_limit }}</td>
                    <td class="border px-2 py-4">{{ $customer->pricelist_code }}</td>
                    <td class="border px-2 py-4">
                        @if($customer->status==0)
                           <span class="text-red-500"> Pending</span>
                        @else
                           <span class="text-green-500"> Posted</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td colspan="2">No Customers to show!!!</td>
                </tr>
                @endif
            </tbody>
        </table>
        {{ $customers->links() }}
    </div>
</div>
