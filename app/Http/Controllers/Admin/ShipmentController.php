<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * ShipmentController
 */
class ShipmentController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    $shipments = Shipment::join('orders', 'shipments.order_id', '=', 'orders.id')
      ->whereRaw('orders.deleted_at IS NULL')
      ->orderBy('shipments.created_at', 'DESC')->paginate(10);
    $this->data['shipments'] = $shipments;

    return view('admin.shipments.index', $this->data);
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param int $id shipment ID
   *
   * @return \Illuminate\Http\Response
   */
  public function edit($id)
  {
    $shipment = Shipment::findOrFail($id);
    $this->data['shipment'] = $shipment;
    $this->data['provinces'] = $this->getProvinces();
    $this->data['cities'] = isset($shipment->province_id) ? $this->getCities($shipment->province_id) : [];

    return view('admin.shipments.edit', $this->data);
  }

  /**
   * Update the specified resource in storage.
   *
   * @param Request $request request params
   * @param int     $id      shipment ID
   *
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, $id)
  {
    $request->validate(
      [
        'track_number' => 'required|max:255',
      ]
    );

    $shipment = Shipment::findOrFail($id);

    $order = DB::transaction(
      function () use ($shipment, $request) {
        $shipment->track_number = $request->input('track_number');
        $shipment->status = Shipment::SHIPPED;
        $shipment->shipped_at = now();
        $shipment->shipped_by = Auth::user()->id;

        if ($shipment->save()) {
          $shipment->order->status = Order::DELIVERED;
          $shipment->order->save();
        }

        return $shipment->order;
      }
    );

    Session::flash('success', 'The shipment has been updated');
    return redirect('admin/orders/' . $order->id);
  }
}
