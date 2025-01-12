<?php

namespace App\Http\Controllers;

use Auth, Config;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Transbank\Webpay\WebpayPlus\Transaction;
use App\Http\Models\Order, App\Http\Models\OrderItem, App\Http\Models\Product, App\Http\Models\Inventory, App\Http\Models\Variant, App\Http\Models\Coverage;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Mail;
use Transbank\Webpay\WebpayPlus;

class CartController extends Controller
{
    public function __Construct(){
    	$this->middleware('auth');

        if(app()->environment('production')){
            WebpayPlus::configureForProduction(
                env('WEBPAY_PLUS_CC'),
                env('WEBPAY_PLUS_KEY_SECRET')
            );
        }else{
            WebpayPlus::configureForTesting();
        }
    }

    public function getCart(){
    	$order = $this->getUserOrder();
    	$items = $order->getItems;
        $shipping = $this->getShippingValue($order->id);
        $order = Order::find($order->id);
    	$data = ['order' => $order, 'items' => $items, 'shipping' => $shipping];
    	return view('cart', $data);
    }

    public function getCartChangeType(Order $order, $type){
        if($order->user_id != Auth::id()):
            return redirect('/');
        endif;

        if($order->status == "0"):
            $order->o_type = $type;
            if($type == "1"):
                $order->user_address_id = null;
                $order->delivery = "0.00";
            endif;

            if($order->save()):
                return back();
            endif;
            
        else:
            return redirect('/');
        endif;
    }

    public function postCart(Request $request){
        $order = $this->getUserOrder();
        $order->session_id = Str::uuid();
        $order->payment_method = $request->input('payment_method');
        $order->user_comment = $request->input('order_msg');
        $order->save();

        $url = self::startTransaction($order);
        return redirect($url);
        
    }

    public function startTransaction($nueva_compra){
        $transaccion = (new Transaction)->create(
            $nueva_compra->id,
            $nueva_compra->session_id,
            number_format($nueva_compra->total, 0, '', ''),
            route('confirmarPago')
        );
        $url = $transaccion->getUrl().'?token_ws='.$transaccion->getToken();
        return $url;
    }

    public function confirmarPago(Request $request){
        try{
            if(!$request->get('token_ws'))
                return redirect('/');
        
            $confirmacion = (new Transaction)->commit($request->get('token_ws'));
            
            $compra = Order::where('id', $confirmacion->buyOrder)->first();

            $this->getProcessOrder($compra->id);

            if($confirmacion->isApproved()){
                $compra->status = 2;
                $compra->paid_at = date('Y-m-d H:i:s');
                $compra->save();
                $mensaje = "PAGO REALIZADO CORRECTAMENTE";

                if($compra->save()):
                    $orderItem = OrderItem::where('order_id', $compra->id)->get();
                    $oitem = $orderItem->toArray();
                    $user = User::where('id', Auth::id())->first();

                    Mail::send('emails.order_paid', ['name' => $user->name, 'order' => $compra->id], function($msj) use ($user){
                        $msj->from(env('MAIL_USERNAME'), "BNE COMPUTERS");
                        $msj->subject("Recepción de pago BneComputers");
                        $msj->to($user->email);
                    });
                    foreach($oitem as $oi){
                        $product = Product::find($oi['product_id']);
    
                        $product->quantity = ($product->quantity - $oi['quantity']);
                        $product->save();
                    }
                endif;

                return redirect('/account/history/order/'.$compra->id)->with('messageToastr', $mensaje);
            }else{
                $compra->status = 100;
                $compra->rejected_at = date('Y-m-d H:i:s');
                $compra->save();
                $mensaje = "PAGO RECHAZADO, POR FAVOR VERIFIQUE SU BANCO";
                return redirect('/')->with('errorToastr', $mensaje);
            }

        }catch(Exception $e){
            dd($e->getMessage());
            return redirect('/')->with('errorToastr', "El pago no ha sido procesado, realice nuevamente la compra.");
        }
    }

    public function getUserOrder(){
    	$order = Order::where('status', '0')->where('user_id', Auth::id())->count();
    	if($order == "0"):
    		$order = new Order;
    		$order->user_id = Auth::id();
    		$order->save();
    	else:
    		$order = Order::where('status', '0')->where('user_id', Auth::id())->first();
    	endif;
    	return $order;
    }

    public function getShippingValue($order_id){
        $order = Order::find($order_id);

        if($order->o_type == "0" || Config::get('cms.to_go') == "0"):
            $shipping_method = Config::get('cms.shipping_method');

            if($shipping_method == "0"):
                $price = '0';
            endif;

            if($shipping_method == "1"):
                $price = Config::get('cms.shipping_default_value');;
            endif;

            if($shipping_method == "2"):
                $user_address_count = Auth::user()->getAddress()->count();
                if($user_address_count == "0"):
                    $price = Config::get('cms.shipping_default_value');
                else:
                    $user_address = Auth::user()->getAddressDefault->city_id;
                    $coverage = Coverage::find($user_address);
                    $price = $coverage->price;
                endif;
            endif;

            if($shipping_method == "3"):
                if($order->getSubtotal() >= Config::get('cms.shipping_amount_min')):
                    $price = '0';
                else:
                    $price = Config::get('cms.shipping_default_value');
                endif;
            endif;
            if(!is_null(Auth::user()->getAddressDefault)):
            $order->user_address_id = Auth::user()->getAddressDefault->id;
            endif;
            $order->o_type = '0';
            $order->subtotal = $order->getSubtotal();
            $order->delivery = $price;
            $order->total = ($order->getSubtotal() + $price);
           
            if($order->save()):
                
            endif;
        else:
            $price = "0";
            $order->total = $order->getSubtotal();
            $order->save();
        endif;
        return $price;
    }

    public function postCartAdd(Request $request, $id){
        $inventory = Product::findOrFail($request->input('product'));
        if(is_null($request->input('product'))):
            return back()->with('message', 'Seleccione una opcion del producto.')->with('typealert', 'danger');
        else:
            if($inventory->quantity == "0"):
                 return back()->with('message', 'La opción seleccionada no esta disponible.')->with('typealert', 'danger');
            else:
                if($inventory->id != $id):
                    return back()->with('message', 'No podemos agregar este producto al carrito.')->with('typealert', 'danger');
                else:
                	$order = $this->getUserOrder();
                	$product = Product::find($id);
                	if($request->input('quantity') < 1):
                		return back()->with('message', 'Es necesario ingresar la cantidad que desea ordenar de este producto.')->with('typealert', 'danger');
                	else:

                        if($request->input('quantity') > $inventory->quantity):
                            return back()->with('message', 'No disponemos de esa cantidad en inventario de este producto.')->with('typealert', 'danger');
                        endif;

                        $query = OrderItem::where('order_id', $order->id)->where('product_id', $inventory->id)->count();
                        if($query == 0):
                    		$oitem = new OrderItem;
                            $price = $this->getCalculatePrice($inventory->in_discount, $inventory->discount, $inventory->price);
                    		$total = $price * $request->input('quantity');
                    		$label = $product->name.' / '.$inventory->name;
                    		$oitem->user_id = Auth::id();
                    		$oitem->order_id = $order->id;
                    		$oitem->product_id = $id;

                            $c = Product::findOrFail($id);
                            $oitem->category_id = $c->category_id;
                    		$oitem->label_item = $label;
                    		$oitem->quantity = $request->input('quantity');
                    		$oitem->discount_status = $inventory->in_discount;
                    		$oitem->discount = $inventory->discount;
                            $oitem->discount_until_date = $inventory->discount_until_date;
                    		$oitem->price_initial = $inventory->price;
                            $oitem->price_unit = $price;
                    		$oitem->total = $total;
                    		if($oitem->save()):
                                return back()->with('message', 'Producto agregado al carrito de compras.')->with('typealert', 'success');
                            endif;
                        else:
                            return back()->with('message', 'Este producto ya esta en su carrito de compra..')->with('typealert', 'danger');
                        endif;
                	endif;
                endif;
            endif;
        endif;
    }

    public function postCartItemQuantityUpdate($id, Request $request){
        $order = $this->getUserOrder();
        $oitem = OrderItem::find($id);
        $inventory = Product::find($oitem->product_id);
        //$product = Product::find($oitem->product_id);
        if($order->id != $oitem->order_id):
            return back()->with('message', 'No podemos actualizar la cantidad de este producto.')->with('typealert', 'danger');
        else:
            if($request->input('quantity') > $inventory->quantity):
                return back()->with('message', 'La cantidad ingresada supera al inventario.')->with('typealert', 'danger');
            endif;
            $total =  $oitem->price_unit * $request->input('quantity');
            $oitem->quantity = $request->input('quantity');
            $oitem->total = $total;
            if($oitem->save()):
                $this->getShippingValue($order->id);
                return back()->with('message', 'Cantidad actualizada con éxito.')->with('typealert', 'success');
            endif;
        endif;
    }

    public function getCartItemDelete($id){
        $oitem = OrderItem::find($id);
        if($oitem->delete()):
            return back()->with('message', 'Producto eliminado del carrito con éxito.')->with('typealert', 'success');
        endif;
    }

    public function getCalculatePrice($in_discount, $discount, $price){
        $final_price = $price;
        if($in_discount == "1"):
            $discount_value = '0.'.$discount;
            $discount_calc = $price * $discount_value;
            $final_price = $price - $discount_calc;
        endif;
        return $final_price;
    }


}
