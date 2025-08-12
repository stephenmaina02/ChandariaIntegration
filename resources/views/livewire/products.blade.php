<div>
    <div class="w-full">
        Total Customers {{ count($products) }}
        <table class="table-auto w-full">
            <thead>
                <tr class="bg-teal-500 text-white">
                    <th class="border px-2 py-4">Code</th>
                    <th class="border px-2 py-4">Name</th>
                    {{-- <th class="border px-2 py-4">Phone</th> --}}
                    {{-- <th class="border px-2 py-4">Email</th> --}}
                    <th class="border px-2 py-4">Category</th>
                    <th class="border px-2 py-4">Description</th>
                    <th class="border px-2 py-4">Tax Code</th>
                    <th class="border px-2 py-4">Product Status</th>
                    <th class="border px-2 py-4">Sync Status</th>
                </tr>
            </thead>
            <tbody>
                {{-- <tr>
                    <td class="border h-12"><input wire:model="queryCode" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryName" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryCat" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryDesc" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12"><input wire:model="queryTaxcode" type="text" class="w-full border-2 m-auto h-full"></td>
                    <td class="border h-12">
                        <select wire:model="product_status" class="w-full border-2 m-auto h-full">
                            <option value=""></option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </td>
                    <td class="border h-12">
                    <select wire:model="status" class="w-full border-2 m-auto h-full">
                        <option value=""></option>
                        <option value="0">Pending</option>
                        <option value="1">Posted</option>
                    </select>
                </td>
                </tr> --}}
                @if(count($products)>0)
                @foreach ($products as $product)
                <tr>
                    <td class="border px-2 py-4">{{ $product->product_code }}</td>
                    <td class="border px-2 py-4">{{ $product->product_name }}</td>
                    {{-- <td class="border px-2 py-4">{{ $product->phone_number }}</td> --}}
                    {{-- <td class="border px-2 py-4">{{ $product->email }}</td> --}}
                    <td class="border px-2 py-4">{{ $product->category }}</td>
                    <td class="border px-2 py-4">{{ $product->description }}</td>
                    <td class="border px-2 py-4">{{ $product->tax_code }}</td>
                    <td class="border px-2 py-4">{{ $product->product_status }}</td>
                    <td class="border px-2 py-4">
                        @if($product->status==0)
                           <span class="text-red-500"> Pending</span>
                        @else
                           <span class="text-green-500"> Posted</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td colspan="2">No products to show!!!</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
