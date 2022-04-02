<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // get products id and quantity from request
        $requestedProducts = $request->products;

        // array to store products
        $lastArray = [];

        // array to store warehouses for checking materials availability
        $warehousesArray = [];

        foreach ($requestedProducts as $product) {

            //getting product from database
            $requestedProduct = Product::with('product_materials')
                ->find($product['id']);

            //checking if product exists
            if (!$requestedProduct){
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            //initializing product details
            $productDetails = [
                'product_name' => $requestedProduct->name,
                'product_qty' => $product['quantity'],
            ];

            //array to store materials for each product
            $materials = [];
            foreach ($requestedProduct->product_materials as $key => $material) {

                //quantity of material needed for product
                $quantity =  $product['quantity'] * $material['quantity'];

                //getting warehouse for material from database
                $warehouses = Warehouse::with(array(
                    'material' => function ($query) {
                        $query->select('id', 'name');
                    },
                ))->where('material_id', $material['material_id'])
                    ->get();

                //inserting warehouses to array
                foreach ($warehouses as $warehouse){
                    if(!isset($warehousesArray[$warehouse->id])){
                        $warehousesArray[$warehouse->id] = $warehouse->remainder;
                    }
                }

                $innerWarehouse = [];


                foreach ($warehouses as $warehouse){

                    //checking if warehouse has enough materials
                    if ($warehousesArray[$warehouse->id] == 0){
                        continue;
                    }

                    //amount of material in warehouse after getting from it
                    $amount = $warehousesArray[$warehouse->id] - $quantity;

                    //if amount is negative, then we need to get more materials from another warehouse
                    if ($amount < 0){

                        //equaling quantity to abs value of amount to get the amount of materials needed from another warehouse
                        $quantity = abs($amount);

                        //inserting warehouse to array
                        $innerWarehouse[] = [
                            'warehouse_id' => $warehouse->id,
                            'material_name' => $warehouse->material->name,
                            'qty' => $warehousesArray[$warehouse->id],
                            'price' => $warehouse->price,
                        ];

                        //setting warehouse remainder to 0 to prevent getting more materials from this warehouse
                        $warehousesArray[$warehouse->id] = 0;
                    }else{
                        //if amount is positive, then we have enough materials in warehouse to get from it
                        $innerWarehouse[] = [
                            'warehouse_id' => $warehouse->id,
                            'material_name' => $warehouse->material->name,
                            'qty' => round($quantity),
                            'price' => $warehouse->price,
                        ];
                        //setting warehouse reminder to amount after getting enough materials
                        $warehousesArray[$warehouse->id] = $amount;
                        break;
                    }


                    $n = 0;
                    //checking if warehouse remainder is 0
                    foreach($warehouses as $warehouse){
                        $n += $warehousesArray[$warehouse->id];
                    }
                    if($n == 0){
                        //if warehouse remainder is 0, then we have to give warehouse id and price null to prevent error
                        $innerWarehouse[] = [
                            'warehouse_id' => null,
                            'material_name' => $warehouse->material->name,
                            'qty' => $quantity,
                            'price' => null,
                        ];
                    }
                }
                //merging innerWarehouse array to materials array to get warehouses for each material
                $materials = array_merge($materials, $innerWarehouse);
            }
            //inserting materials to products array
            $productDetails['materials'] = $materials;

            //inserting product details to store requested products
            $lastArray[] = $productDetails;
        }


        //returning array of products
        return response()->json([
            'success' => true,
            'data' => $lastArray
        ]);
    }
}
