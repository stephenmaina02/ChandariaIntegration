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
</div>
