<div>
    <div class="max-w-sm w-full lg:max-w-full lg:flex">
          <div class="p-3 w-full">
            <div class="font-bold text-xl mb-2 bg-teal-500 text-white"><span class="m-3">Copy this access token for API Authentication</span></div>
            <div class="">
                <textarea class="text-gray-700 text-base w-full border-2" rows="10">{{ $accessToken }}</textarea>
            </div>
          </div>
    </div>
    <div class="font-bold text-xl ml-3 mr-3 bg-teal-500 text-white max-w-sm w-full lg:max-w-full lg:flex"><span class="m-3">Order Details</span></div>
    <div class="max-w-sm w-full lg:max-w-full lg:flex">
            <div class="w-1/2 m-3 border-2">
            <div class="justify-between flex flex-wrap p-3">
                <h3 class="text-xl font-semibold text-gray-500">New Orders</h3>
                <h3 class="text-xl font-bold text-gray-700">{{ $newOrders }}</h3>
            </div>
            </div>
            <div class="w-1/2 m-3 border-2">
                <div class="justify-between flex flex-wrap p-3">
                    <h3 class="text-xl font-semibold text-green-500">Inserted Orders</h3>
                    <h3 class="text-xl font-bold text-green-700">{{ $insertedOrders }}</h3>
                </div>
            </div>
      </div>

      <div class="font-bold text-xl ml-3 mr-3 bg-teal-500 text-white max-w-sm w-full lg:max-w-full lg:flex"><span class="m-3">Customers Details</span></div>
      <div class="max-w-sm w-full lg:max-w-full lg:flex">
        <div class="w-full m-3 border-2">
            <div class="justify-between flex flex-wrap p-3">
                <h3 class="text-xl font-semibold text-green-500">Total Customers from Sage</h3>
                <h3 class="text-xl font-bold text-green-700">{{ $customers }}</h3>
            </div>
        </div>
      </div>

      <div class="font-bold text-xl ml-3 mr-3 bg-teal-500 text-white max-w-sm w-full lg:max-w-full lg:flex"><span class="m-3">Product Details</span></div>
      <div class="max-w-sm w-full lg:max-w-full lg:flex">
        <div class="w-full m-3 border-2">
            <div class="justify-between flex flex-wrap p-3">
                <h3 class="text-xl font-semibold text-green-500">Total Products from Sage</h3>
                <h3 class="text-xl font-bold text-green-700">{{ $products }}</h3>
            </div>
        </div>
      </div>

      <div class="font-bold text-xl ml-3 mr-3 bg-teal-500 text-white max-w-sm w-full lg:max-w-full lg:flex"><span class="m-3">Order Tracking Settings</span></div>
      <div class="max-w-sm w-full lg:max-w-full lg:flex">
        <div class="w-full m-3 border-2 p-4">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Allowed Order Warehouses</h3>
            <p class="text-sm text-gray-500 mb-3">Non-SATCHA orders are only synced from these warehouses.</p>

            @if($warehouseSaved)
                <div class="mb-3 px-3 py-2 bg-green-100 text-green-700 text-sm rounded">Warehouses saved successfully.</div>
            @endif

            <div class="flex flex-wrap gap-2 mb-4">
                @forelse($allowedWarehouses as $index => $warehouse)
                    <span class="inline-flex items-center bg-teal-100 text-teal-800 text-sm font-medium px-3 py-1 rounded-full">
                        {{ $warehouse }}
                        <button wire:click="removeWarehouse({{ $index }})" class="ml-2 text-teal-500 hover:text-red-600 focus:outline-none" title="Remove">&times;</button>
                    </span>
                @empty
                    <span class="text-gray-400 text-sm">No warehouses configured. All non-SATCHA orders will pass through.</span>
                @endforelse
            </div>

            <div class="flex gap-2">
                <input
                    type="text"
                    wire:model="newWarehouse"
                    wire:keydown.enter="addWarehouse"
                    placeholder="Warehouse code (e.g. UNT02)"
                    class="border border-gray-300 rounded px-3 py-1 text-sm w-48 focus:outline-none focus:border-teal-500"
                />
                <button
                    wire:click="addWarehouse"
                    class="bg-teal-500 hover:bg-teal-600 text-white text-sm px-4 py-1 rounded"
                >
                    Add
                </button>
            </div>
        </div>
      </div>
</div>
