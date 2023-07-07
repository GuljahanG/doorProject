<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public function index(){
        $orders = Order::orderBy('created_at', 'desc')->get();
        return view('admin.orders.index',[
            'orders' => $orders
        ]);
    }
    public function show(Order $order){

        return view('admin.orders.show',[
            'order' => $order
        ]);
    }
}
