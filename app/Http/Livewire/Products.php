<?php

namespace App\Http\Livewire;

use App\Models\Product;
use Livewire\Component;

class Products extends Component
{
    public $products=[];
    public $queryCode, $queryName, $queryCat, $queryDesc, $queryTaxcode, $product_status, $status;

    public function mount(){
       $this->products=Product::orderBy('created_at', 'desc')->get();
    }
    public function updated()
    {
        if($this->queryCode=="" && $this->queryName=="" && $this->queryCat=="" && $this->queryDesc==""
            && $this->queryTaxcode=="" && $this->product_status=="" && $this->status=="")
        $this->products=Product::orderBy('created_at', 'desc')->get();
        else
        {
        $this->products=Product::where('product_code', 'LIKE','%'.$this->queryCode.'%')
                                ->where('product_name','LIKE', '%'.$this->queryName.'%')
                                ->where('category','LIKE', '%'.$this->queryCat.'%')
                                ->where('description','LIKE', '%'.$this->queryDesc.'%')
                                ->where('tax_code','LIKE', '%'.$this->queryTaxcode.'%')
                                ->where('product_status','LIKE', '%'.$this->product_status.'%')
                                ->where('status','LIKE', '%'.$this->status.'%')
                                ->orderBy('created_at', 'desc')
                                ->get();
        }
    }
    public function render()
    {
        return view('livewire.products');
    }
}
