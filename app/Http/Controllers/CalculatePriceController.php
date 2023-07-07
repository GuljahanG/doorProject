<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Order;
use App\Models\OrderAttribute;
use App\Models\Coefficient;
use Barryvdh\DomPDF\Facade\Pdf;
use GuzzleHttp\Client;
use Str;
use Storage;
use App\Notifications\SendNotification;

class CalculatePriceController extends Controller
{
    public function calculate(Request $request){

        $total = 0;
        $attributes = array();

        foreach(Attribute::get() as $attribute){
            if($request->has('attribute_'.$attribute->id)){

                if(is_array($request['attribute_'.$attribute->id])){
                    $attr_array = $request['attribute_'.$attribute->id];

                    foreach($attr_array as $attr){
                        $attribute_value = AttributeValue::find($attr);
                        $total+=$attribute_value->price;

                        $data['attribute_id'] = $attribute->id;
                        $data['attribute_value_id'] = $attribute_value->id;
                        array_push($attributes, $data);
                    }
                }else{
                    $attribute_value = AttributeValue::find($request['attribute_'.$attribute->id]);
                    $total+=$attribute_value->price;

                    $data['attribute_id'] = $attribute->id;
                    $data['attribute_value_id'] = $attribute_value->id;
                    array_push($attributes, $data);
                }
            }
        }

        $coefficient = Coefficient::orderBy('created_at', 'desc')->first()['coefficient'];

        $order = new Order;
        $order->total = $total*$coefficient;
        $order->pdf_link = 'link';
        $order->code = date('Ymd-His').rand(10,99);
        $order->save();

        foreach($attributes as $attribute){
            $order_detail = new OrderAttribute;
            $order_detail->order_id = $order->id;
            $order_detail->attribute_id = $attribute['attribute_id'];
            $order_detail->attribute_value_id = $attribute['attribute_value_id'];
            $order_detail->save();
        }

        $pdf = $this->pdf($order);
        $order->pdf_link = $pdf;
        $order->save();

        $bot = new \App\Services\TelegramBot;
        $bot->send($order->pdf_link);

        return redirect()->back()->with(['message'=> 'Success']);

    }

    public function pdf($order){

        $pdf = Pdf::loadView('pdf.invoice', compact('order'));
        $output = $pdf->output();//generate pdf check

        $file_name = 'uploads/orders/'.time() . Str::random(5).'.pdf';
        Storage::disk('public')->put($file_name, $output);
        return $file_name;
    }
}
