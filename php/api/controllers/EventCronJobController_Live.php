<?php

namespace api\controllers;

use common\models\UserProfitLoss;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\models\EventsRunner;
use common\models\EventsPlayList;
use common\models\PlaceBet;
use common\models\MarketType;
use common\models\User;


/**
 * EventCronJob Controller
 */
class EventCronJobControllerLive extends Controller
{

    // Cricket: actionSetOdds

    public function actionDoActiveEvent()
    {
        if (isset($_GET['mid'])) {
            //$url = 'https://jarvisexch.com/api/betfair/activate/'.$_GET['mid'];
            $url = 'http://52.208.223.36/api/betfair/activate_b/' . $_GET['mid'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseData = curl_exec($ch);
            curl_close($ch);
            $resData = json_decode($responseData);
            echo '<pre>';
            print_r($resData);
            die;
        }
    }

    public function actionTestSet()
    {
        $key = 'cTime';
        $cache = \Yii::$app->cache;
        if ($cache->set($key, time())) {
            echo time();
        } else {
            echo '0';
        }

    }

    public function actionTestGet()
    {
        $key = 'cTime';
        $cache = \Yii::$app->cache;
        if ($cache->get($key)) {
            $data = $cache->get($key);
            echo json_decode($data);
        } else {
            echo '0';
        }

    }


    // Cricket: actionSetOdds

    public function actionTest()
    {
        $e = '12345677';
        $key = 'user:12';

        $p1 = '12343';
        $pl = '123456';

        $cache = \Yii::$app->cache;
        $data = $cache->get($key);

        if ($data != null) {
            $p = 0;
            $data1 = json_decode($data);
            print_r($data1);
            die;
            if ($data1 != null) {
                foreach ($data1 as $d) {

                    if ($d->event_id != $e) {
                        $p += $d->profit;
                    }

                }

            }

            $p += $p1;

            if ($pl > $p) {

                if ($data1 != null) {
                    $f = 0;
                    foreach ($data1 as $d) {

                        if ($d->event_id == $e) {
                            $d->profit = $p1;
                            $f = 1;
                        }

                    }

                    if (!$f) {

                        array_push($data1,
                            ['event_id' => $e, 'profit' => $p1]);

                    }

                    $cache = \Yii::$app->cache;
                    $cache->set($key, json_encode($data1));

                }

            } else {
                echo 'false';
            }

        }

//         $key = 'user:12';
//         $data = [
//             0 => ['event_id'=> '12345678',
//                 'profit' => '1234'],
//             1 => ['event_id'=> '12345677',
//                 'profit' => '123'],
//             3 => ['event_id'=> '12345679',
//                 'profit' => '12345'],
//         ];
//         $cache = \Yii::$app->cache;
//         $cache->set($key,json_encode( $data ) );


    }

    public function actionSetOddsMulti()
    {
        $url1 = 'http://odds.appleexch.uk:3000/getmarket?id=1.156700601';
        $url2 = 'http://odds.appleexch.uk:3000/getmarket?id=1.156699503';
        $url3 = 'http://odds.appleexch.uk:3000/getmarket?id=1.156663476';

        $nodes = array($url1, $url2, $url3);
        $node_count = count($nodes);

        $curl_arr = array();
        $master = curl_multi_init();

        for ($i = 0; $i < $node_count; $i++) {
            $url = $nodes[$i];
            $curl_arr[$i] = curl_init($url);
            curl_setopt($curl_arr[$i], CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($master, $curl_arr[$i]);
        }

        do {
            curl_multi_exec($master, $running);
        } while ($running > 0);


        for ($i = 0; $i < $node_count; $i++) {
            $results[] = curl_multi_getcontent($curl_arr[$i]);
        }
        print_r($results);
    }

    // Cricket: actionSetFancy
    public function actionSetFancyLive()
    {
        if ($_GET['mid']) {

            //$url = 'http://fancy.dream24.bet/price/?name='.$_GET['mid'];
            $url = 'http://52.208.223.36/api/dream/get_session/' . $_GET['mid'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseData = curl_exec($ch);
            curl_close($ch);

            $resData = json_decode($responseData);
            //echo '<pre>';print_r($resData);die;

            if (isset($resData->session) && $resData->session != null) {

                foreach ($resData->session as $session_data) {
                    //echo '<pre>';print_r($session_data);die;
                    $marketId = $_GET['mid'];
                    $key1 = $marketId . '-' . $session_data->SelectionId . '.FY';
                    $suspended = $ballRunning = 'N';
                    if ($session_data->GameStatus == 'Ball Running') {
                        $ballRunning = 'Y';
                    }
                    if ($session_data->GameStatus == 'SUSPENDED' || $session_data->GameStatus == 'Starting soon.') {
                        $suspended = 'Y';
                    }

                    $dataNew = [
                        'no' => $session_data->LayPrice1,
                        'no_rate' => $session_data->LaySize1,
                        'yes' => $session_data->BackPrice1,
                        'yes_rate' => $session_data->BackSize1,
                    ];

                    /*$market = (new \yii\db\Query())
                    ->select(['suspended','ball_running'])->from('market_type')
                    ->where(['market_id' => $key1])
                    ->one();
                    if( $market != null ){
                        
                        if( $market['ball_running'] == 'Y' ){
                            $ballRunning = 'Y';
                        }
                        if( $market['suspended'] == 'Y' ){
                            $suspended = 'Y';
                        }
                        
                    }*/

                    $data1[$key1] = json_encode([
                        'market_id' => $key1,
                        'suspended' => $suspended,
                        'ballRunning' => $ballRunning,
                        'time' => round(microtime(true) * 1000),
                        'data' => $dataNew
                    ]);

                }

                //echo '<pre>';print_r($data1);die;

                if ($data1 != null) {
                    $cache = Yii::$app->cache;
                    $cache->multiSet($data1);
                }
                echo '<pre>';
                print_r($data1);
                die;

            }

        }

    }

    // Cricket: actionGetFancy
    public function actionGetFancy()
    {
        if ($_GET['mid']) {
            //echo 'fancy data - ';
            //echo round(microtime(true) * 1000);

            $cache = Yii::$app->cache;
            $data = $cache->get($_GET['mid']);
            echo $data;
            die;
        }


    }

    // Cricket: actionSetOdds
    public function actionSetOdds()
    {


//        $uId = 1;
//
//        $newList = $newList1 = [];
//
//        $eventArr = (new \yii\db\Query())
//            ->select(['event_id'])->from('events_play_list')
//            ->where(['game_over'=> 'NO', 'play_type' => ['IN_PLAY', 'UPCOMING'] ])
//            ->all();
//
//        $listArr = (new \yii\db\Query())
//            ->select(['event_id'])->from('event_market_status')
//            ->where(['user_id'=>$uId,'market_type' => 'all' , 'byuser' => $uId ])
//            ->all();
//
//        if( $listArr != null ){
//            foreach ( $listArr as $list ){
//                $newList[] = $list['event_id'];
//            }
//        }
//
//        if( $eventArr != null ){
//            foreach ( $eventArr as $event ){
//                if( !in_array( $event['event_id'] , $newList ) ){
//                    $newList1[] = $event['event_id'];
//                }
//
//            }
//        }
//
//        echo '<pre>';print_r($newList1);die;


        //$url = 'http://rohitash.dream24.bet:3000/getmarket?id=1.156937116';

        if (isset($_GET['mid'])) {
            //echo $_GET['mid'];die;

            $startTime = time();
            $endTime = time() + 60;


            //while( $endTime > time() ){

            //sleep(1);

            //$url = 'http://rohitash.dream24.bet:3000/getmarket?id='.$marketId;
            //$url = 'https://jarvisexch.com/api/dream/get_match_odds/'.$_GET['mid'];
            $url = 'http://52.208.223.36/api/dream/get_match_odds/' . $_GET['mid'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseData = curl_exec($ch);
            curl_close($ch);
            $responseDataArr = json_decode($responseData);
            //echo '<pre>';print_r($responseDataArr);die;

            if ($responseDataArr != null) {

                foreach ($responseDataArr as $responseData) {

                    //$marketId = $responseData->marketId.'-MD';
                    $marketId = $responseData->marketId;

                    if (!empty($responseData->runners) && !empty($responseData->runners)) {
                        $responseArr = [];
                        foreach ($responseData->runners as $runners) {

                            $selectionId = $runners->selectionId;

                            $backPrice1 = $backPrice2 = $backPrice3 = '-';
                            $layPrice1 = $layPrice2 = $layPrice3 = '-';
                            $backSize1 = $backSize2 = $backSize3 = '';
                            $laySize1 = $laySize2 = $laySize3 = '';
                            if (isset($runners->ex->availableToBack) && !empty($runners->ex->availableToBack)) {
                                if (isset($runners->ex->availableToBack[0])) {
                                    $backArr1 = $runners->ex->availableToBack[0];
                                    $backPrice1 = number_format($backArr1->price, 2);
                                    $backSize1 = number_format($backArr1->size, 2);
                                }
                                if (isset($runners->ex->availableToBack[1])) {
                                    $backArr2 = $runners->ex->availableToBack[1];
                                    $backPrice2 = number_format($backArr2->price, 2);
                                    $backSize2 = number_format($backArr2->size, 2);
                                }
                                if (isset($runners->ex->availableToBack[2])) {
                                    $backArr3 = $runners->ex->availableToBack[2];
                                    $backPrice3 = number_format($backArr3->price, 2);
                                    $backSize3 = number_format($backArr3->size, 2);
                                }
                            }

                            if (isset($runners->ex->availableToLay) && !empty($runners->ex->availableToLay)) {
                                if (isset($runners->ex->availableToLay[0])) {
                                    $layArr1 = $runners->ex->availableToLay[0];
                                    $layPrice1 = number_format($layArr1->price, 2);
                                    $laySize1 = number_format($layArr1->size, 2);
                                }
                                if (isset($runners->ex->availableToLay[1])) {
                                    $layArr2 = $runners->ex->availableToLay[1];
                                    $layPrice2 = number_format($layArr2->price, 2);
                                    $laySize2 = number_format($layArr2->size, 2);
                                }
                                if (isset($runners->ex->availableToLay[2])) {
                                    $layArr3 = $runners->ex->availableToLay[2];
                                    $layPrice3 = number_format($layArr3->price, 2);
                                    $laySize3 = number_format($layArr3->size, 2);
                                }
                            }

                            $responseArr[] = [
                                'selectionId' => $selectionId,
                                'backPrice1' => $backPrice1,
                                'backSize1' => $backSize1,
                                'backPrice2' => $backPrice2,
                                'backSize2' => $backSize2,
                                'backPrice3' => $backPrice3,
                                'backSize3' => $backSize3,
                                'layPrice1' => $layPrice1,
                                'laySize1' => $laySize1,
                                'layPrice2' => $layPrice2,
                                'laySize2' => $laySize2,
                                'layPrice3' => $layPrice3,
                                'laySize3' => $laySize3
                            ];

                        }

                        $data = [
                            'inplay' => $responseData->inplay,
                            'time' => round(microtime(true) * 1000),
                            'odds' => $responseArr
                        ];

                        //echo '<pre>';print_r($data);die;

                        $cache = Yii::$app->cache;
                        $cache->set($marketId, json_encode($data));

                        echo '<pre>';
                        print_r($data);
                        die;

                    }

                }

            }
            //}

        }

    }

    // Cricket: actionGetOdds
    public function actionGetOdds()
    {
        if (isset($_GET['mid'])) {
            //echo $_GET['mid'];die;
            $marketId = $_GET['mid'] . '-MD';
            $cache = Yii::$app->cache;
            $data = $cache->get($marketId);
            echo $data;
            die;
        }

    }

    // Cricket: actionGetOdds
    public function actionDeleteOdds()
    {
        if (isset($_GET['mid'])) {
            //echo $_GET['mid'];die;
            $marketId = $_GET['mid'] . ':match_odd';
            $cache = Yii::$app->cache;
            $data = $cache->delete($marketId);
        }

    }

    // Cricket: actionRefreshEventList : 1sc
    public function actionSetEventList()
    {
        $url = 'http://master.heavyexch.com/api/markets';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $responseData = curl_exec($ch);
        curl_close($ch);
        $responseData = json_decode($responseData);
        //echo '<pre>';print_r($responseData);die;
        if ($responseData != null) {

            foreach ($responseData as $data) {

                if ($data->name == 'Match Odds') {
                    $marketId = $data->Id;
                    $eventId = $data->matchid;
                    $eventLeague = $data->seriesname;
                    $eventName = $data->matchName;
                    $eventTime = strtotime($data->MstDate) * 1000;
                    $sportId = $data->SportID;

                    // Add Fancy Market Data
//                    if( $sportId == 4 ){
//                        $this->addFancyMarketData($marketId,$eventId);
//                    }

                    $check = (new \yii\db\Query())
                        ->select(['id'])->from('events_play_list')
                        ->where(['sport_id' => $sportId, 'event_id' => $eventId, 'market_id' => $marketId])
                        ->one();

                    if ($check != null) {

//                         if( $check->event_time != $eventTime ){
//                             $check->event_time = $eventTime;
//                         }

//                         $check->save();

                    } else {

                        $today = date('Ymd');
                        $tomorrow = date('Ymd', strtotime($today . ' +1 day'));

                        $eventData = date('Ymd', ($eventTime / 1000));

                        if ($today == $eventData || $tomorrow == $eventData) {
                            $model = new EventsPlayList();
                            $model->sport_id = $sportId;
                            $model->event_id = $eventId;
                            $model->market_id = $marketId;
                            $model->event_league = $eventLeague;
                            $model->event_name = $eventName;
                            $model->event_time = $eventTime;
                            $model->play_type = 'UPCOMING';
                            /*$curTime = strtotime(date('Y-m-d H:i:s',strtotime('+330 minutes', time())) )*1000;
                            if( $eventTime < $curTime ){
                                $model->play_type = 'IN_PLAY';
                            }else{
                                $model->play_type = 'UPCOMING';
                            }*/

                            if ($model->save()) {

                                $runnerModelCheck = (new \yii\db\Query())
                                    ->select(['id'])->from('events_runners')
                                    ->where(['market_id' => $marketId])
                                    ->one();

                                //$runnerModelCheck = EventsRunner::findOne(['market_id'=>$marketId]);
                                if ($runnerModelCheck == null) {
                                    if (isset($data->runners)) {

                                        $runnersArr = json_decode($data->runners);
                                        $dataRnr = [];
                                        foreach ($runnersArr->runners as $runners) {

                                            $dataRnr[] = [
                                                'event_id' => $eventId,
                                                'market_id' => $marketId,
                                                'selection_id' => $runners->selectionId,
                                                'runner' => $runners->runnerName,
                                                'created_at' => time(),
                                                'updated_at' => time(),
                                            ];
                                            /*$runnerModel = new EventsRunner();
                                            $runnerModel->event_id = $eventId;
                                            $runnerModel->market_id = $marketId;
                                            $runnerModel->selection_id = $selId;
                                            $runnerModel->runner = $runnerName;
                                            $runnerModel->save();*/
                                        }

                                        if ($dataRnr != null) {
                                            \Yii::$app->db->createCommand()->batchInsert('events_runners',
                                                ['event_id', 'market_id', 'selection_id', 'runner', 'created_at', 'updated_at'], $dataRnr)->execute();
                                        }
                                    }
                                }

                                $AllUser = $dataUsr = [];
                                $uId = 1;
                                $role = \Yii::$app->authManager->getRolesByUser($uId);
                                if (isset($role['admin']) && $role['admin'] != null) {
                                    $AllUser = $this->getAllUserForAdmin($uId);
                                    array_push($AllUser, $uId);
                                    if ($AllUser != null) {
                                        foreach ($AllUser as $user) {
                                            $dataUsr[] = [
                                                'user_id' => $user,
                                                'event_id' => $eventId,
                                                'market_id' => $marketId,
                                                'market_type' => 'all',
                                                'byuser' => $uId
                                            ];
                                        }

                                    }
                                    if ($dataUsr != null) {
                                        \Yii::$app->db->createCommand()->batchInsert('event_market_status',
                                            ['user_id', 'event_id', 'market_id', 'market_type', 'byuser'], $dataUsr)->execute();
                                    }

                                }
                            }

                        }
                    }

                }

            }

        }

        return true;
    }

    // Cricket: actionSetDataMatchOdds : 1sc
    public function actionSetDataMatchOddsCricket()
    {
        //$t = -microtime(true);

        $eventList = (new \yii\db\Query())
            ->select(['id', 'sport_id', 'event_id', 'market_id'])->from('events_play_list')
            ->where(['game_over' => 'NO', 'status' => 1, 'sport_id' => 4])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        $cache = Yii::$app->cache;

        if ($eventList != null) {

            $blockList = $marketsArr = [];

            $blockData = (new \yii\db\Query())
                ->select(['event_id'])->from('event_market_status')
                ->where(['user_id' => 1, 'market_type' => 'all', 'byuser' => 1])
                ->all();

            if ($blockData != null) {
                foreach ($blockData as $list) {
                    $blockList[] = $list['event_id'];
                }
            }

            if ($eventList != null) {
                foreach ($eventList as $event) {
                    if (!in_array($event['event_id'], $blockList)) {
                        $marketsArr[] = $event['market_id'];
                    }

                }
            }

            //echo '<pre>';print_r($marketsArr);die;

            if ($marketsArr != null) {

                $startTime = time();
                $endTime = time() + 60;

                while ($endTime > time()) {

                    //sleep(1); 300000 micro s == 300 ms
                    usleep(300000);
                    //sleep(1);
                    $markets = implode(',', $marketsArr);

                    //$url = 'http://176.58.120.13:4105/api/getMarket?markets='.$markets;
                    //$url = 'https://jarvisexch.com/api/dream/get_match_odds/'.$markets;
                    //$url = 'http://52.208.223.36/api/dream/get_match_odds/'.$markets;
                    $url = 'http://52.208.223.36:8001/api/betfair/' . $markets;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $responseData = curl_exec($ch);
                    curl_close($ch);
                    $responseData = json_decode($responseData);
                    //echo '<pre>';print_r($responseData);die;

                    if ($responseData != null) {
                        $dataClosed = $dataInplay = $data = [];
                        foreach ($responseData as $resData) {
                            //echo '<pre>';print_r($resData);die;
                            if (isset($resData->status) && $resData->status != 'CLOSED') {

                                $marketId = $resData->marketId;

                                if (!empty($resData->runners)) {
                                    $responseArr = [];
                                    foreach ($resData->runners as $runners) {

                                        $selectionId = $runners->selectionId;

                                        $backPrice1 = $backPrice2 = $backPrice3 = '-';
                                        $layPrice1 = $layPrice2 = $layPrice3 = '-';
                                        $backSize1 = $backSize2 = $backSize3 = '';
                                        $laySize1 = $laySize2 = $laySize3 = '';
                                        if (isset($runners->ex->availableToBack) && !empty($runners->ex->availableToBack)) {
                                            if (isset($runners->ex->availableToBack[0])) {
                                                $backArr1 = $runners->ex->availableToBack[0];
                                                $backPrice1 = number_format($backArr1->price, 2);
                                                $backSize1 = number_format($backArr1->size, 2);
                                            }
                                            if (isset($runners->ex->availableToBack[1])) {
                                                $backArr2 = $runners->ex->availableToBack[1];
                                                $backPrice2 = number_format($backArr2->price, 2);
                                                $backSize2 = number_format($backArr2->size, 2);
                                            }
                                            if (isset($runners->ex->availableToBack[2])) {
                                                $backArr3 = $runners->ex->availableToBack[2];
                                                $backPrice3 = number_format($backArr3->price, 2);
                                                $backSize3 = number_format($backArr3->size, 2);
                                            }
                                        }

                                        if (isset($runners->ex->availableToLay) && !empty($runners->ex->availableToLay)) {
                                            if (isset($runners->ex->availableToLay[0])) {
                                                $layArr1 = $runners->ex->availableToLay[0];
                                                $layPrice1 = number_format($layArr1->price, 2);
                                                $laySize1 = number_format($layArr1->size, 2);
                                            }
                                            if (isset($runners->ex->availableToLay[1])) {
                                                $layArr2 = $runners->ex->availableToLay[1];
                                                $layPrice2 = number_format($layArr2->price, 2);
                                                $laySize2 = number_format($layArr2->size, 2);
                                            }
                                            if (isset($runners->ex->availableToLay[2])) {
                                                $layArr3 = $runners->ex->availableToLay[2];
                                                $layPrice3 = number_format($layArr3->price, 2);
                                                $laySize3 = number_format($layArr3->size, 2);
                                            }
                                        }

                                        $responseArr[] = [
                                            'selectionId' => $selectionId,
                                            'backPrice1' => $backPrice1,
                                            'backSize1' => $backSize1,
                                            'backPrice2' => $backPrice2,
                                            'backSize2' => $backSize2,
                                            'backPrice3' => $backPrice3,
                                            'backSize3' => $backSize3,
                                            'layPrice1' => $layPrice1,
                                            'laySize1' => $laySize1,
                                            'layPrice2' => $layPrice2,
                                            'laySize2' => $laySize2,
                                            'layPrice3' => $layPrice3,
                                            'laySize3' => $laySize3
                                        ];

                                    }

                                    if ($resData->inplay) {
                                        array_push($dataInplay, $marketId);
                                    }

//                                    $data[$marketId] = json_encode ( [
//                                        'market_id' => $marketId,
//                                        'inplay' => $resData->inplay,
//                                        'time' => round(microtime(true) * 1000),
//                                        'odds' => $responseArr
//                                    ]);

                                    $data = json_encode([
                                        'market_id' => $marketId,
                                        'inplay' => $resData->inplay,
                                        'time' => round(microtime(true) * 1000),
                                        'odds' => $responseArr
                                    ]);

                                    $cache->set($marketId, $data);

                                }

                            }

                            if (isset($resData->status) && $resData->status == 'CLOSED') {
                                $marketId = $resData->marketId;
                                array_push($dataClosed, $marketId);
                            }

                        }

                        //$t += microtime(true);

                        //if( $t > 1500000 ){
                        //usleep(500000);
                        //}else{
                        //usleep(300000);
                        //}

                        if ($dataInplay != null) {
                            //echo '<pre>';print_r($dataInplay);die;
                            Yii::$app->db->createCommand()
                                ->update('events_play_list', ['play_type' => 'IN_PLAY'], ['IN', 'market_id', $dataInplay])
                                ->execute();
                        }

                        if ($dataClosed != null) {
                            //echo '<pre>';print_r($dataInplay);die;
                            Yii::$app->db->createCommand()
                                ->update('events_play_list', ['play_type' => 'CLOSED'], ['IN', 'market_id', $dataClosed])
                                ->execute();
                        }

                        //echo $key = $marketId.':match_odd';
                        //echo '<pre>';print_r($data);die;
                        //$cache = Yii::$app->cache;
                        //$cache->multiSet( $data );


                    }

                }
            }
        }

        return true;
    }

    // Football: actionSetDataMatchOdds : 1sc
    public function actionSetDataMatchOddsFootball()
    {

        $eventList = (new \yii\db\Query())
            ->select(['id', 'sport_id', 'event_id', 'market_id'])->from('events_play_list')
            ->where(['game_over' => 'NO', 'status' => 1, 'sport_id' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        $cache = Yii::$app->cache;
        if ($eventList != null) {

            $blockList = $marketsArr = [];

            $blockData = (new \yii\db\Query())
                ->select(['event_id'])->from('event_market_status')
                ->where(['user_id' => 1, 'market_type' => 'all', 'byuser' => 1])
                ->all();

            if ($blockData != null) {
                foreach ($blockData as $list) {
                    $blockList[] = $list['event_id'];
                }
            }

            if ($eventList != null) {
                foreach ($eventList as $event) {
                    if (!in_array($event['event_id'], $blockList)) {
                        $marketsArr[] = $event['market_id'];
                    }

                }
            }

            //echo '<pre>';print_r($marketsArr);die;

            if ($marketsArr != null) {

                $startTime = time();
                $endTime = time() + 60;

                while ($endTime > time()) {

                    //sleep(1); 300000 micro s == 300 ms
                    usleep(500000);
                    //sleep(1);

                    $markets = implode(',', $marketsArr);

                    //$url = 'http://176.58.120.13:4105/api/getMarket?markets='.$markets;
                    //$url = 'https://jarvisexch.com/api/dream/get_match_odds/'.$markets;
                    //$url = 'http://52.208.223.36/api/dream/get_match_odds/'.$markets;
                    $url = 'http://52.208.223.36:8001/api/betfair/' . $markets;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $responseData = curl_exec($ch);
                    curl_close($ch);
                    $responseData = json_decode($responseData);
                    //echo '<pre>';print_r($responseData);die;

                    if ($responseData != null) {
                        $dataClosed = $dataInplay = $data = [];
                        foreach ($responseData as $resData) {
                            //echo '<pre>';print_r($resData);die;
                            if (isset($resData->status) && $resData->status != 'CLOSED') {

                                $marketId = $resData->marketId;

                                if (!empty($resData->runners)) {
                                    $responseArr = [];
                                    foreach ($resData->runners as $runners) {

                                        $selectionId = $runners->selectionId;

                                        $backPrice1 = $backPrice2 = $backPrice3 = '-';
                                        $layPrice1 = $layPrice2 = $layPrice3 = '-';
                                        $backSize1 = $backSize2 = $backSize3 = '';
                                        $laySize1 = $laySize2 = $laySize3 = '';
                                        if (isset($runners->ex->availableToBack) && !empty($runners->ex->availableToBack)) {
                                            if (isset($runners->ex->availableToBack[0])) {
                                                $backArr1 = $runners->ex->availableToBack[0];
                                                $backPrice1 = number_format($backArr1->price, 2);
                                                $backSize1 = number_format($backArr1->size, 2);
                                            }
                                            if (isset($runners->ex->availableToBack[1])) {
                                                $backArr2 = $runners->ex->availableToBack[1];
                                                $backPrice2 = number_format($backArr2->price, 2);
                                                $backSize2 = number_format($backArr2->size, 2);
                                            }
                                            if (isset($runners->ex->availableToBack[2])) {
                                                $backArr3 = $runners->ex->availableToBack[2];
                                                $backPrice3 = number_format($backArr3->price, 2);
                                                $backSize3 = number_format($backArr3->size, 2);
                                            }
                                        }

                                        if (isset($runners->ex->availableToLay) && !empty($runners->ex->availableToLay)) {
                                            if (isset($runners->ex->availableToLay[0])) {
                                                $layArr1 = $runners->ex->availableToLay[0];
                                                $layPrice1 = number_format($layArr1->price, 2);
                                                $laySize1 = number_format($layArr1->size, 2);
                                            }
                                            if (isset($runners->ex->availableToLay[1])) {
                                                $layArr2 = $runners->ex->availableToLay[1];
                                                $layPrice2 = number_format($layArr2->price, 2);
                                                $laySize2 = number_format($layArr2->size, 2);
                                            }
                                            if (isset($runners->ex->availableToLay[2])) {
                                                $layArr3 = $runners->ex->availableToLay[2];
                                                $layPrice3 = number_format($layArr3->price, 2);
                                                $laySize3 = number_format($layArr3->size, 2);
                                            }
                                        }

                                        $responseArr[] = [
                                            'selectionId' => $selectionId,
                                            'backPrice1' => $backPrice1,
                                            'backSize1' => $backSize1,
                                            'backPrice2' => $backPrice2,
                                            'backSize2' => $backSize2,
                                            'backPrice3' => $backPrice3,
                                            'backSize3' => $backSize3,
                                            'layPrice1' => $layPrice1,
                                            'laySize1' => $laySize1,
                                            'layPrice2' => $layPrice2,
                                            'laySize2' => $laySize2,
                                            'layPrice3' => $layPrice3,
                                            'laySize3' => $laySize3
                                        ];

                                    }

                                    if ($resData->inplay) {
                                        array_push($dataInplay, $marketId);
                                    }

//                                    $data[$marketId] = json_encode ( [
//                                        'market_id' => $marketId,
//                                        'inplay' => $resData->inplay,
//                                        'time' => round(microtime(true) * 1000),
//                                        'odds' => $responseArr
//                                    ]);

                                    $data = json_encode([
                                        'market_id' => $marketId,
                                        'inplay' => $resData->inplay,
                                        'time' => round(microtime(true) * 1000),
                                        'odds' => $responseArr
                                    ]);

                                    $cache->set($marketId, $data);

                                }

                            }

                            if (isset($resData->status) && $resData->status == 'CLOSED') {
                                $marketId = $resData->marketId;
                                array_push($dataClosed, $marketId);
                            }

                        }

                        if ($dataInplay != null) {
                            Yii::$app->db->createCommand()
                                ->update('events_play_list', ['play_type' => 'IN_PLAY'], ['IN', 'market_id', $dataInplay])
                                ->execute();
                        }

                        if ($dataClosed != null) {
                            Yii::$app->db->createCommand()
                                ->update('events_play_list', ['play_type' => 'CLOSED'], ['IN', 'market_id', $dataClosed])
                                ->execute();
                        }
                        //echo $key = $marketId.':match_odd';
                        //echo '<pre>';print_r($data);die;
                        //$cache = Yii::$app->cache;
                        //$cache->multiSet( $data );

                    }

                }
            }
        }

        return true;
    }

    // Tennis: actionSetDataMatchOddsTennis : 1sc
    public function actionSetDataMatchOddsTennis()
    {

        $eventList = (new \yii\db\Query())
            ->select(['id', 'sport_id', 'event_id', 'market_id'])->from('events_play_list')
            ->where(['game_over' => 'NO', 'status' => 1, 'sport_id' => 2])
            ->orderBy(['id' => SORT_DESC])
            ->all();
        $cache = Yii::$app->cache;
        if ($eventList != null) {

            $blockList = $marketsArr = [];

            $blockData = (new \yii\db\Query())
                ->select(['event_id'])->from('event_market_status')
                ->where(['user_id' => 1, 'market_type' => 'all', 'byuser' => 1])
                ->all();

            if ($blockData != null) {
                foreach ($blockData as $list) {
                    $blockList[] = $list['event_id'];
                }
            }

            if ($eventList != null) {
                foreach ($eventList as $event) {
                    if (!in_array($event['event_id'], $blockList)) {
                        $marketsArr[] = $event['market_id'];
                    }

                }
            }

            //echo '<pre>';print_r($marketsArr);die;

            if ($marketsArr != null) {

                $startTime = time();
                $endTime = time() + 60;

                while ($endTime > time()) {

                    //sleep(1); 300000 micro s == 300 ms
                    usleep(500000);
                    //sleep(1);

                    $markets = implode(',', $marketsArr);

                    //$url = 'http://176.58.120.13:4105/api/getMarket?markets='.$markets;
                    //$url = 'https://jarvisexch.com/api/dream/get_match_odds/'.$markets;
                    //$url = 'http://52.208.223.36/api/dream/get_match_odds/'.$markets;
                    $url = 'http://52.208.223.36:8001/api/betfair/' . $markets;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $responseData = curl_exec($ch);
                    curl_close($ch);
                    $responseData = json_decode($responseData);
                    //echo '<pre>';print_r($responseData);die;

                    if ($responseData != null) {
                        $dataClosed = $dataInplay = $data = [];
                        foreach ($responseData as $resData) {
                            //echo '<pre>';print_r($resData);die;
                            if (isset($resData->status) && $resData->status != 'CLOSED') {

                                $marketId = $resData->marketId;

                                if (!empty($resData->runners)) {
                                    $responseArr = [];
                                    foreach ($resData->runners as $runners) {

                                        $selectionId = $runners->selectionId;

                                        $backPrice1 = $backPrice2 = $backPrice3 = '-';
                                        $layPrice1 = $layPrice2 = $layPrice3 = '-';
                                        $backSize1 = $backSize2 = $backSize3 = '';
                                        $laySize1 = $laySize2 = $laySize3 = '';
                                        if (isset($runners->ex->availableToBack) && !empty($runners->ex->availableToBack)) {
                                            if (isset($runners->ex->availableToBack[0])) {
                                                $backArr1 = $runners->ex->availableToBack[0];
                                                $backPrice1 = number_format($backArr1->price, 2);
                                                $backSize1 = number_format($backArr1->size, 2);
                                            }
                                            if (isset($runners->ex->availableToBack[1])) {
                                                $backArr2 = $runners->ex->availableToBack[1];
                                                $backPrice2 = number_format($backArr2->price, 2);
                                                $backSize2 = number_format($backArr2->size, 2);
                                            }
                                            if (isset($runners->ex->availableToBack[2])) {
                                                $backArr3 = $runners->ex->availableToBack[2];
                                                $backPrice3 = number_format($backArr3->price, 2);
                                                $backSize3 = number_format($backArr3->size, 2);
                                            }
                                        }

                                        if (isset($runners->ex->availableToLay) && !empty($runners->ex->availableToLay)) {
                                            if (isset($runners->ex->availableToLay[0])) {
                                                $layArr1 = $runners->ex->availableToLay[0];
                                                $layPrice1 = number_format($layArr1->price, 2);
                                                $laySize1 = number_format($layArr1->size, 2);
                                            }
                                            if (isset($runners->ex->availableToLay[1])) {
                                                $layArr2 = $runners->ex->availableToLay[1];
                                                $layPrice2 = number_format($layArr2->price, 2);
                                                $laySize2 = number_format($layArr2->size, 2);
                                            }
                                            if (isset($runners->ex->availableToLay[2])) {
                                                $layArr3 = $runners->ex->availableToLay[2];
                                                $layPrice3 = number_format($layArr3->price, 2);
                                                $laySize3 = number_format($layArr3->size, 2);
                                            }
                                        }

                                        $responseArr[] = [
                                            'selectionId' => $selectionId,
                                            'backPrice1' => $backPrice1,
                                            'backSize1' => $backSize1,
                                            'backPrice2' => $backPrice2,
                                            'backSize2' => $backSize2,
                                            'backPrice3' => $backPrice3,
                                            'backSize3' => $backSize3,
                                            'layPrice1' => $layPrice1,
                                            'laySize1' => $laySize1,
                                            'layPrice2' => $layPrice2,
                                            'laySize2' => $laySize2,
                                            'layPrice3' => $layPrice3,
                                            'laySize3' => $laySize3
                                        ];

                                    }

                                    if ($resData->inplay) {
                                        array_push($dataInplay, $marketId);
                                    }

//                                    $data[$marketId] = json_encode ( [
//                                        'market_id' => $marketId,
//                                        'inplay' => $resData->inplay,
//                                        'time' => round(microtime(true) * 1000),
//                                        'odds' => $responseArr
//                                    ]);

                                    $data = json_encode([
                                        'market_id' => $marketId,
                                        'inplay' => $resData->inplay,
                                        'time' => round(microtime(true) * 1000),
                                        'odds' => $responseArr
                                    ]);

                                    $cache->set($marketId, $data);

                                }


                            }

                        }

                        if ($dataInplay != null) {
                            Yii::$app->db->createCommand()
                                ->update('events_play_list', ['play_type' => 'IN_PLAY'], ['IN', 'market_id', $dataInplay])
                                ->execute();
                        }
                        if ($dataClosed != null) {
                            Yii::$app->db->createCommand()
                                ->update('events_play_list', ['play_type' => 'CLOSED'], ['IN', 'market_id', $dataClosed])
                                ->execute();
                        }
                        //echo $key = $marketId.':match_odd';
                        //echo '<pre>';print_r($data);die;
                        //$cache = Yii::$app->cache;
                        //$cache->multiSet( $data );

                    }

                }
            }
        }

        return true;
    }

    // Cricket: actionSetDataFancy : 1sc
    public function actionSetDataFancyOLD()
    {
        $startTime = time();
        $endTime = time() + 60;

        //$url = 'https://jarvisexch.com/api/dream/get_session/';
        $url = 'http://52.208.223.36/api/dream/get_session/';
        //$url = 'http://18.222.77.84:4500/api/nodeFancy?matchid=';

        while ($endTime > time()) {

            sleep(1);
//            usleep(500000);

            $eventList = (new \yii\db\Query())
                ->select(['id', 'sport_id', 'event_id', 'market_id'])->from('events_play_list')
                ->where(['sport_id' => 4, 'game_over' => 'NO', 'status' => 1])
                ->orderBy(['id' => SORT_DESC])
                ->all();
            $responseData = [];
            //echo '<pre>';print_r($eventList);die;
            if ($eventList != null) {

                $blockList = $marketsArr = [];

                $blockData = (new \yii\db\Query())
                    ->select(['event_id'])->from('event_market_status')
                    ->where(['user_id' => 1, 'market_type' => 'all', 'byuser' => 1])
                    ->all();

                if ($blockData != null) {
                    foreach ($blockData as $list) {
                        $blockList[] = $list['event_id'];
                    }
                }

                if ($eventList != null) {
                    foreach ($eventList as $event) {
                        if (!in_array($event['event_id'], $blockList)) {
                            //$marketsArr[] = $event['market_id'];
                            $marketId = $event['market_id'];
                            $nodes[] = $url . $marketId;
                        }
                    }
                }

                //echo '<pre>';print_r($marketsArr);die;

                //foreach ( $eventList as $event ){
                //    $marketId = $event['market_id'];
                //    $nodes[] =  $url.$marketId;
                //}

            }

            $node_count = count($nodes);

            $curl_arr = array();
            $master = curl_multi_init();

            for ($i = 0; $i < $node_count; $i++) {
                $url = $nodes[$i];
                $curl_arr[$i] = curl_init($url);
                curl_setopt($curl_arr[$i], CURLOPT_RETURNTRANSFER, true);
                curl_multi_add_handle($master, $curl_arr[$i]);
            }

            do {
                curl_multi_exec($master, $running);
            } while ($running > 0);


            for ($i = 0; $i < $node_count; $i++) {
                $responseData[] = curl_multi_getcontent($curl_arr[$i]);
            }


            //echo '<pre>';print_r( $responseData );die;
            $data1 = [];
            if ($responseData != null) {

                foreach ($responseData as $resData) {

                    $resData = json_decode($resData);

                    if (isset($resData->session) && $resData->session != null) {

                        foreach ($resData->session as $session_data) {

                            $marketId = $resData->market_id;

                            $key1 = $marketId . '-' . $session_data->SelectionId . '.FY';
                            $suspended = $ballRunning = 'N';

                            if ($session_data->GameStatus == 'Ball Running') {
                                $ballRunning = 'Y';
                                $dataNew = [
                                    'no' => 0,
                                    'no_rate' => 0,
                                    'yes' => 0,
                                    'yes_rate' => 0,
                                ];

                            } else if ($session_data->GameStatus == 'SUSPENDED' || $session_data->GameStatus == 'Starting soon.') {
                                $suspended = 'Y';
                                $dataNew = [
                                    'no' => 0,
                                    'no_rate' => 0,
                                    'yes' => 0,
                                    'yes_rate' => 0,
                                ];
                            } else {
                                $dataNew = [
                                    'no' => $session_data->LayPrice1,
                                    'no_rate' => $session_data->LaySize1,
                                    'yes' => $session_data->BackPrice1,
                                    'yes_rate' => $session_data->BackSize1,
                                ];
                            }

                            /*$market = (new \yii\db\Query())
                            ->select(['suspended','ball_running'])->from('market_type')
                            ->where(['market_id' => $key1])
                            ->one();
                            
                            if( $market != null ){
                                
                                if( $market['ball_running'] == 'Y' ){
                                    $ballRunning = 'Y';
                                }
                                if( $market['suspended'] == 'Y' ){
                                    $suspended = 'Y';
                                }
                                
                            }*/

                            $data1[$key1] = json_encode([
                                'market_id' => $key1,
                                'suspended' => $suspended,
                                'ballRunning' => $ballRunning,
                                'time' => round(microtime(true) * 1000),
                                'data' => $dataNew
                            ]);

                        }


                    }

                    if ($data1 != null) {
                        $cache = Yii::$app->cache;
                        $cache->multiSet($data1);
                    }


                }

            }

            //echo '<pre>';print_r($data1);

        }

        return true;

    }

    // Cricket: actionSetDataFancy : 1sc
    public function actionSetDataFancy()
    {
        $startTime = time();
        $endTime = time() + 60;

        //$url = 'https://jarvisexch.com/api/dream/get_session/';
        //$url = 'http://52.208.223.36/api/dream/get_session/';
        $url = 'http://fancy1.marutisport.com:4502/api/fancy?matchId=';
        $call_count = 0;

        $eventList = (new \yii\db\Query())
            ->select(['id', 'sport_id', 'event_id', 'market_id'])->from('events_play_list')
            ->where(['sport_id' => 4, 'game_over' => 'NO', 'status' => 1])
            ->andWhere(['!=', 'play_type', 'CLOSED'])
            ->orderBy(['id' => SORT_DESC])
            ->all();
        $responseData = [];
        //echo '<pre>';print_r($eventList);die;
        if ($eventList != null) {

            $blockList = $marketsArr = [];

            $blockData = (new \yii\db\Query())
                ->select(['event_id'])->from('event_market_status')
                ->where(['user_id' => 1, 'market_type' => 'all', 'byuser' => 1])
                ->all();

            if ($blockData != null) {
                foreach ($blockData as $list) {
                    $blockList[] = $list['event_id'];
                }
            }

            if ($eventList != null) {
                foreach ($eventList as $event) {
                    if (!in_array($event['event_id'], $blockList)) {
                        //$marketsArr[] = $event['market_id'];
                        //$marketId = $event['market_id'];
                        //$nodes[] =  $url.$marketId;
                        $eventId = $event['event_id'];
                        $nodes[] = $url . $eventId;
                    }
                }
            }

        }

        while ($endTime > time()) {

            sleep(1);
//            usleep(500000);

            $node_count = count($nodes);
            $curl_arr = array();
            $master = curl_multi_init();

            for ($i = 0; $i < $node_count; $i++) {
                $url = $nodes[$i];
                $curl_arr[$i] = curl_init($url);
                curl_setopt($curl_arr[$i], CURLOPT_RETURNTRANSFER, true);
                curl_multi_add_handle($master, $curl_arr[$i]);
            }

            do {
                curl_multi_exec($master, $running);
            } while ($running > 0);


            for ($i = 0; $i < $node_count; $i++) {
                $responseData[] = curl_multi_getcontent($curl_arr[$i]);
            }

            $data1 = [];
            if ($responseData != null) {

                foreach ($responseData as $resData) {

                    $resData = json_decode($resData);

                    if (isset($resData->data) && $resData->status == 200) {

                        foreach ($resData->data as $sessionData) {

                            $marketId = $sessionData->match_market_id;

                            $key1 = $marketId . '-' . $sessionData->_id . '.FY';
                            $suspended = $ballRunning = 'N';

                            if ($sessionData->DisplayMsg == 'BALL RUNNING') {
                                $ballRunning = 'Y';
                                $dataNew = [
                                    'no' => 0,
                                    'no_rate' => 0,
                                    'yes' => 0,
                                    'yes_rate' => 0,
                                ];

                            } else if ($sessionData->cron_status == 0 || $sessionData->DisplayMsg == 'SUSPENDED') {
                                $suspended = 'Y';
                                $dataNew = [
                                    'no' => 0,
                                    'no_rate' => 0,
                                    'yes' => 0,
                                    'yes_rate' => 0,
                                ];
                            } else {

                                if (isset($sessionData->fancyData[0])) {
                                    $dataNew = [
                                        'no' => $sessionData->fancyData[0]->SessInptNo,
                                        'no_rate' => $sessionData->fancyData[0]->NoValume,
                                        'yes' => $sessionData->fancyData[0]->SessInptYes,
                                        'yes_rate' => $sessionData->fancyData[0]->YesValume,
                                    ];
                                } else {
                                    $suspended = 'Y';
                                    $dataNew = [
                                        'no' => 0,
                                        'no_rate' => 0,
                                        'yes' => 0,
                                        'yes_rate' => 0,
                                    ];
                                }

                            }

                            $data1[$key1] = json_encode([
                                'market_id' => $key1,
                                'suspended' => $suspended,
                                'ballRunning' => $ballRunning,
                                'time' => round(microtime(true) * 1000),
                                'data' => $dataNew
                            ]);

                        }


                    }

                    if ($data1 != null) {
                        $cache = Yii::$app->cache;
                        $cache->multiSet($data1);
                    }


                }

            }
            $call_count++;
            echo 'TIME  -> ' . time().' , CALL NUMBER :-> ' . $call_count.' , NO OF EVENT :-> '.$node_count.'<br>';
        }

        return true;

    }


    // Cricket: actionSetDataScore : 1sc
    public function actionSetDataScore()
    {
        if ($_GET['eid']) {
            $url = 'http://score.royalebet.uk/4/' . $_GET['eid'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseData = curl_exec($ch);
            curl_close($ch);
            echo '<pre>';
            print_r($responseData);
            $responseData = json_decode($responseData);
            echo '<pre>';
            print_r($responseData);
            die;
        }


    }

    public function actionUnmatchToMatch()
    {
        // Ckeck UnMatch Bets

        $startTime = time();
        $endTime = time() + 60;

        while ($endTime > time()) {

            sleep(1);
            $unmatchBetList = (new \yii\db\Query())
                ->select(['market_id'])->from('place_bet')
                ->where(['session_type' => 'match_odd', 'match_unmatch' => 0, 'status' => 1, 'bet_status' => 'Pending'])
                ->groupBy(['market_id'])->all();


            $marketsArr = [];
            if ($unmatchBetList != null) {

                foreach ($unmatchBetList as $market) {
                    $marketsArr[] = $market['market_id'];
                }

                $markets = implode(',', $marketsArr);

                //$url = 'http://176.58.120.13:4105/api/getMarket?markets='.$markets;
                $url = 'http://52.208.223.36:8001/api/betfair/' . $markets;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $responseData = curl_exec($ch);
                curl_close($ch);
                $responseDataArr = json_decode($responseData);

                //var_dump( $responseData );die;
                foreach ($responseDataArr as $responseData) {

                    if (!empty($responseData->runners) && !empty($responseData->runners)) {
                        $marketId = $responseData->marketId;
                        foreach ($responseData->runners as $runners) {

                            $selectionId = $runners->selectionId;

                            if (isset($runners->ex->availableToBack) && !empty($runners->ex->availableToBack)) {
                                $backArr = $runners->ex->availableToBack[0];
                                $price = $backArr->price;
                                if ($price != '' || $price != ' - ' || $price != null || $price != '0')
                                    $this->updateUnmatchedData($marketId, 'back', $price, $selectionId);
                            }

                            if (isset($runners->ex->availableToLay) && !empty($runners->ex->availableToLay)) {
                                $layArr = $runners->ex->availableToLay[0];
                                $price = $layArr->price;
                                if ($price != '' || $price != ' - ' || $price != null || $price != '0')
                                    $this->updateUnmatchedData($marketId, 'lay', $price, $selectionId);
                            }

                        }

                    }

                }

            }

        }

        return true;
    }


    //AllUserForAdmin
    public function getAllUserForAdmin($uid)
    {
        $userList = [];
        $smdata = (new \yii\db\Query())
            ->select(['id', 'role'])->from('user')
            ->where(['parent_id' => $uid, 'role' => 2])->all();

        if ($smdata != null) {

            foreach ($smdata as $sm) {

                $userList[] = $sm['id'];
                // get all master
                $sm2data = (new \yii\db\Query())
                    ->select(['id', 'role'])->from('user')
                    ->where(['parent_id' => $sm['id'], 'role' => 2])->all();

                if ($sm2data != null) {

                    foreach ($sm2data as $sm2) {
                        $userList[] = $sm2['id'];
                        // get all master
                        $m1data = (new \yii\db\Query())
                            ->select(['id', 'role'])->from('user')
                            ->where(['parent_id' => $sm2['id'], 'role' => 3])->all();

                        if ($m1data != null) {
                            foreach ($m1data as $m1) {
                                $userList[] = $m1['id'];
                                // get all master
                                $m2data = (new \yii\db\Query())
                                    ->select(['id', 'role'])->from('user')
                                    ->where(['parent_id' => $m1['id'], 'role' => 3])->all();

                                if ($m2data != null) {
                                    foreach ($m2data as $m2) {
                                        $userList[] = $m2['id'];

                                    }
                                }

                            }
                        }
                    }

                }


                // get all master
                $m1data = User::find()->select(['id', 'role'])->where(['parent_id' => $sm['id'], 'role' => 3])->all();
                if ($m1data != null) {
                    foreach ($m1data as $m1) {
                        $userList[] = $m1['id'];
                        // get all master
                        $m2data = (new \yii\db\Query())
                            ->select(['id', 'role'])->from('user')
                            ->where(['parent_id' => $m1['id'], 'role' => 3])->all();
                        if ($m2data != null) {
                            foreach ($m2data as $m2) {
                                $userList[] = $m2['id'];

                            }
                        }

                    }
                }

            }
        }

        // get all master
        $mdata = (new \yii\db\Query())
            ->select(['id', 'role'])->from('user')
            ->where(['parent_id' => $uid, 'role' => 3])->all();


        if ($mdata != null) {

            foreach ($mdata as $m) {
                $userList[] = $m['id'];
                // get all master
                $m2data = (new \yii\db\Query())
                    ->select(['id', 'role'])->from('user')
                    ->where(['parent_id' => $m['id'], 'role' => 3])->all();
                if ($m2data != null) {
                    foreach ($m2data as $m2) {
                        $userList[] = $m2['id'];

                    }
                }

            }

        }

        // get all sub admin and session user
//        $sadata = (new \yii\db\Query())
//            ->select(['id','role'])->from('user')
//            ->where(['parent_id'=>$uid , 'role'=> [4,5]])->all();
//        if($sadata != null){
//
//            foreach ( $sadata as $sa ){
//                $userList[] = $sa['id'];
//            }
//
//        }

        return $userList;

    }


    public function actionRefreshEventListOLD()
    {
        $startTime = time();
        $endTime = time() + 300;

        while ($endTime > time()) {

            sleep(5);
            //CODE for live call api
            $url = 'http://irfan.royalebet.uk/getodds.php?event_id=' . $_GET['id'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseData = curl_exec($ch);
            curl_close($ch);
            $responseData = json_decode($responseData);
            //echo '<pre>';print_r($responseData);die;
            if (isset($responseData->result) && !empty($responseData->result)) {

                foreach ($responseData->result as $result) {

                    $today = date('Ymd');
                    $tomorrow = date('Ymd', strtotime($today . ' +1 day'));
                    $eventDate = date('Ymd', ($result->start / 1000));
                    if ($today == $eventDate || $tomorrow == $eventDate) {

                        $marketId = $result->id;
                        $eventId = $result->event->id;
                        $eventLeague = isset($result->competition->name) ? $result->competition->name : 'No Data';
                        $eventName = $result->event->name;
                        $eventTime = $result->start;

                        // Add Fancy Market Data
                        if ($_GET['id'] == 4) {
                            $this->addFancyMarketData($eventId, $marketId);
                        }

                        $check = EventsPlayList::findOne(['sport_id' => $_GET['id'], 'event_id' => $eventId, 'market_id' => $marketId]);

                        if ($check != null) {

                            if ($result->inPlay == 1 || $result->inPlay == true || $result->inPlay == 'true') {
                                $check->play_type = 'IN_PLAY';
                            } else {
                                $check->play_type = 'UPCOMING';
                            }

                            if ($check->event_time != $result->start) {
                                $check->event_time = $result->start;
                            }

                            if ($check->save()) {
                                $runnerModelCheck = EventsRunner::findOne(['market_id' => $marketId]);
                                if ($runnerModelCheck == null) {
                                    if (isset($result->runners)) {
                                        foreach ($result->runners as $runners) {
                                            $selId = $runners->id;
                                            $runnerName = $runners->name;
                                            $runnerModel = new EventsRunner();
                                            $runnerModel->event_id = $eventId;
                                            $runnerModel->market_id = $marketId;
                                            $runnerModel->selection_id = $selId;
                                            $runnerModel->runner = $runnerName;
                                            $runnerModel->save();
                                        }
                                    }
                                }
                            }


                        } else {
                            $model = new EventsPlayList();
                            $model->sport_id = $_GET['id'];
                            $model->event_id = $eventId;
                            $model->market_id = $marketId;
                            $model->event_league = $eventLeague;
                            $model->event_name = $eventName;
                            $model->event_time = $eventTime;

                            if ($result->inPlay == 1 || $result->inPlay == true || $result->inPlay == 'true') {
                                $model->play_type = 'IN_PLAY';
                            } else {
                                $model->play_type = 'UPCOMING';
                            }
                            if ($model->save()) {
                                $runnerModelCheck = EventsRunner::findOne(['market_id' => $marketId]);
                                if ($runnerModelCheck == null) {
                                    if (isset($result->runners)) {
                                        foreach ($result->runners as $runners) {
                                            $selId = $runners->id;
                                            $runnerName = $runners->name;
                                            $runnerModel = new EventsRunner();
                                            $runnerModel->event_id = $eventId;
                                            $runnerModel->market_id = $marketId;
                                            $runnerModel->selection_id = $selId;
                                            $runnerModel->runner = $runnerName;
                                            $runnerModel->save();
                                        }
                                    }
                                }
                            }
                        }

                    }

                }

            }

            // Ckeck UnMatch Bets
            $unmatchBetList = (new \yii\db\Query())
                ->select(['market_id'])->from('place_bet')
                ->where(['session_type' => 'match_odd', 'match_unmatch' => 0, 'status' => 1, 'bet_status' => 'Pending'])
                ->groupBy(['market_id'])->all();

            if ($unmatchBetList != null) {

                foreach ($unmatchBetList as $market) {

                    $marketId = $market['market_id'];

                    $url = 'http://odds.appleexch.uk:3000/getmarket?id=' . $marketId;
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $responseData = curl_exec($ch);
                    curl_close($ch);
                    $responseData = json_decode($responseData);

                    if (!empty($responseData->runners) && !empty($responseData->runners)) {

                        foreach ($responseData->runners as $runners) {

                            $selectionId = $runners->selectionId;

                            if (isset($runners->ex->availableToBack) && !empty($runners->ex->availableToBack)) {
                                $backArr = $runners->ex->availableToBack[0];
                                $price = $backArr->price;
                                if ($price != '' || $price != ' - ' || $price != null || $price != '0')
                                    $this->updateUnmatchedData($marketId, 'back', $price, $selectionId);
                            }

                            if (isset($runners->ex->availableToLay) && !empty($runners->ex->availableToLay)) {
                                $layArr = $runners->ex->availableToLay[0];
                                $price = $layArr->price;
                                if ($price != '' || $price != ' - ' || $price != null || $price != '0')
                                    $this->updateUnmatchedData($marketId, 'lay', $price, $selectionId);
                            }

                        }

                    }

                }

            }

        }
        return true;
    }

    // add Fancy Market Data
    public function addFancyMarketDatLIVE($eventId, $marketId)
    {
        if (isset($marketId)) {
            //CODE for live call api
            //$url = 'http://fancy.dream24.bet/price/?name='.$marketId;
            $url = 'https://jarvisexch.com/api/dream/get_session/' . $marketId;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseData = curl_exec($ch);
            curl_close($ch);
            $responseData = json_decode($responseData);
            //echo '<pre>';print_r($responseData);die;

            if (isset($responseData->session)) {
                foreach ($responseData->session as $data) {

                    $check = MarketType::findOne(['market_id' => $data->market_id, 'event_id' => $eventId]);

                    if ($check == null) {

                        $model = New MarketType();

                        $model->event_type_id = 4;
                        $model->market_id = $marketId . '-' . $data->SelectionId . '.FY';
                        $model->event_id = $eventId;
                        $model->market_name = $data->RunnerName;
                        $model->market_type = 'INNINGS_RUNS';
                        $model->suspended = 'Y';
                        $model->ball_running = 'N';
                        $model->status = 2;
                        $model->created_at = time();
                        $model->updated_at = time();

                        $model->save();
                    }

                }
            }

        }

    }

    // add Fancy Market Data
    public function addFancyMarketData($marketId, $eventId)
    {
        if (isset($marketId)) {
            //CODE for live call api
            //$url = 'http://irfan.royalebet.uk/getfancy.php?eventId='.$eventId;
            $url = 'http://52.208.223.36/api/dream/get_session/' . $marketId;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseData = curl_exec($ch);
            curl_close($ch);
            $responseData = json_decode($responseData);

            //echo '<pre>';print_r($responseData);die;
            if (isset($responseData->session) && $responseData->session != null) {
                //if( isset( $responseData->data ) && !empty( $responseData->data ) ){
                foreach ($responseData->session as $data) {

                    //if( $key != 'active' ){

                    $mKey = $marketId . '-' . $data->SelectionId . '.FY';

                    $check = MarketType::findOne(['market_id' => $mKey, 'event_id' => $eventId]);

                    if ($check == null) {

                        $model = New MarketType();

                        $model->event_type_id = 4;
                        $model->market_id = $mKey;
                        $model->event_id = $eventId;
                        $model->market_name = $data->RunnerName;
                        $model->market_type = 'INNINGS_RUNS';
                        $model->suspended = 'Y';
                        $model->ball_running = 'N';
                        $model->status = 2;
                        $model->created_at = time();
                        $model->updated_at = time();

                        $model->save();
                    }

                    //}

                }
                //}
            }
        }

    }


    // Cricket: updateUnmatchedData
    public function updateUnmatchedData($marketId, $type, $odd, $secId)
    {
        //$betIds = [];
        if ($type == 'lay') {
            $where = ['market_id' => $marketId, 'bet_type' => $type, 'sec_id' => $secId, 'match_unmatch' => 0, 'status' => 1];
            $andWhere = ['>=', 'price', $odd];
        } else {
            $where = ['market_id' => $marketId, 'bet_type' => $type, 'sec_id' => $secId, 'match_unmatch' => 0, 'status' => 1];
            $andWhere = ['<=', 'price', $odd];
        }

        $betList = (new \yii\db\Query())
            ->select(['id'])->from('place_bet')
            ->where($where)->andWhere($andWhere)
            ->all();

        if ($betList != null) {
            /*foreach ( $betList as $bet ){
                $betIds[] = $bet['id'];
            }*/
            if ($betList != null) {
                if (PlaceBet::updateAll(['match_unmatch' => 1, 'updated_at' => time()], ['id' => $betList])) {

                    //User List
                    $userList = (new \yii\db\Query())->select(['user_id'])->from('place_bet')
                        ->where($where)->andWhere($andWhere)
                        ->groupBy(['user_id'])->asArray()->all();

                    if ($userList != null) {
                        foreach ($userList as $userData) {
                            $uid = $userData['user_id'];
                            UserProfitLoss::balanceValUpdate($uid);
                        }
                    }

                }


            }

        }

        return;
    }

}