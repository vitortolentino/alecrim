<?php

namespace Alecrim\Http\Controllers;
use Alecrim\Item;
use Alecrim\Order;
use Alecrim\User;
use Alecrim\Product;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;  
use Alecrim\Http\Requests\OrderRequest;
// use Session;
use Illuminate\Http\Request;


class OrderController extends Controller
{
  	
    public function store(OrderRequest $request) {

        $data           = $request->all();  // dados da requisição    
        $items          = $data['items'];   // item list
        $order          = new Order();      // nova instancia de Order
        $item           = new Item();       // nova instancia de Item

        // order
        $order->user_id         = $data['atendent'];        
        $order->order_table     = $data['table'];
        $order->order_paid      = $data['paid'];
        $order->order_total     = $data['order_total'];
        $order->order_status    = $data['order_status'];
        $order->save();

        // iterating items
        foreach ($items as $key => $value) {
            // id item
            $item->id = $value['item']['id'];
            // iterating products
            foreach($item->products as $key2 => $value2) {
                $products_by_item_quantity = $item->products[$key2]->pivot->product_quantity;
                $item->products[$key2]->product_quantity -= ($value['quantity'] * $products_by_item_quantity);
                $item->products[$key2]->save();
            }
            $item->orders()->attach($order, ['item_quantity' => $value['quantity']]);

        }
        return response()->json(['order' => ['order' => $order, 'items' => $order->items]]);

    }

    public function showPaidOrders() {
        $order_list = Order::where('order_status', 'pago')->paginate(10);
        return view('orders/list-orders')->with('order_list', $order_list);
    }

    public function showPendingOrders() {
        $order_list = Order::where('order_status', 'pendente')->paginate(10);
        return view('orders/list-orders')->with('order_list', $order_list);
    }

    /*public function editar($id)
    {
        $registro = Item::find($id);
        return view('items.editar', compact('registro'));
    }*/

    public function destroy($id) {
        $order = Order::find($id);        
        $order->items()->detach();
        $order->delete();
        return redirect()->route('orders.pending');
    } 

    public function searchItems() {


        $items = Item::all();
        foreach ($items as $key => $value) {
            $data[$key] = $value->products;  
        }
        // foreach ($data as $key => $value) {
        //     $resp = $value[0]->pivot->product_quantity;
        // }

        return response()->json($items);

    }

    public function opened($id) {
        $order = Order::find($id)->items;
        return response()->json($order);
    }

    public function details($id){
        $order = Order::find($id);
        $order_items = $order->items;
        $user = User::find($order->user_id);

        return view('orders/details', compact('order', 'order_items','user'));
    }
}