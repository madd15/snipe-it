<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Models\Supplier;
use App\Http\Transformers\SuppliersTransformer;

class SuppliersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view', Supplier::class);
        $allowed_columns = ['id','name','address','phone','contact','fax','email'];
        
        $suppliers = Supplier::select(
                array('id','name','address','address2','city','state','country','fax', 'phone','email','contact','created_at','updated_at','deleted_at')
            )->withCount('assets')->withCount('licenses')->whereNull('deleted_at');


        if ($request->has('search')) {
            $suppliers = $suppliers->TextSearch($request->input('search'));
        }

        $offset = request('offset', 0);
        $limit = $request->input('limit', 50);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'created_at';
        $suppliers->orderBy($sort, $order);

        $total = $suppliers->count();
        $suppliers = $suppliers->skip($offset)->take($limit)->get();
        return (new SuppliersTransformer)->transformSuppliers($suppliers, $total);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Supplier::class);
        $supplier = new Supplier;
        $supplier->fill($request->all());

        if ($supplier->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $supplier, trans('admin/suppliers/message.create.success')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, $supplier->getErrors()));

    }

    /**
     * Display the specified resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->authorize('view', Supplier::class);
        $supplier = Supplier::findOrFail($id);
        return (new SuppliersTransformer)->transformSupplier($supplier);
    }


    /**
     * Update the specified resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->authorize('edit', Supplier::class);
        $supplier = Supplier::findOrFail($id);
        $supplier->fill($request->all());

        if ($supplier->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $supplier, trans('admin/suppliers/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $supplier->getErrors()));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->authorize('delete', Supplier::class);
        $supplier = Supplier::with('asset_maintenances', 'assets', 'licenses')->withCount('asset_maintenances','assets', 'licenses')->findOrFail($id);
        $this->authorize('delete', $supplier);


        if ($supplier->assets_count > 0) {
            return response()->json(Helper::formatStandardApiResponse('error', null,  trans('admin/suppliers/message.delete.assoc_assets', ['asset_count' => (int) $supplier->assets_count])));
        }

        if ($supplier->asset_maintenances_count > 0) {
            return response()->json(Helper::formatStandardApiResponse('error', null,  trans('admin/suppliers/message.delete.assoc_maintenances', ['asset_maintenances_count' => $supplier->asset_maintenances_count])));
        }

        if ($supplier->licenses_count > 0) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/suppliers/message.delete.assoc_licenses', ['licenses_count' => (int) $supplier->licenses_count])));
        }

        $supplier->delete();
        return response()->json(Helper::formatStandardApiResponse('success', null,  trans('admin/suppliers/message.delete.success')));

    }
}
