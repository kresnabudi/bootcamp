<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $jsonString = file_get_contents(base_path('resources/data/data.json'));
        $data = json_decode($jsonString, true);
        
        return view('index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    function __construct()
    {
        $this->middleware('token', ['except' => [
            'postManualNotif', 'getShippingCostEstimation','getOrderReminder','getShipping'
        ]]);
    }

    public function getIndex()
    {
        $response = [
            'status' => 'success',
            'result' => null,
            'message' => 'something went wrong'
        ];

        $input = Input::all();
        $accessToken = Request::header('x-access-token');
        $cart_id = $input['cart_id'];
        $status_response = 400;
        $cart_details = CartDetails::customerCartDetailsWithGeneratedImage($cart_id, $input['user_id']);

        $paymentService = new PaymentService();
        if ($cart_details['status'] != 'failed') {
            $status_response = 200;
            $addresses = OrderAddress::getAllByUser($input['user_id']);
            $shipping_address = $billing_address = [];
            $default_billing_address = $default_shipping_address = null;
            foreach ($addresses as $address) {
                $address->id = encode($address->id);
                if ($address->type == 'Shipping') {
                    if ($address->set_default == 1) {
                        $default_shipping_address = $address->id;
                    }

                    $shipping_address[] = $address;
                }

                if ($address->type == 'Billing') {
                    if ($address->set_default == 1) {
                        $default_billing_address = $address->id;
                    }

                    $billing_address[] = $address;
                }
            }

            $address = [
                'shipping' => [
                    'defaultAddress' => $default_shipping_address,
                    'addresses' => $shipping_address
                ],
                'billing' => [
                    'defaultAddress' => $default_billing_address,
                    'addresses' => $billing_address
                ]
            ];

            $paymentServices = $paymentService->paymentMethods(null, 1, false, $input['user_id'], $accessToken);
            if ($paymentServices['status'] == 1) {
                $status_response = 200;
            }

            $item_ids = [];
            foreach ($cart_details['result'] as $microsites) {
                foreach ($microsites as $cart_detail) {
                    $item_ids[] = $cart_detail->id_item;
                }
            }

            $order_service = new OrderService;
            $on_daily_deals = $order_service->checkOnDailyDeals($item_ids);

            $result = [
                'cart_data' => $cart_details['result'],
                'addresses' => $address,
                'payment_methods' => $paymentServices['result']['methods'],
                'cart_id' => encode($input['cart_id']),
                'CCPercentage' => $paymentServices['result']['CCPercentage'],
                'BcaKlikPayPercentage' => $paymentServices['result']['BcaKlikPayPercentage'],
                'flagOvo' => $paymentServices['flagOvo'],
                'flagOtp' => $paymentServices['flagOtp'],
                'accessToken' => $accessToken,
                'on_daily_deals' => $on_daily_deals
            ];

            $response = [
                'status' => 'success',
                'message' => 'successfully retrieved data',
                'result' => $result
            ];
        }
        return response()->json($response, $status_response);
    }

    public function getShippingAddress(OrderService $orderService)
    {
        $input = Input::all();
        $status_response = 400;
        $address = $orderService->shippingAddress($input);
        if ($address['status']) {
            $status_response = 200;
        }
        return response()->json($address, $status_response);
    }

    public function postTransaction()
    {
        $params = Input::all();
        $data = new \stdClass;
        $data->user_id = userSession()['user']->id;
        $accessToken = Request::header('x-access-token');
        $flagService = new FlagService();
        $flagOtp = $flagService->checkFlag($data->user_id, $accessToken, 2);
        $params['flagOTP'] = $flagOtp;
        $params['phoneVerified'] = userSession()['user']->phone_verified;
        $insert_transaction = Orders::wholeSaleOrder($params);
        return json($insert_transaction, 200);
    }

    /**
     * @SWG\Post(
     * path="/api/v2/order/check-promo-code",
     * operationId="postCheckPromoCode",
     * tags={"/api/v2/order"},
     * summary="Check promo code",
     * description="Check if the promo code can be used",
     * @SWG\Parameter(
     *       name="Content-Type",
     *       in="header",
     *       required=true,
     *       type="string",
     *       default="application/json"
     * ),
     * @SWG\Parameter(
     *       name="x-access-token",
     *       in="header",
     *       required=true,
     *       type="string",
     *       default="33260481a150cd01e068494f6a969289102cae8b-bf9fa72f43a0118a0d5e0eebee294610_38e72f56cb6e2ba0575161d05a1df83d"
     * ),
     * @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              required={"cart_details", "promo_code", "user_id", "shipping_used"},
     *              @SWG\Property(
     *                  property="cart_details",
     *                  type="array",
     *                  @SWG\Items(
     *                      @SWG\Property(
     *                          property="id_item",
     *                          type="integer",
     *                          example="5478542"
     *                      ),
     *                      @SWG\Property(
     *                          property="product_quantity",
     *                          type="integer",
     *                          example="20"
     *                      ),
     *                      @SWG\Property(
     *                          property="price",
     *                          type="integer",
     *                          example="70000"
     *                      )
     *                  )
     *              ),
     *              @SWG\Property(
     *                  property="promo_code",
     *                  type="string",
     *                  example="LOYALIVASW"
     *              ),
     *              @SWG\Property(
     *                  property="user_id",
     *                  type="integer",
     *                  example="52555"
     *              ),
     *              @SWG\Property(
     *                  property="shipping_used",
     *                  type="array",
     *                  @SWG\Items(
     *                      @SWG\Property(
     *                          property="vendor_id",
     *                          type="integer",
     *                          example="5478542"
     *                      ),
     *                      @SWG\Property(
     *                          property="price",
     *                          type="integer",
     *                          example="1000"
     *                      ),
     *                      @SWG\Property(
     *                          property="shipping_id",
     *                          type="integer",
     *                          example="15"
     *                      )
     *                  )
     *              ),
     *          )
     *       ),
     *      @SWG\Response(response=200, description="Success"),
     *      @SWG\Response(response=400, description="Bad Request"),
     *      @SWG\Response(response=500, description="Internal Server Error"),
     *      @SWG\Response(response=403, description="Forbidden")
     * )
     */
    public function postCheckPromoCode()
    {
        $params = Input::all();
        $check_promo_code = Orders::simulationOfUsingPromoCode($params);
        return json($check_promo_code, 200);
    }

    /**
     * function for set loyalty points
     *
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function getLoyaltyPoints($input = [])
    {
        if (empty($input)) {
            $input = \Input::all();
        }
        $total_point_buyer = 0;
        $insertion = [];
        $data = $order = null;
        if (isset($input['order_detail_ids']) && $input['order_detail_ids']) {
            if (is_array($input['order_detail_ids'])) {
                foreach ($input['order_detail_ids'] as $order_detail_id) {
                    $where = ['order_details.id' => $order_detail_id];
                    $select = ['order_details.vendor_id', 'order_details.id', 'order_details.id_order',
                        \DB::raw('SUM(rl_order_details.product_quantity * rl_order_details.price) as total_price'),
                    ];

                    $group_by = 'order_details.vendor_id';

                    $detail = Orders::getDetail($select, $where, $group_by);

                    if ($detail) {
                        $total_point = intval(floor($detail->total_price / 1000));
                        $insertion[] = [
                            'loyalty_points' => $total_point,
                            'user_id' => $detail->vendor_id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'action' => 'save',
                            'description' => "FROM order with detail id $detail->id",
                            'order_detail_id' => $detail->id,
                        ];

                        $total_point_buyer += $total_point;
                    }
                }
            } else {
                $where = ['order_details.id' => $input['order_detail_ids']];
                $select = ['order_details.vendor_id', 'order_details.id', 'order_details.id_order',
                    \DB::raw('SUM(rl_order_details.product_quantity * rl_order_details.price) as total_price'),
                ];

                $group_by = 'order_details.vendor_id';

                $detail = Orders::getDetail($select, $where, $group_by);

                if ($detail) {
                    $total_point = intval(floor($detail->total_price / 1000));
                    $insertion[] = [
                        'loyalty_points' => $total_point,
                        'user_id' => $detail->vendor_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'action' => 'save',
                        'description' => "FROM order with detail id $detail->id",
                        'order_detail_id' => $detail->id,
                    ];

                    $total_point_buyer += $total_point;
                }
            }

            if ($detail) {
                # GET BUYER ID
                $select = ['user_id', 'id'];
                $where = ['id' => $detail->id_order];
                $order = Orders::findBy($select, $where);
            }
        }


        # update user balance
        if ($insertion) {
            Microsites::insertLoyaltyLogs($insertion);

            foreach ($insertion as $insert) {
                $where = ['vendor_id' => $insert['user_id']];
                $total_point = $insert['loyalty_points'];
                $updated = ['loyalty_points_balance' =>
                    \DB::raw("rl_microsites.loyalty_points_balance + $total_point")
                ];

                // EACH SELLER
                Microsites::updateLoyaltyBalance($updated, $where);
            }

            # BUYER
            if ($order) {
                foreach ($insertion as $key => $value) {
                    $insertion[$key]['user_id'] = $order->user_id;
                    $insertion[$key]['description'] = "FROM order with id $order->id";
                }

                User::insertLoyaltyLogs($insertion);

                $where = ['id' => $order->user_id];
                $updated = ['loyalty_points_balance' =>
                    \DB::raw("rl_users.loyalty_points_balance + $total_point_buyer")
                ];

                User::updateLoyaltyBalance($updated, $where);
            }

            $data = [
                'status' => 1,
                'result' => true,
                'message' => 'success'
            ];
        }

        return json($data, 200);
    }

    /**
     * @SWG\Get(
     *      path="/api/v2/order/shipping-price/{cart_id}/{shipping_address_id}",
     *      operationId="postShippingPrice",
     *      tags={"/api/v2/order"},
     *      summary="Shipping Price",
     *      description="Get shipping price",
     *      @SWG\Parameter(
     *          name="x-access-token",
     *          in="header",
     *          required=true,
     *          type="string"
     *       ),
     *      @SWG\Parameter(
     *          in="path",
     *          name="cart_id",
     *          required=true,
     *          type="string",
     *          default="",
     *          description="encoded cart id"
     *       ),
     *      @SWG\Parameter(
     *          in="path",
     *          name="shipping_address_id",
     *          required=true,
     *          type="string",
     *          default="",
     *          description="shipping address id"
     *       ),
     *      @SWG\Response(
     *          response=201,
     *          description="Successfully insert data"
     *       ),
     *       @SWG\Response(response=400, description="Bad request")
     *     )
     *
     * Returns success
     */
    public function getShippingPrice($cart_id, $address_id)
    {
        $response = ShippingService::getShippingPrices($cart_id, $address_id);
        $status_response = $response['status_response'];
        return response()->json($response, $status_response);
    }


    private static function loopCurl($key, $v, $shippings, $rand = 0, $cart = '', $vendors= null)
    {
        $seller_prices = null;
        if ($rand == 1) {
            $random_DKI_postal_code = DB::select('select postal_code from rl_seal_subdistricts where province_id = 6 order by rand() limit 1');
            $v['origin'] = $random_DKI_postal_code[0]->postal_code;
        }
        $shipping_price = curlSealTrack('api_tarif_list/', http_build_query($v));

        if (isset($shipping_price->status) && $shipping_price->status == 'OK') {
            if (isset($shipping_price->list) && $shipping_price->list) {
                foreach ($shipping_price->list as $value) {
                    $id = null;
                    if ($shippings) {
                        foreach ($shippings as $shipping) {
                            if ($shipping->name == $value->company
                                && $value->service == $shipping->service_code
                            ) {
                                $id = $shipping->id;
                            }
                        }
                    }

                    if ($id) {
                        if (!isset($seller_prices[$key][$id])) {
                            $seller_prices[$key][$id]['company'] = $value->company;
                            $seller_prices[$key][$id]['service'] = $value->service;
                            $seller_prices[$key][$id]['price'] = 0;
                            $seller_prices[$key][$id]['id'] = $id;
                            $seller_prices[$key][$id]['duration_longest'] = $value->duration_longest;
                            $seller_prices[$key][$id]['duration_shortest'] =
                                ($value->duration_shortest) ? $value->duration_shortest : 0;
                            $seller_prices[$key][$id]['vendor_id'] = (isset($vendors[$key]['vendorID'])) ? $vendors[$key]['vendorID'] : 0;
                        }

                        if ($rand == 0) {
                            $seller_prices[$key][$id]['price'] += intval($value->price);
                        } else {
                            $seller_prices[$key][$id]['price'] += intval($value->price * 1.3);
                        }
                    }
                }
            }
        } else {
            //track seal tarif fail /down
            $trackContainer = (isset($cart['container'])) ? $cart['container'] : 'empty';
            $trackId = (isset($cart['id'])) ? $cart['id'] : 'empty';

            \Log::error('Controller' . ' fails get api_tarif_list on API',
                ['container' => $trackContainer, 'id' => $trackId]);
        }

        return $seller_prices;
    }

    public function getShippingCostEstimation()
    {

        try {
            $input = Input::all();
            $vendor_id = $input['vendor_id'];

            $vendor_address = DB::connection('read')
                ->table('storehouse_addresses as sa')
                ->join('seal_districts as sd', 'sd.id', '=', 'sa.subdistrict_id')
                ->join('seal_cities as sc', 'sc.id', '=', 'sa.city_id')
                ->join('seal_subdistricts as ss', 'ss.postal_code', '=', 'sa.postal_code')
                ->where('sa.user_id', $vendor_id)
                ->whereNull('sa.deleted_at')
                ->orderBy('sa.updated_at', 'desc')
                ->select([
                    'sd.ro_id as ro_id',
                    'sd.ro_city_id as ro_city_id',
                    'sc.name as city_name',
                    'sa.city_id as city_id'
                ])
                ->first();

            if ($vendor_address) {
                $result['shipping_location'] = $params['buyer_address'] = $vendor_address->city_name;
                $params['ro_destination_city'] = $vendor_address->ro_id;
                $params['ro_origin_city'] = $vendor_address->ro_city_id;
                $params['destinationCityID'] = $params['originCityID'] = $vendor_address->city_id;
                $params['destination'] = $params['origin'] = $input['postal_code'];

                $weight = isset($input['weight_uom']) ? convertWeightMeasurementToGram($input['weight'], $input['weight_uom']) : $input['weight'] * 1000;
                $params['weight'] = $weight;
                
                if (isset($input['user_id'])) {
                    $user_address = DB::connection('read')
                        ->table('order_addresses as oa')
                        ->join('seal_districts as sd', 'sd.id', '=', 'oa.subdistrict_id')
                        ->join('seal_cities as sc', 'sc.id', '=', 'oa.city_id')
                        ->where('oa.user_id', $input['user_id'])
                        ->whereNull('oa.deleted_at')
                        ->orderBy('oa.updated_at', 'desc')
                        ->select([
                            'sd.ro_id as ro_id',
                            'sd.ro_city_id as ro_city_id',
                            'sd.name as city_name',
                            'oa.subdistrict_id as subdistrict_id',
                            'oa.postal_code as postal_code',
                            'oa.city_id as city_id',
                        ])
                        ->first();
                    if ($user_address) {
                        $params['ro_destination_city'] = $user_address->ro_id;
                        $result['shipping_location'] = $params['buyer_address'] = $user_address->city_name;
                        $params['ro_destination_subdistrict'] = $user_address->ro_id;
                        $params['destinationCityID'] = $user_address->city_id;
                        $params['destination'] = $user_address->postal_code;
                    }
                }
    
                $vendorShippings = Vendor::getActiveShipping($vendor_id);
    
                if (!empty($vendorShippings)) {
                    $vendorShippings = json_decode($vendorShippings->shipping_id);
                    $courier = [];
                    $vendorShippingList = [];
                    foreach ($vendorShippings as $vs) {
                        $vendorShippingList[$vs->courier_code . '-' . $vs->service_code] = 1;
    
                        $courier_code = strtolower($vs->courier_code);
                        if ($vs->courier_code != "" && !in_array($vs->courier_code, $courier)) $courier[$courier_code] = $courier_code;
                    }
    
                    $logistics = ShippingService::logistics($params, $courier, $vendor_id, $vendorShippings);
                    $availableShipping = [];
                    foreach ($logistics as $key => $logistic) {
                        if (isset($vendorShippingList[$key])) $availableShipping[$key] = $logistic;
                    }
                    if (!empty($availableShipping)) {
                        $result['logistics'] = $availableShipping;
                        $response['status'] = 1;
                        $response['message'] = 'success retrieving shipping';
                        $response['result'] = $result;
                        return $response;
                    }
                }
            }

            $response['status'] = 0;
            $response['message'] = 'error when retrieving shipping or data not found';
            $response['result'] = null;
            return $response;
        } catch (Exception $e) {
            $log = ['Service' => 'OrderController', 'function' => 'getShippingCostEstimation'];
            array_push($log, $params);
            array_push($log, $courier);
            logError($e, $log);
        }
    }

    public static function loopCurlMock($key, $val, $shippings)
    {
        return self::loopCurl($key, $val, $shippings, 1);
    }

    /**
     * [postMethodChange controller post for change payment method]
     * @param string $params [get params value]
     * @return [json]         [description]
     */
    public static function postMethodChange()
    {
        $params = Input::all();

        $changePaymentMethod = Orders::changePaymentMethod($params);

        return json($changePaymentMethod, 200);
    }

    /*********************** super seller ***********************/

    public static function postSuperSellerTransaction()
    {
        $params = Input::all();
        $insert_transaction = SuperSellerOrder::createOrder($params);
        return json($insert_transaction, 200);
    }

    /**
     * [postMethodChange controller post for change payment method]
     * @param string $params [get params value]
     * @return [json]         [description]
     */
    public static function postMethodChangeSuperSeller()
    {
        $params = Input::all();

        $changePaymentMethod = SuperSellerOrder::changePaymentMethodSuperSeller($params);

        return json($changePaymentMethod, 200);
    }

    /** get payment_type from kredivo
     * @return \Illuminate\Http\JsonResponse
     */
    public function postKredivoType()
    {
        $params = Input::all();

        $result = Orders::KredivoType($params);

        return response()->json($result, 200);
    }

    /**
     * this function only used for sending email notification if needed
     * @return string
     */
    public function postManualNotif()
    {
        $params = Input::all();
        $orderId = (isset($params['order_id'])) ? $params['order_id'] : 0;
        $result = Orders::manualNotif($orderId);

        return $result;
    }

    public function getUnpaid()
    {
        return OrderService::doAutoReject();
    }

    public function getRejectBefore2019($limit = null)
    {
        $orderService = new OrderService();
        return $orderService->rejectBefore2019($limit);
    }

    public function getAutoRejectPartial()
    {
        $params = Input::all();
        $order_serial = (isset($params['order_serial'])) ? $params['order_serial'] : 0;
        $orderService = new OrderService();
        $order_list = $orderService->getRejectPartialOrder($order_serial)->firstOrFail();
        $result = $orderService->rejectBySystem($order_list);
        return json_encode($result);
    }

    public function getOrders(\Illuminate\Http\Request $request)
    {
        $params = Input::all();
        try {
            $take = 10;
            $page = 1;
            $usersession = userSession()['user'];
            $userLanguage = DB::table('users')->whereId($usersession->id)->first()->language;
            $orderService = new OrderService();

            $user_agent = !is_null($request->header('x-user-agent')) ? $request->header('x-user-agent') : '';
            $is_mobile = false;
            if ($user_agent == 'Ralali-Android' || $user_agent == 'Ralali-iOS') $is_mobile = true;

            $mobile_result = [];
            $hasNextPage = false;

            $this->api = null;
            $this->api['status'] = 200;
            $this->api['message'] = 'Success retrieve orders';

            $is_multiple_status = (isset($params['status']) && strpos($params['status'], ',')) ? true : false;
            $is_filter_status = true;

            $selectOrders = $orderService->selectOrders();
            $selectOrderDetails = $orderService->selectOrderDetails();

            $listActiveOrderSerial = [];
            if ($is_mobile) $filter_data = ['active', 'pass'];
            else $filter_data = ['website'];

            foreach ($filter_data as $filter) {
                $query = $orderService->getQueryOrders($usersession->id);
                $union_query = null;
                if ($filter == 'active') {
                    $queryTransactionComplete = $orderService->getQueryOrders($usersession->id);
                    $queryTransactionComplete = $queryTransactionComplete
                        ->leftJoin('order_details', 'order_details.id_order', '=', 'orders.id')
                        ->whereIn('order_details.status_flag_id', [4, 10, 13])
                        ->where('orders.payment_status', 'Paid')
                        ->distinct()
                        ->get(['orders.id']);

                    $listTransactionCompleteId = $listSettlementTransactionId = [];
                    foreach ($queryTransactionComplete as $q) $listTransactionCompleteId[] = $q->id;
                    foreach ($queryTransactionComplete as $q) {
                        if ($q->status_flag_id == 13) {
                            $listSettlementTransactionId[] = $q->id;
                            unset($q);
                        } else
                            $listTransactionCompleteId[] = $q->id;
                    }

                    $queryFilterOnOrderProcess = DB::table('order_details')
                        ->whereIn('id_order', $listTransactionCompleteId)
                        ->whereNotIn('status_flag_id', [4, 10])
                        ->distinct()
                        ->get(['id_order']);

                    foreach ($queryFilterOnOrderProcess as $q) {
                        if ($idx = array_search($q->id_order, $listTransactionCompleteId) !== false)
                            array_splice($listTransactionCompleteId, $idx, 1);
                    }

                    $listTransactionCompleteId = array_merge($listTransactionCompleteId, $listSettlementTransactionId);
                    $query->leftJoin('order_details', 'order_details.id_order', '=', 'orders.id')
                        ->where('order_details.status_flag_id', '!=', 10)
                        ->whereNotIn('orders.id', $listTransactionCompleteId)
                        ->whereIn('orders.payment_status', ['Waiting for Payment', 'Paid']);

                    $union_query = $orderService->getQueryOrders($usersession->id);
                    $union_query->where('orders.payment_status', 'Top Paid')->distinct()->get($selectOrders);
                } else if ($filter == 'pass') {
                    $query->whereNotIn('orders.order_serial', $listActiveOrderSerial);
                } else if ($filter == 'website') {
                    $query = $orderService->getQueryOrders($usersession->id);
                }

                if (!$is_mobile) {
                    if (isset($params['status'])) {
                        $statuses = null;
                        if ($is_multiple_status) $statuses = explode(',', $params['status']);
                        else $statuses = [$params['status']];
                        $total_status = count($statuses);
                        if ($total_status >= 7) {
                            $is_multiple_status = $is_filter_status = false;
                        }
                        if ($is_filter_status) {
                            foreach ($statuses as $key => $status) {
                                $tempQuery = $orderService->getQueryOrders($usersession->id);
                                $tempUnion = null;
                                if ($status == 1) {
                                    $tempUnion = $tempQuery->where('payment_status', 'Waiting for Payment')->whereNull('paid_at');
                                } else if ($status == 2) {
                                    $tempUnion = $tempQuery->leftJoin('order_details', 'order_details.id_order', '=', 'orders.id')
                                        ->where('order_details.status_flag_id', 2)
                                        ->where('orders.payment_status', 'Paid');
                                } else if ($status == 3) {
                                    $tempUnion = $tempQuery->where('payment_status', 'Reject');
                                } else if ($status == 4) {
                                    $tempUnion = $tempQuery->leftJoin('order_details', 'order_details.id_order', '=', 'orders.id')
                                        ->whereIn('order_details.status_flag_id', [10, 13])
                                        ->where('orders.payment_status', 'Paid');
                                } else if ($status == 5) {
                                    $tempUnion = $tempQuery->where('payment_status', 'Waiting for Payment')->whereNotNull('paid_at');
                                } else if ($status == 6) {
                                    $tempUnion = $tempQuery->leftJoin('order_details', 'order_details.id_order', '=', 'orders.id')
                                        ->where('order_details.status_flag_id', '=', 3);
                                } else if ($status == 7) {
                                    $tempUnion = $tempQuery->where('payment_status', 'ToP Paid');
                                }
                                $tempUnion = $tempUnion->distinct()->select($selectOrders);
                                if ($key == 0) {
                                    $query = $tempUnion;
                                } else {
                                    $query->union($tempUnion);
                                }
                            }
                            $is_filter_status = false;
                        }
                    }

                    if (isset($params['start_date'])) {
                        if (!is_null($params['start_date'])) {
                            $startDate = date("Y-m-d", strtotime($params['start_date']));
                            $query->where("orders.created_at", ">=", $startDate . ' 00:00:00');
                        }
                    }

                    if (isset($params['end_date'])) {
                        if (!is_null($params['end_date'])) {
                            $endDate = date("Y-m-d", strtotime($params['end_date']));
                            $query->where("orders.created_at", "<=", $endDate . ' 23:59:59');
                        }
                    }

                    if (isset($params['order_serial']) and $params['order_serial'] != '') {
                        if (!is_null($params['order_serial'])) {
                            $orderSerial = str_replace('---', '/', $params['order_serial']);
                            $query->where('orders.order_serial', 'LIKE', '%' . $orderSerial . '%');
                        }
                    }
                }

                if ($union_query != null) $query->union($union_query);

                if ($is_multiple_status && $filter == 'website') {
                    $query = DB::table(DB::raw("({$query->toSql()}) as a"))
                        ->mergeBindings($query->getQuery())
                        ->orderBy('created_at', 'desc');

                    $total_data = $query->count();
                } else
                    $total_data = $query->distinct()->get($selectOrders)->count();

                if ($filter == 'pass' || $filter == 'website') {
                    if (isset($params['page']) && !is_null($params['page'])) $page = $params['page'];
                    $skip = ($page - 1) * $take;
                    $queries = $query->take($take)->skip($skip);

                    $hasNextPage = ((($page + 1) * $take) - $total_data) < 10 ? true : false;
                }

                if (!$is_mobile) {
                    if ($is_multiple_status) $queries = $queries->get();
                    else $queries = $queries->orderBy('created_at', 'desc')->distinct()->get($selectOrders);
                } else
                    $queries = $query->orderBy('created_at', 'desc')->distinct()->get($selectOrders);

                if ($filter == 'active') {
                    $queries = DB::table(DB::raw("({$query->toSql()}) as a"))
                        ->mergeBindings($query->getQuery())
                        ->orderBy('created_at', 'desc')
                        ->get();
                }

                $listOrderId = $listPaymentByOrderId = [];
                foreach ($queries as $q) {
                    $listOrderId[] = $q->id;
                    $listPaymentByOrderId[$q->id] = [
                        'payment_status' => $q->payment_status,
                        'payment_method' => $q->payment_method,
                    ];
                    $listActiveOrderSerial[] = $q->order_serial;
                }

                $queryOrderDetail = $orderService->getQueryOrderDetails();
                $queryOrderDetail = $queryOrderDetail->whereIn('id_order', $listOrderId)
                    ->get($selectOrderDetails);

                $filterOrderDetail = [];
                foreach ($queryOrderDetail as $detail) {
                    if (!isset($filterOrderDetail[$detail->order_detail_id]) and $detail->status_flag_id_log == $detail->status_flag_id) {
                        $filterOrderDetail[$detail->order_detail_id] = $detail;
                        $filterOrderDetail[$detail->order_detail_id]->status_date = date("d M Y, H:i", strtotime($detail->status_date));
                    }
                }
                $queryOrderDetail = $filterOrderDetail;
                $listItemInitialId = [];
                foreach ($queryOrderDetail as $detail) {
                    $listItemInitialId[] = $detail->item_initial_id;
                }
                $item_images = \DB::table('item_images')
                    ->whereNull('deleted_at')
                    ->whereIn('item_initial_id', $listItemInitialId)
                    ->orderBy('created_at', 'desc')
                    ->get(['item_initial_id', 'name', 'extension', 'path']);
                $item_images = collect($item_images)->groupBy('item_initial_id');

                $finish_transaction = [];
                $tempData = [];
                $base_url = cdn();
                $listOnOrderProcess = [];
                foreach ($queryOrderDetail as $key => $detail) {
                    $step = $orderService->getStep($detail, $listPaymentByOrderId);
                    if ($step != 0) $listOnOrderProcess[$detail->id_order] = true;
                    $tempData['image_step'] = $step;

                    $item_image = null;
                    if (isset($item_images[$detail->item_initial_id])) {
                        $curr_item = $item_images[$detail->item_initial_id][0];
                        $item_image = $base_url . '/' . $curr_item->path . '/' . $curr_item->name . '.' . $curr_item->extension;
                    } else {
                        $item_image = $base_url . '/' . $detail->default_image;
                    }
                    unset($detail->item_initial_id);
                    unset($detail->default_image);

                    $tempData['item_image'] = $item_image;

                    $res = array_merge((array)$detail, $tempData);
                    $queryOrderDetail[$key] = $res;

                    $queryOrderDetail[$key]['microsite_url'] = 'v/' . $detail->microsite_url;

                    $finish_transaction = $orderService->checkFinishTransaction($finish_transaction, $detail, $step);
                }

                $queryOrderDetail = collect($queryOrderDetail)->groupBy('id_order');
                $groupByOrderDetailByVendor = $queryOrderDetail->transform(function ($item, $k) {
                    return $item->groupBy('vendor_id');
                });

                $orderData = [];
                foreach ($queries as $key => $q) {
                    $orderData[$key] = json_decode(json_encode($q), true);
                    if ($filter == 'active') $orderData[$key] = (array)$orderData[$key];
                    $invoice_status = $orderService->currentInvoiceStatus($q, $finish_transaction);
                    $orderData[$key]['invoice_status'] = $invoice_status;
                    unset($orderData[$key]['invoice_serial_id']);

                    $orderData[$key]['is_expired'] = date("Y-m-d") > $q->due_date ? 1 : 0;
                    $orderData[$key]['due_date'] = date("d M Y, H:i", strtotime($q->due_date));
                    $orderData[$key]['payment_expired'] = date("d M Y, H:i", strtotime($q->payment_expired));

                    if (isset($queryOrderDetail[$q->id])) {
                        $orderData[$key]['detail'] = $groupByOrderDetailByVendor[$q->id];

                        $statusOrder = $orderService->statusOrder($q, $finish_transaction, $userLanguage, $listOnOrderProcess);
                        if ($statusOrder['on_order_process'] !== null) $orderData[$key]['on_order_process'] = $statusOrder['on_order_process'];
                        $orderData[$key]['status_order'] = $statusOrder['status'];
                        $orderData[$key]['status_flag_id'] = $statusOrder['status_flag_id'];

                        $tempDetail = $tempListItemImage = [];
                        foreach ($orderData[$key]['detail']->toArray() as $dKey => $detail) {
                            foreach ($detail as $d) {
                                if ($is_mobile) $tempListItemImage[] = $d['item_image'];
                                if (in_array($d['status_flag_id'], [2, 3, 10, 13, 14])) {
                                    $tempDetail[$d['vendor_id']][] = $d;
                                }
                            }
                        }
                        if ($is_mobile) $orderData[$key]['list_item_image'] = $tempListItemImage;
                        unset($orderData[$key]['detail']);
                        $orderData[$key]['detail'] = $tempDetail;
                    }
                }

                if ($is_mobile) {
                    $mobile_result['page'] = isset($params['page']) ? (int)$page : 1;
                    $mobile_result['prevPage'] = $mobile_result['page'] == 1 ? null : $mobile_result['page'] - 1;
                    $mobile_result['nextPage'] = !$hasNextPage ? null : (int)$page + 1;
                    $mobile_result[$filter . '_order'] = $orderData;
                    $mobile_result[$filter . '_total_data'] = $total_data;
                } else {
                    $this->api['result'] = $orderData;
                    $this->api['total'] = $total_data;
                }
            }
            if (!empty($mobile_result)) $this->api['result'] = $mobile_result;

            return $this->api;
        } catch (Exception $e) {
            $log = ['Controller' => 'OrderController', 'function' => 'getOrders'];
            array_push($log, $params);
            logError($e, $log);
        }
    }

    public function getOrderDetails()
    {
        $params = Input::all();
        $usersession = userSession()['user'];
        $orderService = new OrderService();
        try {
            $this->api = null;
            $order = $orderService->getQueryOrders($usersession->id);
            $order = $order->where('orders.order_serial', $params['order_serial'])
                ->first([
                    'orders.id',
                    'orders.order_serial',
                    'orders.created_at as order_date',
                    'orders.faktur as faktur_status',
                    'orders.faktur_file',
                    'orders.tenor_day',
                    'shipping_address',
                    'shipping_id',
                    'free_shipping',
                    'total_shipping_price',
                    'total_shipping_insurance',
                    'total_price',
                    'total_weight',
                    'grand_total',
                    'payment_status',
                    'discount',
                    'additional_fee',
                    'paid_at',
                    'promo_code',
                    'due_date',
                    'payment_method',
                    'va_payment',
                    'payments.entry_date_payment',
                    'payments.random_digit',
                    'payment_method.payment_name',
                    'payment_method.type as payment_type',
                    'payment_method.nomor_rekening_marketplace as nomor_rekening',
                    'status_delivery_order',
                    'invoice.invoice_serial_id',
                ]);

            if ($order) {
                $ccPercentage = null;
                if ($order->payment_name == 'Credit Card') {
                    $settingCCPercentage = DB::table('settings')
                        ->whereNull('deleted_at')
                        ->where('status', '=', 'credit_card_fee_percentage')
                        ->select(['notes'])
                        ->first();

                    if ($settingCCPercentage) {
                        $ccPercentage = $settingCCPercentage->notes;
                    }
                }

                $shippingAddress = json_decode($order->shipping_address);
                $userShipping = [
                    'receiver_name' => $shippingAddress->name,
                    'address' => $shippingAddress->address,
                    'province' => $shippingAddress->province_name,
                    'city' => $shippingAddress->city_name,
                    'subdistrict' => $shippingAddress->subdistrict_name,
                    'postal_code' => $shippingAddress->postal_code
                ];

                $queryOrderDetail = $orderService->getQueryOrderDetails();
                $queryOrderDetail = $queryOrderDetail->leftJoin('seal_shippings', 'seal_shippings.id', '=', 'order_details.shipping_id')
                    ->where('id_order', $order->id)
                    ->get([
                        'order_details.id as raw_order_detail_id',
                        'order_details.id_order',
                        'order_details.vendor_id',
                        'order_details.shipping_id',
                        'order_details.no_resi',
                        'order_details.shipping_price',
                        'seal_shippings.service_code as shipping_service',
                        'seal_shippings.name as shipping',
                        'order_details.weight',
                        'microsites.name_shop as vendor_name',
                        'microsites.microsite_url as microsite_url',
                        'item_initial.alias as item_alias',
                        'order_details.item_name',
                        'order_details.product_quantity',
                        'order_details.price',
                        'order_details.status_flag_id',
                        'order_details.delivery_status',
                        'order_status_logs.status_flag_id as status_flag_id_log',
                        'order_status_logs.created_at as status_date',
                        'items.item_initial_id',
                        'item_initial.default_image',
                    ]);

                $transactionReview = 0;
                $user_id = $usersession->id;
                $productService = new ProductReviewService();
                $isReviewed = $productService->isReviewed($user_id, $order->order_serial);

                foreach ($queryOrderDetail as $detail) {
                    $listItemInitialId[] = $detail->item_initial_id;
                }
                $item_images = \DB::table('item_images')
                    ->whereNull('deleted_at')
                    ->whereIn('item_initial_id', $listItemInitialId)
                    ->orderBy('created_at', 'desc')
                    ->get(['item_initial_id', 'name', 'extension', 'path']);
                $item_images = collect($item_images)->groupBy('item_initial_id');

                $base_url = cdn();
                foreach ($queryOrderDetail as $key => $detail) {
                    $queryOrderDetail[$key]->order_detail_id = encode($detail->raw_order_detail_id);

                    $queryOrderDetail[$key]->item_url = 'v/' . $detail->microsite_url . '/product/' . $detail->item_alias;
                    unset($detail->item_alias);

                    $item_image = null;
                    if (isset($item_images[$detail->item_initial_id])) {
                        $curr_item = $item_images[$detail->item_initial_id][0];
                        $item_image = $base_url . '/' . $curr_item->path . '/' . $curr_item->name . '.' . $curr_item->extension;
                    } else {
                        $item_image = $base_url . '/' . $detail->default_image;
                    }

                    $queryOrderDetail[$key]->item_image = $item_image;
                    unset($detail->item_initial_id);
                    unset($detail->default_image);
                }

                $totalFinishedItems = 0;
                $filterOrderDetail = [];
                foreach ($queryOrderDetail as $detail) {
                    if (!isset($filterOrderDetail[$detail->raw_order_detail_id]) and $detail->status_flag_id_log == $detail->status_flag_id) {
                        $filterOrderDetail[$detail->raw_order_detail_id] = $detail;
                        if ($detail->status_flag_id == 10) {
                            $totalFinishedItems++;
                            if ($detail->delivery_status == 'Complete') $transactionReview++;
                        }
                    }
                }


                $confirmStatus = 0;
                if (!$order->entry_date_payment) {
                    if ($order->payment_status != 'Reject' && $order->payment_status != 'Paid') {
                        if ($totalFinishedItems == 0) {
                            $confirmStatus = 1;
                        }
                    }
                }

                $userLanguage = DB::table('users')->whereId($usersession->id)->first()->language;
                $finish_transaction = [];
                $orderDetail = [];
                foreach ($filterOrderDetail as $detail) {
                    $orderDetail[$detail->vendor_name]['vendor_id'] = $detail->vendor_id;
                    $orderDetail[$detail->vendor_name]['vendor_name'] = $detail->vendor_name;
                    $orderDetail[$detail->vendor_name]['shipping_id'] = $detail->shipping_id;
                    $orderDetail[$detail->vendor_name]['shipping_price'] = $detail->shipping_price;
                    $orderDetail[$detail->vendor_name]['shipping_service'] = $detail->shipping_service;
                    $orderDetail[$detail->vendor_name]['shipping'] = $detail->shipping;
                    $orderDetail[$detail->vendor_name]['status_date'] = date("d M Y, H:i", strtotime($detail->status_date));
                    $orderDetail[$detail->vendor_name]['no_resi'] = $detail->no_resi;
                    $orderDetail[$detail->vendor_name]['status_flag_id'] = $detail->status_flag_id;
                    $orderDetail[$detail->vendor_name]['vendor_url'] = 'v/' . $detail->microsite_url;
                    unset($detail->shipping_id);
                    unset($detail->shipping_price);
                    unset($detail->shipping_service);
                    unset($detail->shipping);
                    unset($detail->vendor_id);
                    unset($detail->status_date);
                    unset($detail->no_resi);
                    unset($detail->microsite_url);

                    $step = $orderService->getStep($detail, $order);
                    $orderDetail[$detail->vendor_name]['image_step'] = $step;

                    $finish_transaction = $orderService->checkFinishTransaction($finish_transaction, $detail, $step);

                    $orderDetail[$detail->vendor_name]['items'][] = $detail;
                    unset($detail->vendor_name);
                }

                $statusOrder = $orderService->statusOrder($order, $finish_transaction, $userLanguage, null);
                $order->status_order = $statusOrder['status'];
                $order->status_filter_id = $statusOrder['status_flag_id'];

                $order->raw_order_id = $order->id;
                $order->id = encode($order->id);

                $order->invoice_status = 0;
                if (!is_null($order->invoice_serial_id)) $order->invoice_status = 1;

                unset($order->shipping_address);

                $promo_info = Orders::getPromoInfo($order->order_serial);

                $order->promoCashback = false;
                $order->amount_promo = 0;

                if (isset($promo_info['result'])) {
                    if ($order->free_shipping == 'Y') $order->amount_promo = $promo_info['result']['free_shipping_value'];
                    else if ($promo_info['result']['subvention_shipping_fee'] !== 0) {
                        $order->amount_promo = $promo_info['result']['subvention_shipping_fee'] + $promo_info['result']['total_discount'];
                    } else if (isset($promo_info['result']['cash_back'])) {
                        $order->promoCashback = true;
                        $order->amount_promo = $promo_info['result']['cash_back'];
                    } else {
                        $order->amount_promo = $promo_info['result']['total_discount'];
                    }
                }

                $order->confirmStatus = $confirmStatus;
                $order->totalFinishedItem = $totalFinishedItems;
                $order->free_shipping = ($order->free_shipping == 'Y') ? true : false;
                $order->ionPay = ($order->payment_type == 'ionpay') ? true : false;
                $order->reviewed = $isReviewed;
                $order->ccPercentage = $ccPercentage;
                $order->transactionReview = $transactionReview;
                $order->userPromoValue = isset($promo_info['result']) ? $promo_info['result'] : null;
                $order->userShipping = $userShipping;
                $order->details = $orderDetail;
                $order->order_date = date("d M Y, H:i", strtotime($order->order_date));

                $this->api = [
                    'status' => 200,
                    'message' => 'Success retrieve order detail',
                    'result' => $order
                ];
            } else {
                $this->api = [
                    'status' => '400',
                    'message' => 'order invalid'
                ];
            }
            return $this->api;
        } catch (Exception $e) {
            $log = ['Controller' => 'OrderController', 'function' => 'getOrderDetails'];
            array_push($log, $params);
            logError($e, $log);
        }
    }

    public function postDeleteOrder()
    {
        $params = Input::all();
        $orderService = new OrderService();
        $result = $orderService->deleteOrder($params);
        return $result;
    }

    public function getOrderReminder()
    {
        $f = new FirebaseOrderService();
        return $f->pushOrderNotif();
    }

    // wrapping from getShippingCostEstimation
    public function getShipping()
    {
        try {
            $input      = Input::all();

            $vendorAddress = DB::connection('read')
                                ->table('storehouse_addresses as sa')
                                ->join('seal_districts as sd', 'sd.id', '=', 'sa.subdistrict_id')
                                ->join('seal_cities as sc', 'sc.id', '=', 'sa.city_id')
                                ->join('seal_subdistricts as ss', 'ss.postal_code', '=', 'sa.postal_code')
                                ->where('sa.user_id', $input['vendor_id'])
                                ->whereNull('sa.deleted_at')
                                ->orderBy('sa.updated_at', 'desc')
                                ->select([
                                    'sd.ro_id as ro_id',
                                    'sd.ro_city_id as ro_city_id',
                                    'sc.name as city_name',
                                    'sa.city_id as city_id',
                                    'sa.postal_code'
                                ])
                                ->first();

            if ($vendorAddress) {
                $weight = isset($input['weight_uom']) ? convertWeightMeasurementToGram($input['weight'], $input['weight_uom']) : $input['weight'] * 1000;

                $result['shipping_location']    = $vendorAddress->city_name;

                $params["buyer_address"]        = $input["city_name"];
                $params['destinationCityID']    = $input["city_id"];
                $params['destination']          = $input['postal_code'];
                $params['ro_destination_city']  = $input["city_id"];

                $params['ro_origin_city']       = $vendorAddress->ro_city_id;
                $params['originCityID']         = $vendorAddress->city_id;
                $params['origin']               = $vendorAddress->postal_code;

                $params['weight']               = $weight;

                if (isset($input["district_id"])) {
                    $subdistrict = DB::connection("read")
                                        ->table("seal_districts")
                                        ->where("id", $input["district_id"])
                                        ->first();

                    if ($subdistrict) {
                        $params['ro_destination_subdistrict'] = $subdistrict->ro_id;
                    }
                }

                if (isset($input['user_id'])) {
                    $userAddress = DB::connection('read')
                                        ->table('order_addresses as oa')
                                        ->join('seal_districts as sd', 'sd.id', '=', 'oa.subdistrict_id')
                                        ->join('seal_cities as sc', 'sc.id', '=', 'oa.city_id')
                                        ->where('oa.user_id', $input['user_id'])
                                        ->whereNull('oa.deleted_at')
                                        ->orderBy('oa.updated_at', 'desc')
                                        ->select([
                                            'sd.ro_id as ro_id',
                                            'sd.ro_city_id as ro_city_id',
                                            'sd.name as city_name',
                                            'oa.subdistrict_id as subdistrict_id',
                                            'oa.postal_code as postal_code',
                                            'oa.city_id as city_id',
                                        ])
                                        ->first();

                    if ($userAddress) {
                        $result['shipping_location']            = $params['buyer_address'] = $userAddress->city_name;
                        $params['ro_destination_city']          = $userAddress->ro_id;
                        $params['ro_destination_subdistrict']   = $userAddress->ro_id;
                        $params['destinationCityID']            = $userAddress->city_id;
                        $params['destination']                  = $userAddress->postal_code;
                    }
                }

                $vendorShippings = Vendor::getActiveShipping($input['vendor_id']);

                if (!empty($vendorShippings)) {
                    $vendorShippings = json_decode($vendorShippings->shipping_id);
                    $courier = [];
                    $vendorShippingList = [];

                    foreach ($vendorShippings as $vs) {
                        $vendorShippingList[$vs->courier_code . '-' . $vs->service_code] = 1;

                        $courier_code = strtolower($vs->courier_code);
                        if ($vs->courier_code != "" && !in_array($vs->courier_code, $courier)) $courier[$courier_code] = $courier_code;
                    }

                    $logistics = ShippingService::logistics($params, $courier, $input['vendor_id'], $vendorShippings);

                    # Inject Ralali Kargo
                    $rkg_weight = ceil($weight / 1000);
                    $injected = false;
                    if($rkg_weight >= 5.1) {
                        # Available only DKI JAKARTA
                        $allowed_province_ids = [6];
                        $disallowed_city_ids = [43];
                        $allowed_province = DB::connection('read')
                            ->table('seal_provinces as sp')
                            ->join('seal_cities as sc', 'sc.province_id', '=', 'sp.id')
                            ->whereIn('sp.id', $allowed_province_ids)
                            ->where('sc.id', $input["city_id"])
                            ->whereNotIn('sc.id', $disallowed_city_ids)
                            ->whereNull('sp.deleted_at')
                            ->select(['sp.id as province_id'])
                            ->first();

                        $allowed_city_ids = [21,22,63,64,69];
                        $allowed_city = DB::connection('read')
                            ->table('seal_cities as sc')
                            ->whereIn('sc.id', $allowed_city_ids)
                            ->where('sc.id', $input["city_id"])
                            ->whereNull('sc.deleted_at')
                            ->select(['sc.id as city_id'])
                            ->first();

                        if($allowed_province != null or $allowed_city != null) {
                            $rkg_price = $rkg_weight <= 10 ? 25000 : ($rkg_weight * 2500);

                            $kargo_key = 'rkg-RKG';
                            $logistics += [
                                $kargo_key => [
                                    "company" => "Ralali Kargo - COD",
                                    "service" => "RKG",
                                    "duration_shortest" => 1,
                                    "duration_longest" => 2,
                                    "price" => $rkg_price,
                                    "source" => "AG-AP",
                                    "vendor_id" => $input['vendor_id'],
                                ]
                            ];

                            $injected = true;
                        }

                        // inject seller logistik pribadi
                        $shippingStyle = ItemInitial::vendorShippingStyle($input['vendor_id']);
                        $vendorFreeOngkir = ShippingService::Shippingstyle($shippingStyle, $params['originCityID'], $params['destinationCityID']);
                        if ($vendorFreeOngkir != null) {
                            array_push($vendorShippings, $vendorFreeOngkir);

                            $logistics += [
                                "rkg-FREE" => [
                                    "company"           => "free ongkir",
                                    "service"           => "",
                                    "duration_shortest" => 0,
                                    "duration_longest"  => 0,
                                    "price"             => 0,
                                    "source"            => "AG-AP",
                                    "vendor_id"         => $input['vendor_id'],
                                ]
                            ];
                        }
                    }
                    # End Inject

                    $availableShipping = [];
                    foreach ($vendorShippings as $vs) {
                        $key = $vs->courier_code . '-' . $vs->service_code;
                        
                        if (isset($logistics[$key])) {
                            $vs->duration_shortest  = $logistics[$key]["duration_shortest"];
                            $vs->duration_longest   = $logistics[$key]["duration_longest"];
                            $vs->price              = $logistics[$key]["price"];
                            $vs->source             = $logistics[$key]["source"];

                            # Rename Ralali Kargo to Ralali Kargo - COD
                            if($injected && $key == $kargo_key) {
                                $vs->name = $logistics[$key]["company"];
                                $vs->source = $logistics[$key]["source"];
                            }
                            # End Rename

                            array_push($availableShipping, $vs);
                        }
                    }
                    
                    if (!empty($availableShipping)) {
                        $response = [
                            "status"    => 1,
                            "message"   => "Success retrieving shipping",
                            "logistics" => $availableShipping,
                            "result" => $result
                        ];
                        
                        return $response;
                    }
                }
            }

            $response = [
                "status"    => 0,
                "message"   => "Error when retrieving shipping or data not found",
                "result"    => null
            ];
            
            return $response;
        } catch (Exception $e) {
            $log = ['Service' => 'OrderController', 'function' => 'getShippingCostEstimation'];
            array_push($log, $params);
            array_push($log, $courier);
            logError($e, $log);
        }
    }
}
